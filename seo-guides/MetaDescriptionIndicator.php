<?php
namespace SEOIndicator;

use SEOIndicator\Indicator;

class MetaDescriptionIndicator extends Indicator {
    public function __construct() {
        parent::__construct('Meta Description Indicator');
    }

    public function analyze(array $data): array {
        $metaDescription = $data['meta_description'] ?? '';
        $length = strlen($metaDescription);
        
        $score = ($length >= 120 && $length <= 160) ? 3 : 2;

        return [
            'score' => $score,
            'details' => htmlspecialchars(substr($metaDescription, 0, 100)),
            'suggestion' => 'Optimize the meta description to 120-160 characters'
        ];
    }
}
