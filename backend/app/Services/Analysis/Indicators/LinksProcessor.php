<?php

namespace App\Services\Analysis\Indicators;

use RuntimeException;

/**
 * Links Indicator Processor.
 *
 * Analyzes internal and external links for SEO best practices.
 */
class LinksProcessor extends IndicatorProcessor
{
    public function slug(): string
    {
        return 'links';
    }

    public function name(): string
    {
        return 'Links Analysis';
    }

    public function maxScore(): float
    {
        return 100.0;
    }

    /**
     * Maximum links to check for broken status (to limit HTTP requests).
     */
    protected int $maxLinksToCheck = 20;

    public function process(string $url): IndicatorResult
    {
        $startTime = microtime(true);

        try {
            $html = $this->fetchUrl($url);
            $crawler = $this->parseHtml($html);

            $score = 0.0;
            $maxScore = $this->maxScore();
            $data = [];
            $recommendations = [];

            // Parse base URL for internal/external detection
            $baseUrl = parse_url($url);
            $baseHost = $baseUrl['host'] ?? '';

            // Extract all links
            $links = $this->extractLinks($crawler, $baseHost);
            $data['total_count'] = count($links['internal']) + count($links['external']);

            // Analyze internal links
            $internalResult = $this->analyzeInternalLinks($links['internal']);
            $score += $internalResult['score'];
            $data['internal'] = $internalResult['data'];
            $recommendations = array_merge($recommendations, $internalResult['recommendations']);

            // Analyze external links
            $externalResult = $this->analyzeExternalLinks($links['external']);
            $score += $externalResult['score'];
            $data['external'] = $externalResult['data'];
            $recommendations = array_merge($recommendations, $externalResult['recommendations']);

            // Analyze link text quality
            $textResult = $this->analyzeLinkText($links);
            $score += $textResult['score'];
            $data['link_text_analysis'] = $textResult['data'];
            $recommendations = array_merge($recommendations, $textResult['recommendations']);

            // Summary
            $data['summary'] = [
                'internal_count' => count($links['internal']),
                'external_count' => count($links['external']),
                'nofollow_count' => $data['external']['nofollow_count'] ?? 0,
                'broken_count' => 0, // Would require HTTP requests
            ];

            $processingTime = $this->calculateProcessingTime($startTime);

            return IndicatorResult::success(
                slug: $this->slug(),
                name: $this->name(),
                score: min($score, $maxScore),
                maxScore: $maxScore,
                data: $data,
                recommendations: $recommendations,
                processingTimeMs: $processingTime,
            );

        } catch (RuntimeException $e) {
            $processingTime = $this->calculateProcessingTime($startTime);

            return IndicatorResult::failed(
                slug: $this->slug(),
                name: $this->name(),
                maxScore: $this->maxScore(),
                error: $e->getMessage(),
                processingTimeMs: $processingTime,
            );
        }
    }

    /**
     * Extract all links and categorize as internal/external.
     */
    protected function extractLinks($crawler, string $baseHost): array
    {
        $links = [
            'internal' => [],
            'external' => [],
        ];

        $crawler->filter('a[href]')->each(function ($node) use ($baseHost, &$links) {
            $href = $node->attr('href');
            $text = trim($node->text());
            $rel = $node->attr('rel');
            $target = $node->attr('target');

            // Skip anchors, javascript, and mailto links
            if (!$href || str_starts_with($href, '#') || 
                str_starts_with($href, 'javascript:') || 
                str_starts_with($href, 'mailto:')) {
                return;
            }

            $parsedUrl = parse_url($href);
            $isExternal = false;

            if (isset($parsedUrl['host'])) {
                $isExternal = strtolower($parsedUrl['host']) !== strtolower($baseHost);
            } elseif (str_starts_with($href, '//')) {
                // Protocol-relative URL - treat as external for safety
                $isExternal = true;
            }

            $linkData = [
                'href' => $href,
                'text' => $text,
                'text_length' => strlen($text),
                'rel' => $rel,
                'target' => $target,
                'has_nofollow' => $rel !== null && str_contains(strtolower($rel), 'nofollow'),
                'is_sponsored' => $rel !== null && str_contains(strtolower($rel), 'sponsored'),
                'is_ugc' => $rel !== null && str_contains(strtolower($rel), 'ugc'),
                'opens_new_tab' => $target === '_blank',
            ];

            if ($isExternal) {
                $links['external'][] = $linkData;
            } else {
                $links['internal'][] = $linkData;
            }
        });

        return $links;
    }

