<?php

namespace App\Services;

use App\Enums\AbstractStatus;
use App\Models\AbstractSubmission;
use App\Models\PresentationFile;
use App\Models\ProgrammeSession;
use App\Models\User;
use App\Support\ChunkedUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Accepted abstracts in the programme: which session each is presented in, in
 * what order, and the slides the presenter uploads for it.
 */
class ProgrammeService
{
    private const CHUNKS = 'presentation-uploads';

    /** Add an accepted abstract to the end of a session. An abstract is presented once. */
    public function schedule(ProgrammeSession $session, AbstractSubmission $abstract): void
    {
        DB::transaction(function () use ($session, $abstract) {
            $abstract = AbstractSubmission::lockForUpdate()->findOrFail($abstract->id);

            if ($abstract->edition_id !== $session->edition_id || $abstract->status !== AbstractStatus::Accepted) {
                throw new InvalidArgumentException('Only accepted abstracts of this summit can be scheduled.');
            }
            if (! $session->isScannable()) {
                throw new InvalidArgumentException('Abstracts cannot be presented in a break.');
            }
            if ($abstract->sessions()->exists()) {
                throw new InvalidArgumentException($abstract->code.' is already scheduled in '.$abstract->sessions()->first()->title.'. Remove it there first.');
            }

            $session->abstracts()->attach($abstract->id, ['position' => (int) $session->abstracts()->max('position') + 1]);
        });
    }

    public function unschedule(ProgrammeSession $session, AbstractSubmission $abstract): void
    {
        DB::transaction(function () use ($session, $abstract) {
            $session->abstracts()->detach($abstract->id);
            $this->renumber($session);
        });
    }

    /** Move a presentation one place earlier (-1) or later (+1) in its session. */
    public function move(ProgrammeSession $session, AbstractSubmission $abstract, int $direction): void
    {
        DB::transaction(function () use ($session, $abstract, $direction) {
            $order = $session->abstracts()->pluck('abstracts.id')->values();
            $from = $order->search($abstract->id);
            $to = $from === false ? false : $from + ($direction < 0 ? -1 : 1);

            if ($from === false || $to < 0 || $to >= $order->count()) {
                return;
            }

            $ids = $order->all();
            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

            foreach ($ids as $i => $id) {
                $session->abstracts()->updateExistingPivot($id, ['position' => $i + 1]);
            }
        });
    }

    /**
     * Store one chunk of the slides. Once the last chunk is in, the file
     * replaces any earlier upload for this abstract.
     *
     * @param  array{upload_id: string, index: int, total: int, size: int, name: string}  $upload
     */
    public function receiveSlides(AbstractSubmission $abstract, User $user, array $upload, UploadedFile $chunk): ?PresentationFile
    {
        $chunks = new ChunkedUpload(self::CHUNKS);
        $joined = $chunks->receive($user, $upload, $chunk);

        if (! $joined) {
            return null;
        }

        try {
            $disk = Storage::disk(config('presentations.disk'));
            $extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
            $path = 'presentations/'.$abstract->edition_id.'/'.($abstract->code ?: $abstract->id).'-'.Str::random(8).'.'.$extension;

            $stream = fopen($joined, 'rb');
            $disk->writeStream($path, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            $old = $abstract->presentationFile;
            $file = $abstract->presentationFile()->updateOrCreate([], [
                'path' => $path,
                'original_name' => Str::limit(basename($upload['name']), 250, ''),
                'bytes' => $upload['size'],
                'uploaded_by' => $user->id,
            ]);

            if ($old && $old->path !== $path) {
                $disk->delete($old->path);
            }

            return $file;
        } finally {
            $chunks->done($user, $upload['upload_id']);
        }
    }

    private function renumber(ProgrammeSession $session): void
    {
        $session->abstracts()->pluck('abstracts.id')->values()
            ->each(fn (int $id, int $i) => $session->abstracts()->updateExistingPivot($id, ['position' => $i + 1]));
    }
}
