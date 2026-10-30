{{ $reportName }} is Ready
========================================

Hello,

Your {{ $reportName }} for {{ $siteName }} has been generated and is ready for download.

REPORT DETAILS
----------------------------------------
Report Name: {{ $reportName }}
Site: {{ $siteName }}
@if(isset($dateRangeStart) && isset($dateRangeEnd))
Report Period: {{ $dateRangeStart }} - {{ $dateRangeEnd }}
@endif
Format: {{ strtoupper($reportFormat ?? 'PDF') }}

@if(!empty($metrics))
KEY METRICS PREVIEW
----------------------------------------
@foreach($metrics as $metric)
{{ $metric['label'] ?? 'Metric' }}: {{ $metric['value'] ?? '-' }}@if(isset($metric['change'])) ({{ $metric['change'] }})@endif

@endforeach
@endif

DOWNLOAD YOUR REPORT
----------------------------------------
Click the link below to download your report:
{{ $downloadUrl }}

@if(isset($previewUrl))
Or view it in your dashboard:
{{ $previewUrl }}
@endif

----------------------------------------
This report was generated on {{ \Carbon\Carbon::parse($generatedAt ?? now())->format('F j, Y \a\t g:i A') }}.

---
SEOIndicator - Track your SEO performance
{{ config('app.url') }}

You're receiving this email because you're subscribed to report notifications.
To unsubscribe from this report, visit: {{ $unsubscribeUrl }}
