<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ConferenceSession;
use App\Models\User;

trait HandlesRapporteurReportForm
{
    /**
     * Sessions and users needed to render the rapporteur report form.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
     */
    protected function rapporteurReportFormData(): array
    {
        $sessions = ConferenceSession::reportable()
            ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(schedule_days, '$[0]')) ASC")
            ->orderBy('start_time')
            ->get();

        $users = User::orderBy('first_name')->orderBy('last_name')
            ->select('id', 'first_name', 'last_name', 'title', 'affiliation', 'email')
            ->get();

        return [$sessions, $users];
    }

    /**
     * Validation rules shared by the author form and the chief edit-in-place form.
     */
    protected function rapporteurReportRules(): array
    {
        return [
            'conference_session_id'  => 'required|exists:conference_sessions,id',
            'subtheme'               => 'nullable|string|max:255',
            'rapporteur2_user_id'    => 'nullable|exists:users,id',
            'presentations'          => 'nullable|array',
            'presentations.*.abstract_id'   => 'nullable|string|max:50',
            'presentations.*.presenter'     => 'nullable|string|max:255',
            'presentations.*.institution'   => 'nullable|string|max:255',
            'presentations.*.title'         => 'nullable|string|max:500',
            'presentations.*.study_setting' => 'nullable|string|max:255',
            'presentations.*.method'        => 'nullable|string|max:255',
            'presentations.*.key_finding'   => 'nullable|string',
            'presentations.*.implication'   => 'nullable|string',
            'discussion_questions'          => 'nullable|array',
            'discussion_questions.*.question'   => 'nullable|string',
            'discussion_questions.*.raised_by'  => 'nullable|string|max:255',
            'discussion_questions.*.response'   => 'nullable|string',
            'discussion_questions.*.resolved'   => 'nullable|string|max:10',
            'areas_of_agreement'     => 'nullable|string',
            'areas_of_debate'        => 'nullable|string',
            'follow_up_issues'       => 'nullable|string',
            'scientific_message_1'   => 'nullable|string',
            'scientific_message_2'   => 'nullable|string',
            'scientific_message_3'   => 'nullable|string',
            'most_important_finding' => 'nullable|string',
            'evidence_nature'        => 'nullable|array',
            'evidence_nature.*'      => 'nullable|string|max:100',
            'evidence_status'        => 'nullable|string|max:100',
            'important_method'       => 'nullable|string',
            'main_limitation'        => 'nullable|string',
            'recommendations'        => 'nullable|array',
            'recommendations.*.recommendation'    => 'nullable|string',
            'recommendations.*.basis'             => 'nullable|string',
            'recommendations.*.target_audience'   => 'nullable|array',
            'recommendations.*.target_audience.*' => 'nullable|string|max:100',
            'recommendations.*.timeline'          => 'nullable|string|max:50',
            'recommendations.*.priority'          => 'nullable|string|max:20',
        ];
    }
}
