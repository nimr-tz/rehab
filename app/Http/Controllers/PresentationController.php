<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AbstractSubmission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\NotificationService;
use App\Services\EmailNotificationService;

class PresentationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of presentations for the authenticated user
     */
    public function index()
    {
        $submissions = AbstractSubmission::where('user_id', Auth::id())
            ->where('status', 'accepted')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('presentations.index', compact('submissions'));
    }

    /**
     * Show the form for uploading presentation files
     */
    public function show(AbstractSubmission $submission)
    {
        // Check if user owns this submission
        if ($submission->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Check if submission is accepted
        if ($submission->status !== 'accepted') {
            return redirect()->route('presentations.index')
                ->with('error', 'Presentations can only be uploaded for accepted abstracts.');
        }

        return view('presentations.show', compact('submission'));
    }

    /**
     * Handle file upload for presentations
     */
    public function upload(Request $request, AbstractSubmission $submission)
    {
        // Check authorization
        if (!$submission->canUploadPresentation()) {
            return response()->json(['error' => 'Unauthorized or invalid submission status'], 403);
        }

        // Check if presentation_mode is set
        if (empty($submission->presentation_mode)) {
            return response()->json(['error' => 'Presentation mode is not set for this abstract. Please contact an administrator.'], 400);
        }

        // Validate based on presentation mode (normalize to lowercase)
        try {
            $presentationMode = strtolower($submission->presentation_mode);
            $validator = $this->getValidationRules($presentationMode);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'message' => implode(' ', $validator->errors()->all()), // More direct message
                    'errors' => $validator->errors()
                ], 422);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'Please check your file upload. ' . implode(' ', $e->errors()),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Validation error in presentation upload: ' . $e->getMessage());
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->getMessage()
            ], 422);
        }

        try {
            $uploadedFiles = [];
            $hasPrimaryFile = false;

            // Normalize presentation_mode to lowercase for comparison
            $presentationMode = strtolower($submission->presentation_mode);

            switch ($presentationMode) {
                case 'oral':
                    if ($request->hasFile('oral_presentation')) {
                        $uploadedFiles['oral'] = $this->handleOralPresentationUpload($request, $submission);
                    }
                    break;

                case 'poster':
                    if ($request->hasFile('poster_presentation')) {
                        $uploadedFiles['poster'] = $this->handlePosterPresentationUpload($request, $submission);
                    }
                    break;

                case 'audio_poster':
                    if ($request->hasFile('audio_file') || $request->hasFile('poster_file') || $request->filled('audio_description')) {
                        $uploadedFiles['audio_poster'] = $this->handleAudioPosterUpload($request, $submission);
                    }
                    break;

                default:
                    return response()->json([
                        'error' => 'Invalid presentation mode: ' . $submission->presentation_mode . '. Expected: oral, poster, or audio_poster'
                    ], 400);
            }

            // Check if at least one file exists (either already vaulted or just uploaded)
            // or if the user is just updating notes
            if (!$submission->hasActualPresentationFiles() && count($uploadedFiles) === 0 && !$request->filled('presentation_notes')) {
                return response()->json([
                    'error' => 'Please upload at least one presentation file.'
                ], 400);
            }

            // Update submission with notes
            if ($request->filled('presentation_notes')) {
                $submission->presentation_notes = $request->presentation_notes;
            }

            // Mark as uploaded if it has files
            if ($submission->hasActualPresentationFiles()) {
                $submission->markPresentationAsUploaded();
            } else {
                $submission->save();
            }

            // Send notifications to author and admins
            try {
                $emailService = app(EmailNotificationService::class);
                $emailService->sendPresentationUploadedNotification($submission);

                // In-app notification for author
                $this->notificationService->createNotification(
                    $submission->user,
                    'presentation_upload',
                    'Presentation Uploaded Successfully',
                    "Your presentation for \"{$submission->title}\" has been uploaded.",
                    ['abstract_id' => $submission->id],
                    route('presentations.show', $submission->id),
                    'medium'
                );

                // In-app notification for admins
                $this->notificationService->createAdminNotification(
                    'presentation_upload',
                    'New Presentation Uploaded',
                    "Author {$submission->user->full_name} has uploaded a presentation for: \"{$submission->title}\"",
                    ['abstract_id' => $submission->id, 'user_id' => $submission->user_id],
                    route('admin.abstracts.view', $submission->id),
                    'medium'
                );
            } catch (\Exception $e) {
                Log::warning("Failed to send presentation upload notifications: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Presentation uploaded successfully!',
                'files' => $uploadedFiles,
                'redirect' => route('presentations.show', $submission)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Presentation upload error: ' . $e->getMessage(), [
                'submission_id' => $submission->id,
                'presentation_mode' => $submission->presentation_mode,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a presentation file
     */
    public function delete(Request $request, AbstractSubmission $submission)
    {
        if (!$submission->canUploadPresentation()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $fileType = $request->input('file_type');
        $fileName = $request->input('file_name');

        try {
            switch ($fileType) {
                case 'oral':
                    if ($submission->oral_presentation_file) {
                        Storage::disk('public')->delete('presentations/' . $submission->oral_presentation_file);
                        $submission->oral_presentation_file = null;
                    }
                    break;

                case 'poster':
                    if ($submission->poster_presentation_file) {
                        Storage::disk('public')->delete('presentations/' . $submission->poster_presentation_file);
                        $submission->poster_presentation_file = null;
                    }
                    break;

                case 'audio_poster':
                    if ($submission->audio_poster_file) {
                        Storage::disk('public')->delete('presentations/' . $submission->audio_poster_file);
                        $submission->audio_poster_file = null;
                    }
                    if ($submission->audio_poster_poster_file) {
                        Storage::disk('public')->delete('presentations/' . $submission->audio_poster_poster_file);
                        $submission->audio_poster_poster_file = null;
                    }
                    $submission->audio_poster_description = null;
                    $submission->audio_poster_duration = null;
                    $submission->audio_poster_metadata = null;
                    break;
            }

            // Reset presentation status to pending if no files remain
            if (!$submission->hasActualPresentationFiles()) {
                $submission->presentation_status = 'pending';
                $submission->presentation_uploaded_at = null;
            }

            $submission->save();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a presentation file
     */
    public function download(AbstractSubmission $submission, $fileType, $fileName = null)
    {
        // Check authorization - users can download their own files, admins can download any
        if ($submission->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        try {
            $filePath = null;

            switch ($fileType) {
                case 'oral':
                    $filePath = 'presentations/' . $submission->oral_presentation_file;
                    break;
                case 'poster':
                    $filePath = 'presentations/' . $submission->poster_presentation_file;
                    break;
                case 'audio_poster_audio':
                    $filePath = 'presentations/' . $submission->audio_poster_file;
                    break;
                case 'audio_poster_poster':
                    $filePath = 'presentations/' . $submission->audio_poster_poster_file;
                    break;
            }

            if (!$filePath || !Storage::disk('public')->exists($filePath)) {
                abort(404, 'File not found');
            }

            $fullPath = Storage::disk('public')->path($filePath);
            return response()->file($fullPath);

        } catch (\Exception $e) {
            abort(500, 'Download failed: ' . $e->getMessage());
        }
    }

    /**
     * Get validation rules based on presentation mode
     */
    private function getValidationRules($presentationMode)
    {
        $rules = [];

        switch ($presentationMode) {
            case 'oral':
                // Using extensions rule instead of mimes to avoid MIME-type detection issues on some servers
                $rules['oral_presentation'] = 'nullable|file|extensions:ppt,pptx,pdf,pps,ppsx|max:51200'; // 50MB
                break;

            case 'poster':
                $rules['poster_presentation'] = 'nullable|file|extensions:mp4|max:102400'; // 100MB
                break;

            case 'audio_poster':
                $rules['audio_file'] = 'nullable|file|extensions:mp3,wav,aac|max:102400'; // 100MB
                $rules['poster_file'] = 'nullable|file|extensions:ppt,pptx,pdf,pps,ppsx|max:25600'; // 25MB
                $rules['audio_description'] = 'nullable|string|max:1000';
                break;
        }

        $rules['presentation_notes'] = 'nullable|string|max:2000';

        return Validator::make(request()->all(), $rules);
    }

    /**
     * Handle oral presentation upload
     */
    private function handleOralPresentationUpload(Request $request, AbstractSubmission $submission)
    {
        $file = $request->file('oral_presentation');
        $fileName = $this->generateFileName($file, $submission, 'oral');

        $file->storeAs('presentations', $fileName, 'public');

        // Delete old file if exists
        if ($submission->oral_presentation_file) {
            Storage::disk('public')->delete('presentations/' . $submission->oral_presentation_file);
        }

        $submission->oral_presentation_file = $fileName;
        $submission->save();

        return $fileName;
    }

    /**
     * Handle poster presentation upload
     */
    private function handlePosterPresentationUpload(Request $request, AbstractSubmission $submission)
    {
        $file = $request->file('poster_presentation');
        $fileName = $this->generateFileName($file, $submission, 'poster');

        $file->storeAs('presentations', $fileName, 'public');

        // Delete old file if exists
        if ($submission->poster_presentation_file) {
            Storage::disk('public')->delete('presentations/' . $submission->poster_presentation_file);
        }

        $submission->poster_presentation_file = $fileName;
        $submission->save();

        return $fileName;
    }

    /**
     * Handle audio poster upload (both audio and poster files)
     */
    private function handleAudioPosterUpload(Request $request, AbstractSubmission $submission)
    {
        $uploadedFiles = [];

        // Handle audio file
        if ($request->hasFile('audio_file')) {
            $audioFile = $request->file('audio_file');
            $audioFileName = $this->generateFileName($audioFile, $submission, 'audio');

            $audioFile->storeAs('presentations', $audioFileName, 'public');

            // Delete old audio file if exists
            if ($submission->audio_poster_file) {
                Storage::disk('public')->delete('presentations/' . $submission->audio_poster_file);
            }

            $submission->audio_poster_file = $audioFileName;
            $uploadedFiles['audio'] = $audioFileName;

            // Extract audio metadata
            $this->extractAudioMetadata($submission, $audioFileName);
        }

        // Handle poster file
        if ($request->hasFile('poster_file')) {
            $posterFile = $request->file('poster_file');
            $posterFileName = $this->generateFileName($posterFile, $submission, 'poster');

            $posterFile->storeAs('presentations', $posterFileName, 'public');

            // Delete old poster file if exists
            if ($submission->audio_poster_poster_file) {
                Storage::disk('public')->delete('presentations/' . $submission->audio_poster_poster_file);
            }

            $submission->audio_poster_poster_file = $posterFileName;
            $uploadedFiles['poster'] = $posterFileName;
        }

        // Handle description
        if ($request->filled('audio_description')) {
            $submission->audio_poster_description = $request->audio_description;
        }

        $submission->save();

        return $uploadedFiles;
    }

    /**
     * Generate a unique filename for uploads
     */
    private function generateFileName($file, AbstractSubmission $submission, $type)
    {
        $extension = $file->getClientOriginalExtension();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = Str::slug($originalName);

        return sprintf(
            '%s_%s_%s_%s.%s',
            $submission->id,
            $type,
            $sanitizedName,
            time(),
            $extension
        );
    }

    /**
     * Extract audio metadata
     */
    private function extractAudioMetadata(AbstractSubmission $submission, $audioFileName)
    {
        try {
            $filePath = storage_path('app/public/presentations/' . $audioFileName);

            // Basic file info without getID3
            if (file_exists($filePath)) {
                $fileSize = filesize($filePath);
                $mimeType = mime_content_type($filePath);

                $metadata = [
                    'filesize' => $fileSize,
                    'mime_type' => $mimeType,
                    'uploaded_at' => now()->toISOString(),
                ];

                $submission->setAudioPosterMetadata($metadata);
            }
        } catch (\Exception $e) {
            // Silently fail metadata extraction, it's not critical
        }
    }
}
