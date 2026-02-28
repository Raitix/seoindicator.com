<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEOIndicator - Ranking Alert</title>
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
                    Your keyword ranking has changed:
                </p>

                <!-- Keyword Card -->
                <table role="presentation" width="100%" style="background-color: #f4f4f5; border-radius: 8px; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 20px;">
                            <p style="margin: 0 0 10px; color: #71717a; font-size: 14px;">Keyword</p>
                            <p style="margin: 0 0 20px; color: #18181b; font-size: 18px; font-weight: 600;">{{ $keyword->keyword }}</p>

                            @foreach($events as $event)
                                <table role="presentation" width="100%" style="margin-bottom: 15px; border-left: 3px solid {{ in_array($event['event'], ['position_gain', 'entered_top_10', 'entered_top_3', 'serp_feature_gained']) ? '#22c55e' : '#ef4444' }}; padding-left: 15px;">
                                    <tr>
                                        <td>
                                            @php
                                                $eventLabels = [
                                                    'position_drop' => 'Position Dropped',
                                                    'position_gain' => 'Position Improved',
                                                    'entered_top_10' => 'Entered Top 10',
                                                    'left_top_10' => 'Left Top 10',
                                                    'entered_top_3' => 'Entered Top 3',
                                                    'left_top_3' => 'Left Top 3',
                                                    'serp_feature_gained' => 'Gained SERP Feature',
                                                    'serp_feature_lost' => 'Lost SERP Feature',
                                                ];
                                                $label = $eventLabels[$event['event']] ?? $event['event'];
                                            @endphp
                                            <p style="margin: 0; color: #18181b; font-size: 16px; font-weight: 600;">
                                                {{ $label }}
                                            </p>
                                            @if(isset($event['old_position']) && isset($event['new_position']))
                                                <p style="margin: 5px 0 0; color: #71717a; font-size: 14px;">
                                                    Position {{ $event['old_position'] }} → {{ $event['new_position'] }}
                                                    @if(isset($event['change']))
                                                        ({{ $event['change'] }} positions)
                                                    @endif
                                                </p>
                                            @endif
                                            @if(isset($event['feature_type']))
                                                <p style="margin: 5px 0 0; color: #71717a; font-size: 14px;">
                                                    Feature: {{ ucfirst(str_replace('_', ' ', $event['feature_type'])) }}
                                                </p>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @endforeach
                        </td>
                    </tr>
                </table>

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
                    You received this because you enabled ranking alerts.
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
