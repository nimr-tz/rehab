<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('conference.short_name') }} {{ config('conference.year') }}</title>
    <!-- Modern Web Fonts (Supported in many modern clients, fallbacks included) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Base Reset */
        body {
            font-family: 'Plus Jakarta Sans', 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            padding: 40px 16px;
            background-color: #f8fafc;
        }
        .email-container {
            background-color: white;
            border-radius: 20px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.08); /* Deep soft shadow */
            overflow: hidden;
            border: 1px solid #f1f5f9;
        }
        /* Gradient Header */
        .email-header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 50%, #1e3a8a 100%);
            color: white;
            padding: 48px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .email-header::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }
        .email-header img {
            width: 80px;
            height: auto;
            margin-bottom: 16px;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.25));
        }
        .email-header h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.02em;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            color: #ffffff;
            line-height: 1.2;
            text-transform: uppercase;
        }
        /* Content Area */
        .email-content {
            padding: 40px 32px;
            color: #334155;
            font-size: 16px;
        }
        .message-intro {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 24px;
        }
        /* Highlight Info Card */
        .info-card {
            background-color: #ffffff;
            padding: 24px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            margin: 32px 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
        }
        .info-card h3 {
            margin: 0 0 16px 0;
            color: #1e40af;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            display: flex;
            align-items: center;
        }
        /* Button */
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white !important;
            padding: 16px 40px;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
            transition: all 0.3s ease;
            text-align: center;
            margin: 8px 0;
        }
        /* Table styles */
        .details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .details-table th, .details-table td {
            padding: 14px 0;
            text-align: left;
            border-bottom: 1px dotted #e2e8f0;
            font-size: 15px;
        }
        .details-table th {
            color: #64748b;
            font-weight: 600;
            width: 35%;
        }
        .details-table td {
            color: #1e293b;
            font-weight: 700;
        }
        .details-table tr:last-child th, .details-table tr:last-child td {
            border-bottom: none;
        }
        /* Footer */
        .email-footer {
            background-color: #f8fafc;
            padding: 40px 30px;
            text-align: center;
            border-top: 1px solid #f1f5f9;
        }
        .footer-logo {
            width: 50px;
            height: auto;
            opacity: 0.6;
            margin-bottom: 20px;
        }
        .footer-text {
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.8;
            margin: 0;
        }
        .social-line {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #f1f5f9;
        }
        /* Dark Theme Support (Native) */
        @media (prefers-color-scheme: dark) {
            /* Basic dark overrides if client supports it */
            /* Note: Many email clients don't support dark mode media queries well */
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <img src="{{ asset(config('conference.logo_mark_path')) }}" alt="{{ config('conference.host') }}">
                <h1>@yield('title', config('conference.short_name') . ' ' . config('conference.year'))</h1>
            </div>

            <!-- Content -->
            <div class="email-content">
                @yield('content')
            </div>
            
            <!-- Footer -->
            <div class="email-footer">
                <p class="footer-text">
                    <strong>{{ config('conference.name') }} ({{ config('conference.short_name') }} {{ config('conference.year') }})</strong><br>
                    {{ config('conference.host') }}<br>
                    <em>Transforming Healthcare Through Innovation</em>
                </p>
                
                <div class="social-line">
                    <p class="footer-text" style="color: #cbd5e1; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">
                        &copy; {{ date('Y') }} {{ config('conference.short_name') }}. This is an automated notification.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
