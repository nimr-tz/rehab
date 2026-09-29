<?php

namespace App\Services;

class DecisionTemplateService
{
    public static function getTemplates(): array
    {
        return [
            'accept_strong_consensus' => [
                'title' => 'Accept - Strong Consensus',
                'condition' => 'All reviewers recommend acceptance with high scores',
                'template' => 'This abstract demonstrates {quality_aspects} and has received unanimous positive reviews from qualified reviewers. The average score of {average_score}/100 and consistent recommendations for acceptance indicate strong academic merit. The work contributes significantly to {field_area} and meets all acceptance criteria.',
                'variables' => ['quality_aspects', 'average_score', 'field_area'],
                'auto_applicable' => true,
                'confidence_threshold' => 90
            ],
            
            'reject_strong_consensus' => [
                'title' => 'Reject - Strong Consensus', 
                'condition' => 'All reviewers recommend rejection with low scores',
                'template' => 'After careful review by qualified experts, this abstract does not meet the standards for acceptance. The average score of {average_score}/100 and unanimous rejection recommendations indicate {main_concerns}. The authors are encouraged to address these fundamental issues: {specific_issues}.',
                'variables' => ['average_score', 'main_concerns', 'specific_issues'],
                'auto_applicable' => true,
                'confidence_threshold' => 85
            ],
            
            'accept_after_conflict_resolution' => [
                'title' => 'Accept - After Conflict Resolution',
                'condition' => 'Mixed reviews resolved in favor of acceptance',
                'template' => 'While reviewers initially had differing opinions (scores ranging from {min_score} to {max_score}), careful analysis of the detailed feedback reveals that {acceptance_rationale}. The concerns raised by {dissenting_reviewers} regarding {concerns} are {concern_resolution}. On balance, the work merits acceptance.',
                'variables' => ['min_score', 'max_score', 'acceptance_rationale', 'dissenting_reviewers', 'concerns', 'concern_resolution'],
                'auto_applicable' => false,
                'confidence_threshold' => 70
            ],
            
            'reject_after_conflict_resolution' => [
                'title' => 'Reject - After Conflict Resolution',
                'condition' => 'Mixed reviews resolved in favor of rejection',
                'template' => 'Despite some positive feedback from reviewers, the significant concerns raised outweigh the strengths. Key issues include {major_issues}. While the work shows {positive_aspects}, the fundamental problems with {critical_areas} prevent acceptance at this time. The authors should consider {improvement_suggestions}.',
                'variables' => ['major_issues', 'positive_aspects', 'critical_areas', 'improvement_suggestions'],
                'auto_applicable' => false,
                'confidence_threshold' => 65
            ],
            
            'minor_revision_consensus' => [
                'title' => 'Minor Revision - Consensus',
                'condition' => 'Reviewers agree minor changes needed',
                'template' => 'This work shows promise and addresses an important topic in {field}. Reviewers have identified specific areas for improvement that can be addressed through minor revisions: {revision_points}. Once these changes are made, the work will be suitable for acceptance. The authors have {deadline_days} days to submit revisions.',
                'variables' => ['field', 'revision_points', 'deadline_days'],
                'auto_applicable' => true,
                'confidence_threshold' => 80
            ],
            
            'major_revision_consensus' => [
                'title' => 'Major Revision - Consensus',
                'condition' => 'Reviewers agree substantial changes needed',
                'template' => 'While this work addresses an relevant topic, substantial revisions are required before acceptance can be considered. Reviewers have identified significant concerns: {major_concerns}. The authors must thoroughly address {critical_issues} and provide {additional_requirements}. Given the scope of changes required, {deadline_days} days are provided for revision.',
                'variables' => ['major_concerns', 'critical_issues', 'additional_requirements', 'deadline_days'],
                'auto_applicable' => true,
                'confidence_threshold' => 75
            ],
            
            'expertise_weighted_decision' => [
                'title' => 'Decision Based on Reviewer Expertise',
                'condition' => 'Decision influenced by reviewer expertise weighting',
                'template' => 'This decision has been made considering the relative expertise and experience of the reviewers. {expert_reviewer} with {expertise_details} provided particularly valuable insights regarding {expert_areas}. While there was some disagreement (weighted average: {weighted_score}/100), the expert assessment indicates {final_decision_rationale}.',
                'variables' => ['expert_reviewer', 'expertise_details', 'expert_areas', 'weighted_score', 'final_decision_rationale'],
                'auto_applicable' => false,
                'confidence_threshold' => 60
            ],
            
            'time_sensitive_decision' => [
                'title' => 'Time-Sensitive Decision',
                'condition' => 'Decision made due to deadline constraints',
                'template' => 'Given the approaching deadline and the need to provide timely feedback to authors, this decision has been made based on the available reviews. The {review_count} completed reviews show {review_summary}. While additional review time would be beneficial, the current evidence {decision_justification}.',
                'variables' => ['review_count', 'review_summary', 'decision_justification'],
                'auto_applicable' => false,
                'confidence_threshold' => 55
            ]
        ];
    }
    
