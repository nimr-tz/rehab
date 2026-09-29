<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\AbstractReview;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoRoutingService
{
    public static function processAutoRouting(): array
    {
        $processed = [];
        $failed = [];
        
        // Find abstracts eligible for auto-routing
        $eligibleAbstracts = self::getEligibleAbstracts();
        
        foreach ($eligibleAbstracts as $abstract) {
            try {
                $decision = self::evaluateAutoDecision($abstract);
                
                if ($decision['auto_applicable']) {
                    self::applyAutoDecision($abstract, $decision);
                    $processed[] = [
                        'id' => $abstract->id,
                        'title' => $abstract->title,
                        'decision' => $decision['decision_type'],
                        'confidence' => $decision['confidence']
                    ];
                    
                    Log::info('Auto-routing applied', [
                        'abstract_id' => $abstract->id,
                        'decision' => $decision['decision_type'],
                        'confidence' => $decision['confidence']
                    ]);
                }
            } catch (\Exception $e) {
                $failed[] = [
                    'id' => $abstract->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Auto-routing failed', [
                    'abstract_id' => $abstract->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'processed' => $processed,
            'failed' => $failed,
            'total_eligible' => $eligibleAbstracts->count()
        ];
    }
    
    public static function getEligibleAbstracts()
    {
        return AbstractSubmission::where('status', 'under_review')
            ->whereHas('reviews', function($query) {
                $query->where('status', 'submitted');
            }, '>=', 2)
            ->whereDoesntHave('reviews', function($query) {
                // Exclude if any reviews are still pending
                $query->where('status', 'pending');
            })
            ->where('created_at', '<=', Carbon::now()->subHours(24)) // At least 24 hours old
            ->with(['reviews' => function($query) {
                $query->where('status', 'submitted')
                      ->with('reviewer:id,name,review_experience_years,reviewer_consistency_score');
            }])
            ->get();
    }
    
    public static function evaluateAutoDecision(AbstractSubmission $abstract): array
    {
        $reviews = $abstract->reviews->where('status', 'submitted');
        
        if ($reviews->count() < 2) {
            return ['auto_applicable' => false, 'reason' => 'Insufficient reviews'];
        }
        
        $scores = $reviews->pluck('score')->filter()->toArray();
        $recommendations = $reviews->pluck('recommendation')->toArray();
        
        if (empty($scores) || count($scores) < 2) {
            return ['auto_applicable' => false, 'reason' => 'Missing scores'];
        }
        
        $avgScore = array_sum($scores) / count($scores);
        $scoreDiff = max($scores) - min($scores);
        $uniqueRecommendations = array_unique($recommendations);
        
        // Strong consensus for acceptance
        if (count($uniqueRecommendations) === 1 && 
            in_array($recommendations[0], ['accept_oral', 'accept_poster']) &&
            $avgScore >= 75 && 
            $scoreDiff <= 15 &&
            min($scores) >= 65) {
            
            return [
                'auto_applicable' => true,
                'decision_type' => 'accept',
                'new_status' => 'accepted',
                'confidence' => self::calculateConfidence($reviews, 'accept'),
                'rationale' => "Strong consensus for acceptance (avg: {$avgScore}, range: " . min($scores) . "-" . max($scores) . ")"
            ];
        }
        
        // Strong consensus for rejection
        if (count($uniqueRecommendations) === 1 && 
            $recommendations[0] === 'reject' &&
            $avgScore <= 50 && 
            $scoreDiff <= 20 &&
            max($scores) <= 60) {
            
            return [
                'auto_applicable' => true,
                'decision_type' => 'reject',
                'new_status' => 'rejected',
                'confidence' => self::calculateConfidence($reviews, 'reject'),
                'rationale' => "Strong consensus for rejection (avg: {$avgScore}, range: " . min($scores) . "-" . max($scores) . ")"
            ];
        }
        
        // Minor revision consensus
        if (count($uniqueRecommendations) === 1 && 
            $recommendations[0] === 'minor_revisions' &&
            $avgScore >= 60 && $avgScore <= 75 &&
            $scoreDiff <= 20) {
            
            return [
                'auto_applicable' => true,
                'decision_type' => 'minor_revision',
                'new_status' => 'minor_revision_required',
                'confidence' => self::calculateConfidence($reviews, 'minor_revision'),
                'rationale' => "Consensus for minor revisions (avg: {$avgScore}, range: " . min($scores) . "-" . max($scores) . ")"
            ];
        }
        
        return [
            'auto_applicable' => false,
            'reason' => 'No clear consensus or criteria not met',
            'details' => [
                'unique_recommendations' => count($uniqueRecommendations),
                'avg_score' => $avgScore,
                'score_diff' => $scoreDiff,
                'recommendations' => $recommendations
            ]
        ];
    }
    
    private static function calculateConfidence($reviews, $decisionType): int
    {
        $baseConfidence = 70;
        
        // Review count factor
        if ($reviews->count() >= 3) {
            $baseConfidence += 15;
        } elseif ($reviews->count() >= 4) {
            $baseConfidence += 20;
        }
        
        // Reviewer experience factor
        $totalExperience = 0;
        $reviewerCount = 0;
        
        foreach ($reviews as $review) {
            if ($review->reviewer && $review->reviewer->review_experience_years) {
                $totalExperience += $review->reviewer->review_experience_years;
                $reviewerCount++;
            }
        }
        
        if ($reviewerCount > 0) {
            $avgExperience = $totalExperience / $reviewerCount;
            if ($avgExperience >= 5) {
                $baseConfidence += 10;
            } elseif ($avgExperience >= 3) {
                $baseConfidence += 5;
            }
        }
        
        // Reviewer consistency factor
        $totalConsistency = 0;
        $consistencyCount = 0;
        
        foreach ($reviews as $review) {
            if ($review->reviewer && $review->reviewer->reviewer_consistency_score) {
                $totalConsistency += $review->reviewer->reviewer_consistency_score;
                $consistencyCount++;
            }
        }
        
        if ($consistencyCount > 0) {
            $avgConsistency = $totalConsistency / $consistencyCount;
            if ($avgConsistency >= 80) {
                $baseConfidence += 10;
            } elseif ($avgConsistency >= 70) {
                $baseConfidence += 5;
            }
        }
        
        return min(95, $baseConfidence); // Cap at 95% confidence
    }
    
    private static function applyAutoDecision(AbstractSubmission $abstract, array $decision)
    {
        $updateData = [
            'status' => $decision['new_status'],
            'admin_comment' => 'AUTO-ROUTED: ' . $decision['rationale'],
            'decision_made_at' => Carbon::now(),
            'decision_made_by' => null, // System decision
            'auto_routed' => true,
            'auto_routing_confidence' => $decision['confidence']
        ];
        
        // Add revision-specific data if needed
        if ($decision['decision_type'] === 'minor_revision') {
            $updateData['revision_feedback'] = 'Based on reviewer consensus, please address the minor concerns raised by the reviewers.';
            $updateData['revision_deadline'] = Carbon::now()->addDays(14);
            $updateData['revision_requested_at'] = Carbon::now();
            $updateData['revision_round'] = ($abstract->revision_round ?? 0) + 1;
        }
        
        $abstract->update($updateData);
        
        // Log the auto-routing decision
        Log::info('Auto-routing decision applied', [
            'abstract_id' => $abstract->id,
            'decision_type' => $decision['decision_type'],
            'confidence' => $decision['confidence'],
            'rationale' => $decision['rationale']
        ]);
    }
    
    public static function canAutoRoute(AbstractSubmission $abstract): bool
    {
        $decision = self::evaluateAutoDecision($abstract);
        return $decision['auto_applicable'] && ($decision['confidence'] ?? 0) >= 75;
    }
}