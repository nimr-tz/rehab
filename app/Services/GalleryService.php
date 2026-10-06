<?php

namespace App\Services;

use App\Exceptions\UnreadablePhoto;
use App\Models\Album;
use App\Models\Photo;
use App\Models\PhotoRemovalRequest;
use App\Models\User;
use App\Notifications\PhotoRemovalDecided;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Photo uploads and their files.
 *
 * Browsers send each photo in chunks (see config/gallery.php), which are kept
 * on the local disk until the last one arrives. The joined file is then
 * processed straight away, so the photographer sees it within seconds and the
 * server needs no queue worker.
 */
class GalleryService
{
    private const CHUNKS = 'gallery-uploads';

    public function __construct(private PhotoProcessor $processor) {}

    public function disk(): Filesystem
    {
        return Storage::disk(config('gallery.disk'));
    }

    /**
     * Stores one chunk. When it is the last one, joins the chunks and adds the photo.
     *
     * @param  array{upload_id: string, index: int, total: int, size: int, name: string}  $upload
     * @return array{done: bool, photo?: Photo, duplicate?: bool}
     *
     * @throws UnreadablePhoto
     */
    public function receiveChunk(Album $album, User $user, array $upload, UploadedFile $chunk, bool $publish): array
    {
        $temp = Storage::disk('local');
        $dir = self::CHUNKS."/{$user->id}/{$upload['upload_id']}";

        if ($upload['index'] === 0) {
            $this->clearAbandonedUploads($user);
        }

        $temp->putFileAs($dir, $chunk, sprintf('%05d.part', $upload['index']));

        $parts = collect($temp->files($dir))->filter(fn ($file) => str_ends_with($file, '.part'))->sort()->values();
        if ($parts->count() < $upload['total']) {
            return ['done' => false];
        }

        $joined = $temp->path("{$dir}/joined");
        try {
            $out = fopen($joined, 'wb');
            foreach ($parts as $part) {
                $in = fopen($temp->path($part), 'rb');
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
            fclose($out);

            if (filesize($joined) !== $upload['size']) {
                throw new UnreadablePhoto('The upload was incomplete. Try this photo again.');
            }

            $photo = $this->add($album, $user, $joined, $upload['name'], $publish);
        } finally {
            $temp->deleteDirectory($dir);
        }

        return $photo ? ['done' => true, 'photo' => $photo] : ['done' => true, 'duplicate' => true];
    }

    /**
     * Processes a photo file into the album. Returns null when the album already
     * has the same photo, for example when a memory card is uploaded twice.
     *
     * @throws UnreadablePhoto
     */
    public function add(Album $album, User $user, string $path, string $name, bool $publish = false): ?Photo
    {
        $checksum = hash_file('sha256', $path);

        if ($album->photos()->where('checksum', $checksum)->exists()) {
            return null;
        }

        $files = $this->processor->process($path);
        $base = "gallery/{$album->edition_id}/{$album->id}/".Str::uuid();
        $paths = [
            'original_path' => $base.($files['mime'] === 'image/png' ? '.png' : '.jpg'),
            'display_path' => "{$base}-display.jpg",
            'thumb_path' => "{$base}-thumb.jpg",
        ];

        $disk = $this->disk();
        $disk->put($paths['original_path'], $files['original']);
        $disk->put($paths['display_path'], $files['display']);
        $disk->put($paths['thumb_path'], $files['thumb']);

        return $album->photos()->create($paths + [
            'user_id' => $user->id,
            'original_name' => Str::limit(basename($name), 250, ''),
            'mime' => $files['mime'],
            'bytes' => strlen($files['original']),
            'width' => $files['width'],
            'height' => $files['height'],
            'checksum' => $checksum,
            'taken_at' => $files['taken_at'],
            'published_at' => $publish ? now() : null,
        ]);
    }

    public function delete(Photo $photo): void
    {
        $this->disk()->delete([$photo->original_path, $photo->display_path, $photo->thumb_path]);
        $photo->delete();
    }

    public function deleteAlbum(Album $album): void
    {
        $album->photos()->each(fn (Photo $photo) => $this->delete($photo));
        $album->delete();
    }

    /** Takes the photo down, or keeps it, records who decided, and emails the people who asked. */
    public function resolveRemoval(PhotoRemovalRequest $request, User $admin, bool $remove): void
    {
        $photo = $request->photo;

        // Other requests about the same photo are settled by the same decision.
        $requests = $photo
            ? PhotoRemovalRequest::pending()->where('photo_id', $photo->id)->get()
            : collect([$request]);

        DB::transaction(function () use ($requests, $admin, $remove, $photo) {
            PhotoRemovalRequest::whereKey($requests->modelKeys())->update([
                'outcome' => $remove ? 'removed' : 'kept', 'resolved_at' => now(), 'resolved_by' => $admin->id,
            ]);

            if ($remove && $photo) {
                $this->delete($photo);
            }
        });

        $requests->each(fn (PhotoRemovalRequest $request) => Notification::route('mail', $request->email)
            ->notify(new PhotoRemovalDecided($request->refresh())));
    }

    /** Chunks from uploads that were abandoned more than a day ago. */
    private function clearAbandonedUploads(User $user): void
    {
        $temp = Storage::disk('local');

        foreach ($temp->directories(self::CHUNKS."/{$user->id}") as $dir) {
            $files = $temp->files($dir);
            $latest = $files ? max(array_map(fn ($file) => $temp->lastModified($file), $files)) : 0;

            if ($latest < now()->subDay()->getTimestamp()) {
                $temp->deleteDirectory($dir);
            }
        }
    }
}
