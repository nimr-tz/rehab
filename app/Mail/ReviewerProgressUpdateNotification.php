<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\AbstractSubmission;

class ReviewerProgressUpdateNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $reviewer;
    public $totalAssigned;
    public $completedReviews;
    public $pendingReviews;
    public $completionRate;
    public $averageScore;
    public $updateDate;

    /**
     * Create a new message instance.
     */
    public function __construct(User $reviewer)
    {
        $this->reviewer = $reviewer;
        $this->updateDate = now();
        
        // Calculate reviewer statistics
        $this->calculateReviewerStats();
    }

    /**
     * Calculate reviewer statistics
     */
    private function calculateReviewerStats()
    {
        // Get all assigned abstracts for this reviewer
        $assignedAbstracts = AbstractSubmission::where('reviewer1_id', $this->reviewer->id)
            ->orWhere('reviewer2_id', $this->reviewer->id)
            ->get();

        $this->totalAssigned = $assignedAbstracts->count();
        
        // Count completed reviews
        $this->completedReviews = $assignedAbstracts->filter(function ($abstract) {
            return $abstract->reviewer1_id == $this->reviewer->id && $abstract->reviewer1_score !== null ||
                   $abstract->reviewer2_id == $this->reviewer->id && $abstract->reviewer2_score !== null;
        })->count();
        
        $this->pendingReviews = $this->totalAssigned - $this->completedReviews;
        
        // Calculate completion rate
        $this->completionRate = $this->totalAssigned > 0 ? round(($this->completedReviews / $this->totalAssigned) * 100, 1) : 0;
        
        // Calculate average score
        $scores = [];
        foreach ($assignedAbstracts as $abstract) {
            if ($abstract->reviewer1_id == $this->reviewer->id && $abstract->reviewer1_score !== null) {
                $scores[] = $abstract->reviewer1_score;
            }
            if ($abstract->reviewer2_id == $this->reviewer->id && $abstract->reviewer2_score !== null) {
                $scores[] = $abstract->reviewer2_score;
            }
        }
        
        $this->averageScore = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('conference.short_name') . " " . config('conference.year') . ": Your Review Progress Update",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reviewer-progress-update-notification',
            with: [
                'reviewer' => $this->reviewer,
                'totalAssigned' => $this->totalAssigned,
                'completedReviews' => $this->completedReviews,
                'pendingReviews' => $this->pendingReviews,
                'completionRate' => $this->completionRate,
                'averageScore' => $this->averageScore,
                'updateDate' => $this->updateDate,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
} 