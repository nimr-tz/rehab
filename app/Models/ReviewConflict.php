<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewConflict extends Model
{
    use HasFactory;

    protected $fillable = [
        'abstract_submission_id',
        'reviewer_id',
        'conflict_type',
        'conflict_reason',
        'is_resolved'
    ];

    protected $casts = [
        'is_resolved' => 'boolean'
    ];

    // Relationships
    public function abstract()
    {
        return $this->belongsTo(AbstractSubmission::class, 'abstract_submission_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // ✅ REAL CONFLICT DETECTION - Only meaningful conflicts

    public static function detectCollaborationConflict($abstractId, $reviewerId)
    {
        $abstract = AbstractSubmission::find($abstractId);
        $reviewer = User::find($reviewerId);
        
        if (!$abstract || !$reviewer) return false;
        
        // Check if reviewer is listed as co-author
        $coauthors = [];
        if ($abstract->coauthors) {
            if (is_string($abstract->coauthors)) {
                $coauthors = json_decode($abstract->coauthors, true) ?? [];
            } elseif (is_array($abstract->coauthors)) {
                $coauthors = $abstract->coauthors;
            }
        }
            
        foreach ($coauthors as $coauthor) {
            $coauthorName = strtolower(trim($coauthor['name'] ?? ''));
            $reviewerFullName = strtolower(trim($reviewer->name ?? ''));
            $reviewerFirstLast = strtolower(trim(($reviewer->first_name ?? '') . ' ' . ($reviewer->last_name ?? '')));
            
            // Check multiple name formats
            if ($coauthorName === $reviewerFullName || 
                $coauthorName === $reviewerFirstLast ||
                self::namesAreSimilar($coauthorName, $reviewerFullName)) {
                return true;
            }
        }
        
        return false;
    }

    public static function detectAuthorConflict($abstractId, $reviewerId)
    {
        $abstract = AbstractSubmission::find($abstractId);
        $reviewer = User::find($reviewerId);
        
        if (!$abstract || !$reviewer) return false;
        
        // Check if reviewer is the main author
        $authorName = strtolower(trim($abstract->author_name));
        $reviewerFullName = strtolower(trim($reviewer->name ?? ''));
        $reviewerFirstLast = strtolower(trim(($reviewer->first_name ?? '') . ' ' . ($reviewer->last_name ?? '')));
        
        return $authorName === $reviewerFullName || 
               $authorName === $reviewerFirstLast ||
               self::namesAreSimilar($authorName, $reviewerFullName);
    }

    public static function detectSelfReview($abstractId, $reviewerId)
    {
        $abstract = AbstractSubmission::find($abstractId);
        
        if (!$abstract) return false;
        
        // Check if reviewer is the same user who submitted the abstract
        return $abstract->user_id === $reviewerId;
    }

    // Helper method to check name similarity (handles typos, middle names, etc.)
    private static function namesAreSimilar($name1, $name2)
    {
        // Remove common titles and normalize
        $cleanName1 = preg_replace('/\b(dr|prof|professor|mr|ms|mrs)\b\.?\s*/i', '', $name1);
        $cleanName2 = preg_replace('/\b(dr|prof|professor|mr|ms|mrs)\b\.?\s*/i', '', $name2);
        
        // Simple similarity check (can be enhanced)
        similar_text($cleanName1, $cleanName2, $percent);
        return $percent > 85; // 85% similarity threshold
    }

    // Get all conflict types for easy reference
    public static function getConflictTypes()
    {
        return [
            'author' => 'Reviewer is the main author',
            'collaboration' => 'Reviewer is listed as co-author',
            'self_review' => 'User trying to review their own submission',
            'manual' => 'Manually flagged conflict'
        ];
    }

    // Check if a specific conflict type exists
    public static function hasConflictType($abstractId, $reviewerId, $type)
    {
        return self::where('abstract_submission_id', $abstractId)
                  ->where('reviewer_id', $reviewerId)
                  ->where('conflict_type', $type)
                  ->where('is_resolved', false)
                  ->exists();
    }
}