<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEOIndicator - SERP Feature Alert</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <!-- Header -->
        <tr>
            <td style="padding: 20px 0; text-align: center;">
                <h1 style="margin: 0; color: #18181b; font-size: 24px; font-weight: 700;">
                    SEOIndicator
                </h1>
            </td>
        </tr>

        <!-- Main Content -->
        <tr>
            <td style="background-color: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h2 style="margin: 0 0 20px; color: #18181b; font-size: 20px; font-weight: 600;">
                    Hello {{ $user->name ?? 'there' }},
                </h2>

                <p style="margin: 0 0 20px; color: #52525b; font-size: 16px; line-height: 1.5;">
                    {{ $body }}
                </p>

                <!-- Feature Card -->
                <table role="presentation" width="100%" style="background-color: #f4f4f5; border-radius: 8px; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 20px;">
                            <p style="margin: 0 0 10px; color: #71717a; font-size: 14px;">Keyword</p>
                            <p style="margin: 0 0 20px; color: #18181b; font-size: 18px; font-weight: 600;">{{ $keyword }}</p>

                            <!-- Feature Change -->
                            <table role="presentation" width="100%" style="margin-bottom: 15px; border-left: 3px solid {{ $action === 'gained' ? '#22c55e' : '#ef4444' }}; padding-left: 15px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0; color: #18181b; font-size: 16px; font-weight: 600;">
                                            @if($action === 'gained')
                                                🎉 Gained SERP Feature
                                            @else
                                                ⚠️ Lost SERP Feature
                                            @endif
                                        </p>
                                        <p style="margin: 5px 0 0; color: #71717a; font-size: 14px;">
                                            Feature: {{ $featureName }}
                                        </p>
                                        @if($ctrImpact)
                                            <p style="margin: 5px 0 0; color: #71717a; font-size: 14px;">
                                                CTR Impact: {{ $ctrImpact }}
                                            </p>
                                        @endif
                                        @if($url)
                                            <p style="margin: 5px 0 0; color: #71717a; font-size: 14px;">
                                                URL: <a href="{{ $url }}" style="color: #2563eb;">{{ Str::limit($url, 50) }}</a>
                                            </p>
                                        @endif
                                        @if($action === 'lost' && $previousUrl)
                                            <p style="margin: 5px 0 0; color: #71717a; font-size: 14px;">
                                                Previous URL: <a href="{{ $previousUrl }}" style="color: #2563eb;">{{ Str::limit($previousUrl, 50) }}</a>
                                            </p>
                                        @endif
                                        @if($detectedAt)
                                            <p style="margin: 5px 0 0; color: #71717a; font-size: 14px;">
                                                Detected: {{ \Carbon\Carbon::parse($detectedAt)->format('M j, Y g:i A') }} UTC
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                @if($action === 'lost')
                    <!-- Recommendations for lost features -->
                    <table role="presentation" width="100%" style="background-color: #fef3c7; border-radius: 8px; margin-bottom: 20px; border-left: 3px solid #f59e0b;">
                        <tr>
                            <td style="padding: 15px;">
                                <p style="margin: 0 0 10px; color: #92400e; font-size: 14px; font-weight: 600;">
                                    Recommended Actions:
                                </p>
                                <ul style="margin: 0; padding-left: 20px; color: #92400e; font-size: 14px;">
                                    <li>Check your page content for recent changes</li>
                                    <li>Review competitor activity for this keyword</li>
                                    <li>Ensure your structured data is properly implemented</li>
                                    <li>Verify the page is still accessible and indexed</li>
                                </ul>
                            </td>
                        </tr>
                    </table>
                @endif

                <!-- CTA Button -->
                <table role="presentation" width="100%">
                    <tr>
                        <td style="text-align: center; padding: 10px 0;">
                            <a href="{{ $dashboardUrl }}" style="display: inline-block; background-color: #18181b; color: #ffffff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-size: 16px; font-weight: 500;">
                                View in Dashboard
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding: 20px 0; text-align: center;">
                <p style="margin: 0 0 10px; color: #71717a; font-size: 14px;">
                    You received this because you enabled SERP feature alerts.
                </p>
                <p style="margin: 0; color: #71717a; font-size: 14px;">
                    <a href="{{ $unsubscribeUrl }}" style="color: #71717a; text-decoration: underline;">Unsubscribe from alerts</a>
                </p>
                <p style="margin: 20px 0 0; color: #a1a1aa; font-size: 12px;">
                    &copy; {{ date('Y') }} SEOIndicator. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
