<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $reportName }} is Ready</title>
    
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    
    <style>
        /* Reset styles */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        
        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            height: 100% !important;
        }
        
        /* Base styles */
        body, table, td, p, a, li {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #1a1a1a;
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .email-body {
                background-color: #0a0a0a !important;
            }
            .email-container {
                background-color: #1a1a1a !important;
                border-color: #333333 !important;
            }
            .email-header {
                background-color: #2563eb !important;
            }
            .email-content {
                background-color: #1a1a1a !important;
            }
            h1, h2, h3, p, li {
                color: #f5f5f5 !important;
            }
            .metric-label {
                color: #a0a0a0 !important;
            }
            .metric-value {
                color: #ffffff !important;
            }
            .footer {
                background-color: #1a1a1a !important;
                border-top-color: #333333 !important;
            }
            .footer-text, .footer-link {
                color: #a0a0a0 !important;
            }
        }
        
        /* Responsive styles */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            
            .content-padding {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }
            
            .metric-box {
                display: block !important;
                width: 100% !important;
                margin-bottom: 12px !important;
            }
            
            .button {
                width: 100% !important;
                display: block !important;
            }
            
            .button-secondary {
                margin-top: 12px !important;
                margin-left: 0 !important;
            }
        }
        
        /* Component styles */
        .email-body {
            background-color: #f5f5f5;
            padding: 20px 0;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e5e5e5;
        }
        
        .email-header {
            background-color: #2563eb;
            padding: 32px 40px;
            text-align: center;
        }
        
        .email-header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 8px 0;
        }
        
        .email-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin: 0;
        }
        
        .email-content {
            padding: 32px 40px;
            background-color: #ffffff;
        }
        
        .greeting {
            font-size: 18px;
            margin: 0 0 24px 0;
            color: #1a1a1a;
        }
        
        .report-info {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
            border-left: 4px solid #2563eb;
        }
        
        .report-info-item {
            margin-bottom: 8px;
        }
        
        .report-info-item:last-child {
            margin-bottom: 0;
        }
        
        .report-info-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-info-value {
            font-size: 16px;
            color: #1a1a1a;
            font-weight: 500;
        }
        
        .metrics-section {
            margin-bottom: 28px;
        }
        
        .metrics-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 16px 0;
        }
        
        .metrics-grid {
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
        }
        
        .metric-box {
            display: table-cell;
            width: 33.33%;
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            vertical-align: top;
        }
        
        .metric-label {
            font-size: 12px;
            color: #6b7280;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0;
        }
        
        .metric-change {
            font-size: 12px;
            margin: 4px 0 0 0;
        }
        
        .metric-change.positive {
            color: #16a34a;
        }
        
        .metric-change.negative {
            color: #dc2626;
        }
        
        .metric-change.neutral {
            color: #6b7280;
        }
        
        .actions {
            text-align: center;
            margin: 28px 0;
        }
        
        .button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            mso-padding-alt: 0;
        }
        
        .button:hover {
            background-color: #1d4ed8;
        }
        
        .button-secondary {
            display: inline-block;
            padding: 14px 32px;
            background-color: #f8fafc;
            color: #2563eb;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            border: 1px solid #e5e7eb;
            margin-left: 12px;
        }
        
        .button-secondary:hover {
            background-color: #f1f5f9;
        }
        
        .divider {
            border-top: 1px solid #e5e7eb;
            margin: 28px 0;
        }
        
        .footer {
            background-color: #f8fafc;
            padding: 24px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-text {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 12px 0;
        }
        
        .footer-link {
            color: #2563eb;
            text-decoration: none;
        }
        
        .footer-link:hover {
            text-decoration: underline;
        }
        
        .unsubscribe {
            font-size: 12px;
            color: #9ca3af;
            margin: 12px 0 0 0;
        }
        
        .unsubscribe a {
            color: #9ca3af;
            text-decoration: underline;
        }
    </style>
</head>
<body class="email-body" style="margin: 0; padding: 0; background-color: #f5f5f5;">
    
    <!-- Preview text (hidden) -->
    <div style="display: none; max-height: 0; overflow: hidden;">
        Your {{ $reportName }} for {{ $siteName }} is ready for download.
    </div>
    
    <!-- Main container -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5;">
        <tr>
            <td style="padding: 20px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="email-container" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e5e5;">
                    
                    <!-- Header -->
                    <tr>
                        <td class="email-header" style="background-color: #2563eb; padding: 32px 40px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 24px; font-weight: 600; margin: 0 0 8px 0;">{{ $reportName }}</h1>
                            <p style="color: rgba(255, 255, 255, 0.9); font-size: 14px; margin: 0;">Your SEO report is ready</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td class="email-content content-padding" style="padding: 32px 40px; background-color: #ffffff;">
                            
                            <!-- Greeting -->
                            <p class="greeting" style="font-size: 18px; margin: 0 0 24px 0; color: #1a1a1a;">
                                Hello,
                            </p>
                            
                            <p style="margin: 0 0 24px 0; color: #1a1a1a;">
                                Your <strong>{{ $reportName }}</strong> for <strong>{{ $siteName }}</strong> has been generated and is ready for download.
                            </p>
                            
                            <!-- Report Info Box -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8fafc; border-radius: 8px; margin-bottom: 24px; border-left: 4px solid #2563eb;">
                                <tr>
                                    <td style="padding: 20px;">
                                        @if(isset($dateRangeStart) && isset($dateRangeEnd))
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding-bottom: 8px;">
                                                    <p class="report-info-label" style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">Report Period</p>
                                                    <p class="report-info-value" style="font-size: 16px; color: #1a1a1a; font-weight: 500; margin: 4px 0 0 0;">{{ $dateRangeStart }} - {{ $dateRangeEnd }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                        @endif
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td>
                                                    <p class="report-info-label" style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">Site</p>
                                                    <p class="report-info-value" style="font-size: 16px; color: #1a1a1a; font-weight: 500; margin: 4px 0 0 0;">
                                                        {{ $siteName }}
                                                        @if(isset($siteUrl))
                                                        <br><span style="font-size: 14px; font-weight: 400; color: #6b7280;">{{ $siteUrl }}</span>
                                                        @endif
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            @if(!empty($metrics))
                            <!-- Metrics Preview -->
                            <div class="metrics-section">
                                <p class="metrics-title" style="font-size: 16px; font-weight: 600; color: #1a1a1a; margin: 0 0 16px 0;">Key Metrics Preview</p>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" class="metrics-grid">
                                    <tr>
                                        @foreach($metrics as $index => $metric)
                                        <td class="metric-box" style="width: 33.33%; background-color: #f8fafc; border-radius: 8px; padding: 16px; text-align: center; vertical-align: top; {{ $loop->last ? '' : 'padding-right: 8px;' }}">
                                            <p class="metric-label" style="font-size: 12px; color: #6b7280; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 0.5px;">{{ $metric['label'] ?? 'Metric' }}</p>
                                            <p class="metric-value" style="font-size: 24px; font-weight: 600; color: #1a1a1a; margin: 0;">{{ $metric['value'] ?? '-' }}</p>
                                            @if(isset($metric['change']))
                                            @php
                                                $changeClass = ($metric['isPositive'] ?? false) ? 'positive' : ((isset($metric['isNegative']) && $metric['isNegative']) ? 'negative' : 'neutral');
                                            @endphp
                                            <p class="metric-change {{ $changeClass }}" style="font-size: 12px; margin: 4px 0 0 0; color: {{ $changeClass === 'positive' ? '#16a34a' : ($changeClass === 'negative' ? '#dc2626' : '#6b7280') }};">
                                                {{ $metric['change'] }}
                                            </p>
                                            @endif
                                        </td>
                                        @endforeach
                                    </tr>
                                </table>
                            </div>
                            @endif
                            
                            <!-- Action Buttons -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" class="actions" style="text-align: center; margin: 28px 0;">
                                <tr>
                                    <td style="text-align: center;">
                                        <a href="{{ $downloadUrl }}" class="button" style="display: inline-block; padding: 14px 32px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
                                            Download {{ strtoupper($reportFormat ?? 'PDF') }}
                                        </a>
                                        @if(isset($previewUrl))
                                        <a href="{{ $previewUrl }}" class="button-secondary" style="display: inline-block; padding: 14px 32px; background-color: #f8fafc; color: #2563eb; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; border: 1px solid #e5e7eb; margin-left: 12px;">
                                            View in Dashboard
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Divider -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td class="divider" style="border-top: 1px solid #e5e7eb; margin: 28px 0;"></td>
                                </tr>
                            </table>
                            
                            <p style="font-size: 14px; color: #6b7280; margin: 0;">
                                This report was generated on {{ \Carbon\Carbon::parse($generatedAt ?? now())->format('F j, Y \a\t g:i A') }}.
                            </p>
                            
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td class="footer" style="background-color: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p class="footer-text" style="font-size: 14px; color: #6b7280; margin: 0 0 12px 0;">
                                SEOIndicator — Track your SEO performance
                            </p>
                            <p class="footer-text" style="font-size: 14px; color: #6b7280; margin: 0;">
                                <a href="{{ config('app.url') }}" class="footer-link" style="color: #2563eb; text-decoration: none;">Visit Dashboard</a>
                            </p>
                            <p class="unsubscribe" style="font-size: 12px; color: #9ca3af; margin: 12px 0 0 0;">
                                You're receiving this email because you're subscribed to report notifications.
                                <br>
                                <a href="{{ $unsubscribeUrl }}" style="color: #9ca3af; text-decoration: underline;">Unsubscribe from this report</a>
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
    
</body>
</html>
