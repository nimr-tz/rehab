<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Large files arrive in chunks small enough for the server's request limit
 * (nginx allows 1 MB per request by default). Chunks wait on the local disk
 * until the last one arrives, then are joined into one file.
 */
class ChunkedUpload
{
    public function __construct(private string $area) {}

    /**
     * Store one chunk. Returns the joined file's path once every chunk is in,
     * otherwise null. The caller removes the joined file with done().
     *
     * @param  array{upload_id: string, index: int, total: int, size: int}  $upload
     *
     * @throws RuntimeException when the joined file is not the size announced
     */
    public function receive(User $user, array $upload, UploadedFile $chunk): ?string
    {
        $temp = Storage::disk('local');
        $dir = $this->dir($user, $upload['upload_id']);

        if ($upload['index'] === 0) {
            $this->clearAbandoned($user);
        }

        $temp->putFileAs($dir, $chunk, sprintf('%05d.part', $upload['index']));

        $parts = collect($temp->files($dir))->filter(fn ($file) => str_ends_with($file, '.part'))->sort()->values();
        if ($parts->count() < $upload['total']) {
            return null;
        }

        $joined = $temp->path("{$dir}/joined");
        $out = fopen($joined, 'wb');
        foreach ($parts as $part) {
            $in = fopen($temp->path($part), 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        if (filesize($joined) !== $upload['size']) {
            $this->done($user, $upload['upload_id']);

            throw new RuntimeException('The upload was incomplete.');
        }

        return $joined;
    }

    /** Remove an upload's chunks and joined file. */
    public function done(User $user, string $uploadId): void
    {
        Storage::disk('local')->deleteDirectory($this->dir($user, $uploadId));
    }

    private function dir(User $user, string $uploadId): string
    {
        return "{$this->area}/{$user->id}/{$uploadId}";
    }

    /** Chunks from uploads that were abandoned more than a day ago. */
    private function clearAbandoned(User $user): void
    {
        $temp = Storage::disk('local');

        foreach ($temp->directories("{$this->area}/{$user->id}") as $dir) {
            $files = $temp->files($dir);
            $latest = $files ? max(array_map(fn ($file) => $temp->lastModified($file), $files)) : 0;

            if ($latest < now()->subDay()->getTimestamp()) {
                $temp->deleteDirectory($dir);
            }
        }
    }
}
