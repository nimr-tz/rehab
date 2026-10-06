<?php

namespace App\Http\Controllers\Media;

use App\Exceptions\UnreadablePhoto;
use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\GalleryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Receives photo uploads one chunk at a time (see resources/js/photo-uploader.js). */
class UploadController extends Controller
{
    public function __invoke(Request $request, Album $album, GalleryService $gallery): JsonResponse
    {
        $maxMb = config('gallery.max_photo_mb');
        $chunkKb = config('gallery.chunk_kb');

        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255', 'regex:/\.(jpe?g|png)$/i'],
            'size' => ['required', 'integer', 'min:1', 'max:'.($maxMb * 1024 * 1024)],
            'total' => ['required', 'integer', 'min:1'],
            'index' => ['required', 'integer', 'min:0', 'lt:total'],
            'chunk' => ['required', 'file', 'max:'.($chunkKb + 1)],
            'publish' => ['boolean'],
        ], [
            'name.regex' => 'Only JPEG and PNG photos can be uploaded.',
            'size.max' => "Photos can be up to {$maxMb} MB.",
        ]);

        // The chunk count must match the agreed chunk size, so no request is larger than allowed.
        if ((int) $data['total'] !== (int) ceil($data['size'] / ($chunkKb * 1024))) {
            return response()->json(['message' => 'The upload was split incorrectly. Reload the page and try again.'], 422);
        }

        try {
            $result = $gallery->receiveChunk($album, $request->user(), [
                'upload_id' => $data['upload_id'],
                'index' => (int) $data['index'],
                'total' => (int) $data['total'],
                'size' => (int) $data['size'],
                'name' => $data['name'],
            ], $request->file('chunk'), $request->boolean('publish'));
        } catch (UnreadablePhoto $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'done' => $result['done'],
            'duplicate' => $result['duplicate'] ?? false,
            'photo' => isset($result['photo']) ? ['id' => $result['photo']->id, 'thumb' => $result['photo']->url()] : null,
        ]);
    }
}
