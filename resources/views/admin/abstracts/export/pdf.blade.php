<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abstract Export - {{ $abstract->title }}</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #2563eb;
            font-size: 28px;
            margin: 0 0 10px 0;
            font-weight: bold;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 16px;
            margin: 0;
        }
        
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 10px 15px;
            margin: 0 0 15px 0;
            font-size: 18px;
            font-weight: bold;
            border-radius: 5px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            border: 1px solid #e5e7eb;
            padding: 10px;
            border-radius: 5px;
            background-color: #f9fafb;
        }
        
        .info-label {
            font-weight: bold;
            color: #374151;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #111827;
            font-size: 16px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-submitted { background-color: #dbeafe; color: #1e40af; }
        .status-under_review { background-color: #fef3c7; color: #d97706; }
        .status-accepted { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #dc2626; }
        .status-revision { background-color: #ede9fe; color: #7c3aed; }
        
        .description-box {
            border: 2px solid #e5e7eb;
            padding: 20px;
            border-radius: 8px;
            background-color: #f9fafb;
            line-height: 1.8;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #3b82f6, #8b5cf6);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #3b82f6;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #e5e7eb;
        }
        
        .timeline-date {
            color: #6b7280;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .timeline-action {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
            text-transform: capitalize;
        }
        
        .timeline-reviewer {
            color: #4b5563;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .timeline-score {
            display: inline-block;
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .timeline-comments {
            background: #f3f4f6;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #3b82f6;
            font-style: italic;
            color: #374151;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        
        .export-info {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .export-info h3 {
            color: #0369a1;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .export-details {
            font-size: 14px;
            color: #374151;
        }
        
        .no-data {
            text-align: center;
            color: #6b7280;
            font-style: italic;
            padding: 20px;
        }
        
        /* Print styles */
        @media print {
            body {
                margin: 0;
                padding: 15px;
                font-size: 12px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .section-title {
                font-size: 16px;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            .timeline-item {
                page-break-inside: avoid;
                margin-bottom: 15px;
            }
            
            .section {
                margin-bottom: 20px;
            }
        }
        
        /* Page break utilities */
        .page-break {
            page-break-before: always;
        }
        
        .no-break {
            page-break-inside: avoid;
        }
        
        /* Responsive adjustments */
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .timeline {
                padding-left: 20px;
            }
            
            .timeline::before {
                left: 10px;
            }
            
            .timeline-item::before {
                left: -16px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Abstract Submission Report</h1>
        <p class="subtitle">Conference Management System</p>
    </div>

    <!-- Export Information -->
    <div class="export-info no-break">
        <h3>Export Information</h3>
        <div class="export-details">
            <strong>Generated:</strong> {{ $exportDate->format('F d, Y \a\t h:i A') }}<br>
            <strong>Exported by:</strong> {{ $exportedBy->name }} ({{ $exportedBy->email }})<br>
            <strong>Abstract ID:</strong> #{{ $abstract->id }}
        </div>
    </div>

    <!-- Basic Information -->
    <div class="section no-break">
        <h2 class="section-title">Basic Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Abstract ID</div>
                <div class="info-value">#{{ $abstract->id }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Current Status</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $abstract->status }}">
                        {{ ucfirst(str_replace('_', ' ', $abstract->status)) }}
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Author Name</div>
                <div class="info-value">{{ $abstract->author_name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Author Institute</div>
                <div class="info-value">{{ $abstract->author_institute }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Submitter</div>
                <div class="info-value">{{ $abstract->user->name }} ({{ $abstract->user->email }})</div>
            </div>
            <div class="info-item">
                <div class="info-label">Subtheme</div>
                <div class="info-value">{{ $abstract->subtheme }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Submission Date</div>
                <div class="info-value">{{ $abstract->created_at->format('F d, Y \a\t h:i A') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Last Updated</div>
                <div class="info-value">{{ $abstract->updated_at->format('F d, Y \a\t h:i A') }}</div>
            </div>
        </div>
    </div>

    <!-- Title and Description -->
    <div class="section">
        <h2 class="section-title">Abstract Content</h2>
        <div style="margin-bottom: 20px;">
            <div class="info-label" style="margin-bottom: 10px;">Title:</div>
            <h3 style="margin: 0; color: #1f2937; font-size: 20px; line-height: 1.4;">{{ $abstract->title }}</h3>
        </div>
        <div>
            <div class="info-label" style="margin-bottom: 10px;">Description:</div>
            <div class="description-box">{!! $abstract->description !!}</div>
        </div>
    </div>

    <!-- Reviewer Information -->
    @if($abstract->reviewer1_id || $abstract->reviewer2_id)
    <div class="section no-break">
        <h2 class="section-title">Reviewer Assignment</h2>
        <div class="info-grid">
            @if($abstract->reviewer1)
            <div class="info-item">
                <div class="info-label">Reviewer 1</div>
                <div class="info-value">{{ $abstract->reviewer1->name }}</div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">{{ $abstract->reviewer1->email }}</div>
            </div>
            @endif
            @if($abstract->reviewer2)
            <div class="info-item">
                <div class="info-label">Reviewer 2</div>
                <div class="info-value">{{ $abstract->reviewer2->name }}</div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">{{ $abstract->reviewer2->email }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Review History -->
    <div class="section">
        <h2 class="section-title">Review History</h2>
        @if($reviewHistory->count() > 0)
            <div class="timeline">
                @foreach($reviewHistory as $history)
                <div class="timeline-item">
                    <div class="timeline-date">{{ $history->action_date->format('F d, Y \a\t h:i A') }}</div>
                    <div class="timeline-action">{{ str_replace('_', ' ', $history->action) }}</div>
                    
                    @if($history->reviewer)
                    <div class="timeline-reviewer">
                        <strong>Reviewer:</strong> {{ $history->reviewer->name }}
                        @if($history->reviewer_position)
                            ({{ $history->reviewer_position }})
                        @endif
                    </div>
                    @endif
                    
                    @if($history->score)
                    <div>
                        <span class="timeline-score">Score: {{ number_format($history->score, 1) }}/10</span>
                    </div>
                    @endif
                    
                    @if($history->comments)
                    <div class="timeline-comments">
                        <strong>Comments:</strong> {{ $history->comments }}
                    </div>
                    @endif
                    
                    @if($history->reason)
                    <div style="margin-top: 8px; font-size: 14px; color: #4b5563;">
                        <strong>Reason:</strong> {{ $history->reason }}
                    </div>
                    @endif
                    
                    @if($history->admin)
                    <div style="margin-top: 8px; font-size: 12px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px;">
                        <strong>Admin Action:</strong> Performed by {{ $history->admin->name }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        @else
            <div class="no-data">
                No review history available for this abstract.
            </div>
        @endif
    </div>

    <!-- Summary Statistics -->
    @if($reviewHistory->count() > 0)
    <div class="section no-break">
        <h2 class="section-title">Summary Statistics</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Total Events</div>
                <div class="info-value">{{ $reviewHistory->count() }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Review Submissions</div>
                <div class="info-value">{{ $reviewHistory->where('action', 'review_submitted')->count() }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Review Updates</div>
                <div class="info-value">{{ $reviewHistory->where('action', 'review_updated')->count() }}</div>
            </div>
            @php
                $averageScore = $reviewHistory->whereNotNull('score')->avg('score');
            @endphp
            @if($averageScore)
            <div class="info-item">
                <div class="info-label">Average Score</div>
                <div class="info-value">{{ number_format($averageScore, 1) }}/10</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="footer">
        <p>This report was generated automatically by the Conference Management System.</p>
        <p>For questions or concerns, please contact the conference administrators.</p>
        <p style="margin-top: 10px;">
            <strong>Generated:</strong> {{ $exportDate->format('F d, Y \a\t h:i:s A T') }}
        </p>
    </div>

    <script>
        // Auto-print functionality
        window.onload = function() {
            // Add a small delay to ensure content is fully loaded
            setTimeout(function() {
                // Check if this is being opened for print
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('print') === 'true') {
                    window.print();
                }
            }, 1000);
        };
    </script>
</body>
</html>
