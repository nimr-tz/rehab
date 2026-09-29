@extends('emails.layout')

@section('header')
    Presentation Guidance for Your Accepted Abstract
@endsection

@section('content')
    <p>Dear {{ $user->first_name }} {{ $user->last_name }},</p>

    <p>Your abstract, <strong>{{ $abstract->title }}</strong>, remains accepted for {{ config('conference.short_name') }} {{ config('conference.year') }}.</p>

    <p>To help you prepare a stronger conference presentation, we are sharing reviewer and editorial comments that are available for this abstract.</p>

    @if(($feedbackItems ?? collect())->count() > 0)
        <div class="info-card">
            <h3>Presentation Improvement Notes</h3>
            @foreach($feedbackItems as $item)
                <div style="margin-bottom: 18px;">
                    <p style="margin: 0 0 6px 0; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #1e40af;">
                        {{ $item['source_label'] }}
                        @if(!empty($item['recommendation_label']))
                            · {{ $item['recommendation_label'] }}
                        @endif
                    </p>
                    <p style="margin: 0; color: #334155;">{{ $item['comment'] }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <p>These notes are shared as guidance for strengthening your presentation at the conference. They do not change the acceptance decision.</p>

    <p>You can also review this feedback in your abstract portal.</p>

    <p>Warm regards,<br>
    The {{ config('conference.short_name') }} {{ config('conference.year') }} Organizing Committee</p>
@endsection