    public static function selectTemplate($abstract, $analysis): ?array
    {
        $templates = self::getTemplates();
        $reviews = $abstract->reviews->where('status', 'submitted');
        
        if ($reviews->count() < 2) {
            return null;
        }
        
        $scores = $reviews->pluck('score')->filter()->toArray();
        $recommendations = $reviews->pluck('recommendation')->toArray();
        $uniqueRecommendations = array_unique($recommendations);
        $avgScore = array_sum($scores) / count($scores);
        $conflictScore = $analysis['conflict_analysis']['conflict_score'] ?? 0;
        
        // Strong consensus for acceptance
        if (count($uniqueRecommendations) == 1 && 
            in_array($recommendations[0], ['accept_oral', 'accept_poster']) && 
            $avgScore >= 70) {
            return $templates['accept_strong_consensus'];
        }
        
        // Strong consensus for rejection  
        if (count($uniqueRecommendations) == 1 && 
            $recommendations[0] == 'reject' && 
            $avgScore < 60) {
            return $templates['reject_strong_consensus'];
        }
        
        // Minor revision consensus
        if (count($uniqueRecommendations) == 1 && 
            $recommendations[0] == 'minor_revisions') {
            return $templates['minor_revision_consensus'];
        }
        
        // Major revision consensus
        if (count($uniqueRecommendations) == 1 && 
            $recommendations[0] == 'major_revisions') {
            return $templates['major_revision_consensus'];
        }
        
        // Expertise weighted decision
        if (isset($analysis['review_summary']['weighted_average_score']) && 
            abs($analysis['review_summary']['weighted_average_score'] - $avgScore) > 5) {
            return $templates['expertise_weighted_decision'];
        }
        
        // Conflict resolution templates
        if ($conflictScore >= 40) {
            if ($avgScore >= 65) {
                return $templates['accept_after_conflict_resolution'];
            } else {
                return $templates['reject_after_conflict_resolution'];
            }
        }
        
        return null;
    }
    
    public static function populateTemplate(array $template, $abstract, $analysis): string
    {
        $content = $template['template'];
        $reviews = $abstract->reviews->where('status', 'submitted');
        
        // Extract common variables
        $variables = [
            'average_score' => round($analysis['review_summary']['average_score'] ?? 0, 1),
            'weighted_score' => round($analysis['review_summary']['weighted_average_score'] ?? 0, 1),
            'min_score' => $analysis['review_summary']['score_range']['min'] ?? 0,
            'max_score' => $analysis['review_summary']['score_range']['max'] ?? 0,
            'review_count' => $reviews->count(),
            'field' => $abstract->subtheme ?? 'the relevant field',
            'field_area' => $abstract->subtheme ?? 'this area',
            'deadline_days' => 14
        ];
        
        // Replace variables in template
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        
        // Handle remaining placeholders with generic text
        $content = preg_replace('/\{[^}]+\}/', '[to be customized]', $content);
        
        return $content;
    }
}