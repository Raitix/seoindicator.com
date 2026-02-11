<?php
namespace SEOIndicator;

use SEOIndicator\Indicator;

class UrlIndicator extends Indicator {
    public function __construct() {
        parent::__construct('URL Indicator');
    }

    public function analyze(array $data): array {
        $urlData = $data['url'] ?? null;
        if (!$urlData) {
            return ['score' => 1, 'details' => 'No URL data', 'suggestion' => 'Provide valid URL data'];
        }

        $https = $urlData['https'] ? 1 : 0;
        $domainLength = $urlData['domain_length'] <= 15 ? 1 : 0;
        $score = $https + $domainLength + 1;

        return [
            'score' => min(3, $score),
            'details' => $urlData['url'],
            'suggestion' => $https ? 'Good URL' : 'Use HTTPS'
        ];
    }
}
