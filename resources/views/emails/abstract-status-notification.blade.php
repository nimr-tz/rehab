@extends('emails.layout')

@section('title', $status === 'accepted' ? '🎉 Abstract Accepted' : '📝 Review Update')

@section('content')
    <div class="message-intro">
        Dear {{ $abstract->author_name }},
    </div>
    
    <p>We have completed the review process for your abstract submission. Please find the details below:</p>
    
    <div class="info-card">
        <h3>📋 Abstract Details</h3>
        <table class="details-table">
            <tr>
                <th>Title:</th>
                <td>{{ $abstract->title }}</td>
            </tr>
            <tr>
                <th>Subtheme:</th>
                <td>{{ $abstract->subtheme }}</td>
            </tr>
            <tr>
                <th>Review Status:</th>
                <td>
                    @if($status === 'accepted')
                        <span style="color: #10b981; font-weight: 800;">✅ ACCEPTED</span>
                    @else
                        <span style="color: #ef4444; font-weight: 800;">❌ NOT ACCEPTED</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @if($status === 'accepted')
        <div class="info-card" style="border-left: 6px solid #3b82f6; background: #eff6ff;">
            <h3 style="color: #1d4ed8;">💳 Payments & Registration</h3>
            <p style="margin: 0; color: #1e40af;">Please ensure your conference registration payment is completed successfully in the portal to confirm your attendance and for inclusion in the official conference program.</p>
        </div>
    @endif
    
    @if($status === 'accepted')
        <div class="info-card" style="border-left: 6px solid #10b981; background: #f0fdf4;">
            <h3 style="color: #059669;">🎊 Congratulations!</h3>
            <p>We are pleased to inform you that your abstract has been <strong>accepted</strong> for presentation at {{ config('conference.short_name') }} {{ config('conference.year') }}.</p>
            
            <h4 style="color: #059669; font-size: 14px; margin: 16px 0 8px;">📅 Next Steps:</h4>
            <ul style="margin: 0; padding-left: 20px; color: #065f46;">
                <li style="margin-bottom: 6px;"><strong>Specifications:</strong> Final presentation formatting requirements and duration will be shared via email soon.</li>
                <li style="margin-bottom: 6px;"><strong>Schedule:</strong> Detailed session timing will be shared via the portal.</li>
                <li><strong>Registration:</strong> Please ensure your conference registration is complete.</li>
            </ul>
            
            @if($abstract->conference_code)
                <div style="background-color: white; padding: 16px; border-radius: 12px; margin: 20px 0; border: 1px dashed #10b981; text-align: center;">
                    <p style="margin: 0; font-size: 13px; color: #64748b; text-transform: uppercase; font-weight: 700;">Your Conference Code</p>
                    <p style="margin: 4px 0 0; font-size: 24px; color: #059669; font-weight: 800; letter-spacing: 2px;">{{ $abstract->conference_code }}</p>
                </div>
            @endif

            <div style="text-align: center; margin-top: 24px;">
                <a href="{{ route('dashboard') }}" class="cta-button" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">View Your Dashboard</a>
            </div>
        </div>
    @else
        <div class="info-card" style="border-left: 6px solid #ef4444; background: #fef2f2;">
            <h3 style="color: #dc2626;">Thank You for Your Submission</h3>
            <p style="color: #991b1b;">After careful consideration by our review panel, we regret to inform you that your abstract was not selected for presentation at this year's conference.</p>
            
            <p style="font-size: 14px; color: #b91c1c; margin-top: 12px;"><strong>This decision does not reflect the quality of your work.</strong> Due to the high volume of high-quality submissions and limited presentation slots, we had to make these difficult choices.</p>
            
            <div style="text-align: center; margin-top: 24px;">
                <a href="{{ route('dashboard') }}" class="cta-button" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">Portal Home</a>
            </div>
        </div>
    @endif
    
    @if($message)
        <div class="info-card" style="background: #f8fafc; border-left: 4px solid #94a3b8;">
            <h4 style="margin: 0 0 8px 0; font-size: 13px; color: #475569;">📧 Message from the Committee:</h4>
            <p style="margin: 0; font-style: italic; color: #64748b;">"{{ $message }}"</p>
        </div>
    @endif
    
    <p style="margin-top: 32px;">Thank you for your interest in {{ config('conference.short_name') }} {{ config('conference.year') }} and for contributing to the advancement of medical research.</p>
    
    <p>Best regards,<br>
    <strong>The Conference Review Committee</strong></p>
@endsection