    /**
     * Analyze internal links.
     */
    protected function analyzeInternalLinks(array $links): array
    {
        $score = 0.0;
        $data = [];
        $recommendations = [];

        $count = count($links);
        $data['count'] = $count;

        // Score for having internal links
        if ($count === 0) {
            $recommendations[] = $this->recommendation(
                'medium',
                'No internal links found',
                'Add internal links to help users and search engines navigate your site.'
            );
        } elseif ($count < 3) {
            $score += 15;
            $recommendations[] = $this->recommendation(
                'low',
                'Few internal links',
                "Only {$count} internal links found. Consider adding more internal links to improve navigation and link equity distribution."
            );
        } elseif ($count <= 100) {
            $score += 30; // Good amount
        } else {
            $score += 25;
            // Too many links can dilute link equity
            $recommendations[] = $this->recommendation(
                'low',
                'Many internal links',
                "Found {$count} internal links. Ensure links are relevant and consider reducing if too many."
            );
        }

        // Check for links with descriptive text
        $goodTextLinks = 0;
        $emptyTextLinks = 0;
        $genericTextLinks = 0;
        $genericTexts = ['click here', 'read more', 'more', 'learn more', 'here', 'link'];

        foreach ($links as $link) {
            if (empty($link['text'])) {
                $emptyTextLinks++;
            } elseif (in_array(strtolower($link['text']), $genericTexts)) {
                $genericTextLinks++;
            } else {
                $goodTextLinks++;
            }
        }

        $data['descriptive_text_count'] = $goodTextLinks;
        $data['empty_text_count'] = $emptyTextLinks;
        $data['generic_text_count'] = $genericTextLinks;

        if ($genericTextLinks > 0) {
            $recommendations[] = $this->recommendation(
                'low',
                'Some links use generic text',
                "{$genericTextLinks} links use generic text like 'click here'. Use descriptive anchor text for better SEO."
            );
        }

        $score += min($goodTextLinks * 0.5, 20);

        return [
            'score' => $score,
            'data' => $data,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Analyze external links.
     */
    protected function analyzeExternalLinks(array $links): array
    {
        $score = 0.0;
        $data = [];
        $recommendations = [];

        $count = count($links);
        $data['count'] = $count;

        $nofollowCount = 0;
        $sponsoredCount = 0;
        $ugcCount = 0;
        $openNewTabCount = 0;
        $withoutRelCount = 0;

        foreach ($links as $link) {
            if ($link['has_nofollow']) $nofollowCount++;
            if ($link['is_sponsored']) $sponsoredCount++;
            if ($link['is_ugc']) $ugcCount++;
            if ($link['opens_new_tab']) $openNewTabCount++;
            if ($link['rel'] === null) $withoutRelCount++;
        }

        $data['nofollow_count'] = $nofollowCount;
        $data['sponsored_count'] = $sponsoredCount;
        $data['ugc_count'] = $ugcCount;
        $data['opens_new_tab_count'] = $openNewTabCount;
        $data['without_rel_count'] = $withoutRelCount;

        // Score calculation
        if ($count === 0) {
            $score += 25; // No external links is fine
        } else {
            // Points for appropriate rel attributes
            $relRatio = ($nofollowCount + $sponsoredCount + $ugcCount) / $count;
            $score += $relRatio * 25;

            // Recommend rel attributes for user-generated or sponsored content
            if ($withoutRelCount > 0 && $count > 3) {
                $recommendations[] = $this->recommendation(
                    'low',
                    'Some external links without rel attributes',
                    "Consider adding rel='noopener' or rel='nofollow' to external links for security and SEO purposes."
                );
            }
        }

        // Check for noopener on target="_blank" links
        $missingNoopener = 0;
        foreach ($links as $link) {
            if ($link['opens_new_tab'] && ($link['rel'] === null || !str_contains(strtolower($link['rel']), 'noopener'))) {
                $missingNoopener++;
            }
        }

        $data['missing_noopener_count'] = $missingNoopener;

        if ($missingNoopener > 0) {
            $recommendations[] = $this->recommendation(
                'medium',
                'External links missing noopener',
                "{$missingNoopener} external links open in new tab without rel='noopener'. Add this for security."
            );
            $score -= min($missingNoopener * 2, 10);
        }

        return [
            'score' => max(0, $score),
            'data' => $data,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Analyze link text quality.
     */
    protected function analyzeLinkText(array $links): array
    {
        $score = 0.0;
        $data = [];
        $recommendations = [];

        $allLinks = array_merge($links['internal'], $links['external']);
        $total = count($allLinks);

        if ($total === 0) {
            return [
                'score' => 25,
                'data' => ['message' => 'No links to analyze'],
                'recommendations' => [],
            ];
        }

        // Analyze text lengths
        $shortText = 0;
        $longText = 0;
        $optimalText = 0;

        foreach ($allLinks as $link) {
            $len = $link['text_length'];
            if ($len < 2) {
                $shortText++;
            } elseif ($len > 60) {
                $longText++;
            } else {
                $optimalText++;
            }
        }

        $data['short_text_count'] = $shortText;
        $data['long_text_count'] = $longText;
        $data['optimal_text_count'] = $optimalText;
        $data['optimal_percentage'] = round(($optimalText / $total) * 100, 1);

        // Score for text quality
        $optimalRatio = $optimalText / $total;
        $score = $optimalRatio * 25;

        if ($shortText > 0) {
            $recommendations[] = $this->recommendation(
                'low',
                'Some links have very short anchor text',
                "{$shortText} links have anchor text shorter than 2 characters. Use more descriptive text."
            );
        }

        return [
            'score' => $score,
            'data' => $data,
            'recommendations' => $recommendations,
        ];
    }
}
