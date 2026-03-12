<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEOIndicator - Content Health Report</title>
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
                <h2 style="margin: 0 0 10px; color: #18181b; font-size: 20px; font-weight: 600;">
                    Hello {{ $user->name ?? 'there' }},
                </h2>

                <p style="margin: 0 0 20px; color: #52525b; font-size: 16px; line-height: 1.5;">
                    Here's your {{ $digest['frequency'] === 'monthly' ? 'monthly' : 'weekly' }} content health report for <strong>{{ $siteName }}</strong>:
                </p>

                <!-- Summary Stats -->
                <table role="presentation" width="100%" style="background-color: #f4f4f5; border-radius: 8px; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 20px;">
                            <p style="margin: 0 0 15px; color: #71717a; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                Content Health Summary
                            </p>
                            
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="width: 33%; text-align: center; padding: 10px;">
                                        <p style="margin: 0; color: #ef4444; font-size: 28px; font-weight: 700;">
                                            {{ $digest['summary']['total_new'] ?? 0 }}
                                        </p>
                                        <p style="margin: 5px 0 0; color: #71717a; font-size: 12px;">
                                            Pages Need Attention
                                        </p>
                                    </td>
                                    <td style="width: 33%; text-align: center; padding: 10px;">
                                        <p style="margin: 0; color: #f97316; font-size: 28px; font-weight: 700;">
                                            {{ $digest['summary']['critical_count'] ?? 0 }}
                                        </p>
                                        <p style="margin: 5px 0 0; color: #71717a; font-size: 12px;">
                                            Critical Issues
                                        </p>
                                    </td>
                                    <td style="width: 33%; text-align: center; padding: 10px;">
                                        <p style="margin: 0; color: #22c55e; font-size: 28px; font-weight: 700;">
                                            {{ $digest['summary']['total_recovered'] ?? 0 }}
                                        </p>
                                        <p style="margin: 5px 0 0; color: #71717a; font-size: 12px;">
                                            Pages Recovered
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            @if(($digest['summary']['total_lost_clicks'] ?? 0) > 0)
                                <p style="margin: 15px 0 0; color: #52525b; font-size: 14px; text-align: center;">
                                    <strong>{{ number_format($digest['summary']['total_lost_clicks']) }}</strong> estimated clicks lost
                                    @if(($digest['summary']['estimated_upside_clicks'] ?? 0) > 0)
                                        · <strong>{{ number_format($digest['summary']['estimated_upside_clicks']) }}</strong> recovery potential
                                    @endif
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>

                @if(!empty($digest['top_candidates']))
                    <!-- Top Priority Pages -->
                    <h3 style="margin: 25px 0 15px; color: #18181b; font-size: 16px; font-weight: 600;">
                        🔴 Top Priority Pages
                    </h3>

                    @foreach($digest['top_candidates'] as $index => $candidate)
                        <table role="presentation" width="100%" style="background-color: #fef2f2; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid {{ $candidate['severity'] === 'critical' ? '#ef4444' : ($candidate['severity'] === 'high' ? '#f97316' : '#eab308') }};">
                            <tr>
                                <td style="padding: 15px;">
                                    <p style="margin: 0 0 5px; color: #18181b; font-size: 14px; font-weight: 600; word-break: break-all;">
                                        {{ Str::limit($candidate['page_title'] ?? 'Untitled', 60) }}
                                    </p>
                                    <p style="margin: 0 0 10px; color: #71717a; font-size: 12px; word-break: break-all;">
                                        {{ Str::limit($candidate['page_url'], 80) }}
                                    </p>
                                    <table role="presentation" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="padding-right: 15px;">
                                                <span style="display: inline-block; background-color: {{ $candidate['severity'] === 'critical' ? '#fef2f2' : ($candidate['severity'] === 'high' ? '#fff7ed' : '#fefce8') }}; color: {{ $candidate['severity'] === 'critical' ? '#dc2626' : ($candidate['severity'] === 'high' ? '#ea580c' : '#ca8a04') }}; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                                    {{ ucfirst($candidate['severity']) }}
                                                </span>
                                            </td>
                                            <td style="color: #52525b; font-size: 12px;">
                                                Score: {{ $candidate['decay_score'] }}
                                            </td>
                                            @if(($candidate['lost_clicks'] ?? 0) > 0)
                                                <td style="padding-left: 15px; color: #52525b; font-size: 12px;">
                                                    -{{ number_format($candidate['lost_clicks']) }} clicks
                                                </td>
                                            @endif
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    @endforeach
                @endif

                @if(!empty($digest['recovered_candidates']))
                    <!-- Recovered Pages -->
                    <h3 style="margin: 25px 0 15px; color: #18181b; font-size: 16px; font-weight: 600;">
                        🟢 Recovered Pages
                    </h3>

                    @foreach($digest['recovered_candidates'] as $candidate)
                        <table role="presentation" width="100%" style="background-color: #f0fdf4; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid #22c55e;">
                            <tr>
                                <td style="padding: 15px;">
                                    <p style="margin: 0 0 5px; color: #18181b; font-size: 14px; font-weight: 600; word-break: break-all;">
                                        {{ Str::limit($candidate['page_title'] ?? 'Untitled', 60) }}
                                    </p>
                                    <p style="margin: 0 0 5px; color: #71717a; font-size: 12px; word-break: break-all;">
                                        {{ Str::limit($candidate['page_url'], 80) }}
                                    </p>
                                    @if(($candidate['recovery_upside'] ?? 0) > 0)
                                        <p style="margin: 0; color: #16a34a; font-size: 12px;">
                                            +{{ number_format($candidate['recovery_upside']) }} clicks recovered
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    @endforeach
                @endif

                @if(!empty($digest['actionable_recommendations']))
                    <!-- Recommendations -->
                    <h3 style="margin: 25px 0 15px; color: #18181b; font-size: 16px; font-weight: 600;">
                        💡 Recommendations
                    </h3>

                    <ul style="margin: 0; padding-left: 20px; color: #52525b; font-size: 14px; line-height: 1.8;">
                        @foreach($digest['actionable_recommendations'] as $recommendation)
                            <li>{{ $recommendation }}</li>
                        @endforeach
                    </ul>
                @endif

                @if(empty($digest['top_candidates']) && empty($digest['recovered_candidates']))
                    <!-- No Changes -->
                    <table role="presentation" width="100%" style="background-color: #f0fdf4; border-radius: 8px; margin-bottom: 20px;">
                        <tr>
                            <td style="padding: 30px; text-align: center;">
                                <p style="margin: 0; color: #16a34a; font-size: 18px; font-weight: 600;">
                                    ✅ All Clear!
                                </p>
                                <p style="margin: 10px 0 0; color: #52525b; font-size: 14px;">
                                    No content decay detected this period. Your pages are performing well.
                                </p>
                            </td>
                        </tr>
                    </table>
                @endif

                <!-- CTA Button -->
                <table role="presentation" width="100%">
                    <tr>
                        <td style="text-align: center; padding: 20px 0 10px;">
                            <a href="{{ $dashboardUrl }}" style="display: inline-block; background-color: #18181b; color: #ffffff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-size: 16px; font-weight: 500;">
                                View Refresh Queue →
                            </a>
                        </td>
                    </tr>
                </table>

                <!-- Period Info -->
                <p style="margin: 15px 0 0; color: #a1a1aa; font-size: 12px; text-align: center;">
                    Report period: {{ $digest['period_start'] ?? '' }} to {{ $digest['period_end'] ?? '' }}
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding: 20px 0; text-align: center;">
                <p style="margin: 0 0 10px; color: #71717a; font-size: 14px;">
                    You received this because you enabled content decay digests.
                </p>
                <p style="margin: 0; color: #71717a; font-size: 14px;">
                    <a href="{{ $unsubscribeUrl }}" style="color: #71717a; text-decoration: underline;">Unsubscribe from digests</a>
                </p>
                <p style="margin: 20px 0 0; color: #a1a1aa; font-size: 12px;">
                    &copy; {{ date('Y') }} SEOIndicator. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
