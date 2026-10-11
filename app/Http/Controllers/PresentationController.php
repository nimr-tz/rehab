<?php

namespace App\Http\Controllers;

use App\Enums\AbstractStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\User;
use App\Services\ProgrammeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Slides for an accepted abstract. The presenter uploads them before the
 * deadline; the committee can upload them at any time, and both can download.
 */
class PresentationController extends Controller
{
    /** Receives the slides one chunk at a time (see resources/js/file-uploader.js). */
    public function upload(Request $request, AbstractSubmission $abstract, ProgrammeService $programme): JsonResponse
    {
        abort_unless($this->canUpload($request->user(), $abstract), 403, 'Slides can no longer be uploaded for this abstract.');

        $maxMb = config('presentations.max_mb');
        $chunkKb = config('presentations.chunk_kb');
        $extensions = implode('|', config('presentations.extensions'));

        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255', 'regex:/\.('.$extensions.')$/i'],
            'size' => ['required', 'integer', 'min:1', 'max:'.($maxMb * 1024 * 1024)],
            'total' => ['required', 'integer', 'min:1'],
            'index' => ['required', 'integer', 'min:0', 'lt:total'],
            'chunk' => ['required', 'file', 'max:'.($chunkKb + 1)],
        ], [
            'name.regex' => 'Upload the slides as PowerPoint, Keynote, OpenDocument or PDF.',
            'size.max' => "Slides can be up to {$maxMb} MB.",
        ]);

        if ((int) $data['total'] !== (int) ceil($data['size'] / ($chunkKb * 1024))) {
            return response()->json(['message' => 'The upload was split incorrectly. Reload the page and try again.'], 422);
        }

        try {
            $file = $programme->receiveSlides($abstract, $request->user(), [
                'upload_id' => $data['upload_id'],
                'index' => (int) $data['index'],
                'total' => (int) $data['total'],
                'size' => (int) $data['size'],
                'name' => $data['name'],
            ], $request->file('chunk'));
        } catch (RuntimeException) {
            return response()->json(['message' => 'The upload was incomplete. Try again.'], 422);
        }

        return response()->json(['done' => (bool) $file, 'name' => $file?->original_name, 'size' => $file?->sizeLabel()]);
    }

    public function download(Request $request, AbstractSubmission $abstract): StreamedResponse
    {
        $file = $abstract->presentationFile;
        abort_unless($file && $this->canDownload($request->user(), $abstract), 404);

        return Storage::disk(config('presentations.disk'))->download($file->path, ($abstract->code ? $abstract->code.' ' : '').$file->original_name);
    }

    private function canUpload(User $user, AbstractSubmission $abstract): bool
    {
        return $this->isCommittee($user)
            ? $abstract->status === AbstractStatus::Accepted
            : $abstract->user_id === $user->id && $abstract->acceptsPresentationUpload();
    }

    private function canDownload(User $user, AbstractSubmission $abstract): bool
    {
        return $abstract->user_id === $user->id || $this->isCommittee($user);
    }

    private function isCommittee(User $user): bool
    {
        return $user->hasAnyRole([Role::ScientificAdmin->value, Role::Admin->value]);
    }
}
