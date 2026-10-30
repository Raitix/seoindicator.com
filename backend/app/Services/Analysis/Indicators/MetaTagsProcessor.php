<?php

namespace App\Services\Analysis\Indicators;

use RuntimeException;
use DOMDocument;

/**
 * Meta Tags Indicator Processor.
 *
 * Analyzes meta tags including title, description, and Open Graph tags.
 */
class MetaTagsProcessor extends IndicatorProcessor
{
    public function slug(): string
    {
        return 'meta_tags';
    }

    public function name(): string
    {
        return 'Meta Tags';
    }

    public function maxScore(): float
    {
        return 100.0;
    }

    public function process(string $url): IndicatorResult
    {
        $startTime = microtime(true);

        try {
            $html = $this->fetchUrl($url);
            $dom = $this->parseHtml($html);

            $score = 0.0;
            $maxScore = $this->maxScore();
            $data = [];
            $recommendations = [];

            // Analyze title tag
            $titleResult = $this->analyzeTitle($dom);
            $score += $titleResult['score'];
            $data['title'] = $titleResult['data'];
            $recommendations = array_merge($recommendations, $titleResult['recommendations']);

            // Analyze meta description
            $descriptionResult = $this->analyzeDescription($dom);
            $score += $descriptionResult['score'];
            $data['description'] = $descriptionResult['data'];
            $recommendations = array_merge($recommendations, $descriptionResult['recommendations']);

            // Analyze Open Graph tags
            $ogResult = $this->analyzeOpenGraph($dom);
            $score += $ogResult['score'];
            $data['open_graph'] = $ogResult['data'];
            $recommendations = array_merge($recommendations, $ogResult['recommendations']);

            // Analyze Twitter Card tags
            $twitterResult = $this->analyzeTwitterCards($dom);
            $score += $twitterResult['score'];
            $data['twitter_cards'] = $twitterResult['data'];
            $recommendations = array_merge($recommendations, $twitterResult['recommendations']);

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
     * Analyze title tag.
     *
     * @return array{score: float, data: array, recommendations: array}
     */
    protected function analyzeTitle(DOMDocument $dom): array
    {
        $score = 0.0;
        $data = [];
        $recommendations = [];

        $title = $this->extractText($dom, 'title');
        $data['text'] = $title;
        $data['length'] = $title ? strlen($title) : 0;
        $data['present'] = !empty($title);

        if (empty($title)) {
            $recommendations[] = $this->recommendation(
                'high',
                'Missing title tag',
                'Add a descriptive title tag to improve SEO and user experience.'
            );
        } else {
            // Score for presence
            $score += 15;

            // Check length (optimal: 50-60 characters)
            if ($data['length'] >= 50 && $data['length'] <= 60) {
                $score += 15;
                $data['length_status'] = 'optimal';
            } elseif ($data['length'] >= 30 && $data['length'] < 50) {
                $score += 10;
                $data['length_status'] = 'slightly_short';
                $recommendations[] = $this->recommendation(
                    'low',
                    'Title tag is slightly short',
                    "Title is {$data['length']} characters. Aim for 50-60 characters for optimal display in search results."
                );
            } elseif ($data['length'] < 30) {
                $score += 5;
                $data['length_status'] = 'too_short';
                $recommendations[] = $this->recommendation(
                    'medium',
                    'Title tag is too short',
                    "Title is {$data['length']} characters. Aim for 50-60 characters for optimal display in search results."
                );
            } elseif ($data['length'] > 60 && $data['length'] <= 70) {
                $score += 12;
                $data['length_status'] = 'slightly_long';
                $recommendations[] = $this->recommendation(
                    'low',
                    'Title tag is slightly long',
                    "Title is {$data['length']} characters. Consider shortening to 50-60 characters to avoid truncation."
                );
            } else {
                $score += 5;
                $data['length_status'] = 'too_long';
                $recommendations[] = $this->recommendation(
                    'medium',
                    'Title tag is too long',
                    "Title is {$data['length']} characters. Reduce to 50-60 characters to avoid truncation in search results."
                );
            }
        }

        return [
            'score' => $score,
            'data' => $data,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Analyze meta description.
     *
     * @return array{score: float, data: array, recommendations: array}
     */
    protected function analyzeDescription(DOMDocument $dom): array
    {
        $score = 0.0;
        $data = [];
        $recommendations = [];

        $description = $this->extractAttribute($dom, 'meta[name="description"]', 'content');
        $data['text'] = $description;
        $data['length'] = $description ? strlen($description) : 0;
        $data['present'] = !empty($description);

        if (empty($description)) {
            $recommendations[] = $this->recommendation(
                'high',
                'Missing meta description',
                'Add a compelling meta description to improve click-through rates in search results.'
            );
        } else {
            // Score for presence
            $score += 15;

            // Check length (optimal: 150-160 characters)
            if ($data['length'] >= 150 && $data['length'] <= 160) {
                $score += 15;
                $data['length_status'] = 'optimal';
            } elseif ($data['length'] >= 100 && $data['length'] < 150) {
                $score += 10;
                $data['length_status'] = 'slightly_short';
                $recommendations[] = $this->recommendation(
                    'low',
                    'Meta description is slightly short',
                    "Description is {$data['length']} characters. Aim for 150-160 characters for optimal display."
                );
            } elseif ($data['length'] < 100) {
                $score += 5;
                $data['length_status'] = 'too_short';
                $recommendations[] = $this->recommendation(
                    'medium',
                    'Meta description is too short',
                    "Description is {$data['length']} characters. Aim for 150-160 characters for optimal display."
                );
            } elseif ($data['length'] > 160 && $data['length'] <= 200) {
                $score += 12;
                $data['length_status'] = 'slightly_long';
                $recommendations[] = $this->recommendation(
                    'low',
                    'Meta description is slightly long',
                    "Description is {$data['length']} characters. Consider shortening to 150-160 characters."
                );
            } else {
                $score += 5;
                $data['length_status'] = 'too_long';
                $recommendations[] = $this->recommendation(
                    'medium',
                    'Meta description is too long',
                    "Description is {$data['length']} characters. Reduce to 150-160 characters to avoid truncation."
                );
            }
        }

        return [
            'score' => $score,
            'data' => $data,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Analyze Open Graph tags.
     *
     * @return array{score: float, data: array, recommendations: array}
     */
    protected function analyzeOpenGraph(DOMDocument $dom): array
    {
        $score = 0.0;
        $data = [];
        $recommendations = [];

        $ogTags = ['og:title', 'og:description', 'og:image', 'og:url', 'og:type'];
        $foundTags = [];

        foreach ($ogTags as $tag) {
            $value = $this->extractAttribute($dom, "meta[property=\"{$tag}\"]", 'content');
            $foundTags[$tag] = $value;
            $data[$tag] = $value;
        }

        $data['present'] = !empty(array_filter($foundTags));

        if (!$data['present']) {
            $recommendations[] = $this->recommendation(
                'medium',
                'Missing Open Graph tags',
                'Add Open Graph tags for better social media sharing previews.'
            );
        } else {
            // Score for each present tag (max 20 points)
            foreach ($foundTags as $tag => $value) {
                if (!empty($value)) {
                    $score += 4;
                }
            }

            // Check essential tags
            $essentialTags = ['og:title', 'og:description', 'og:image'];
            $missingEssential = array_filter($essentialTags, fn($tag) => empty($foundTags[$tag]));

            if (!empty($missingEssential)) {
                $recommendations[] = $this->recommendation(
                    'medium',
                    'Missing essential Open Graph tags',
                    'Add missing tags: ' . implode(', ', $missingEssential) . ' for complete social sharing preview.'
                );
            }
        }

        return [
            'score' => min($score, 20), // Max 20 points for OG tags
            'data' => $data,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Analyze Twitter Card tags.
     *
     * @return array{score: float, data: array, recommendations: array}
     */
    protected function analyzeTwitterCards(DOMDocument $dom): array
    {
        $score = 0.0;
        $data = [];
        $recommendations = [];

        $twitterTags = ['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image'];
        $foundTags = [];

        foreach ($twitterTags as $tag) {
            $value = $this->extractAttribute($dom, "meta[name=\"{$tag}\"]", 'content');
            $foundTags[$tag] = $value;
            $data[$tag] = $value;
        }

        $data['present'] = !empty(array_filter($foundTags));

        if (!$data['present']) {
            // Don't penalize too much - Twitter can use OG tags
            $recommendations[] = $this->recommendation(
                'low',
                'Missing Twitter Card tags',
                'Consider adding Twitter Card tags for better Twitter sharing previews, though Open Graph tags will be used as fallback.'
            );
        } else {
            // Score for each present tag (max 20 points)
            foreach ($foundTags as $tag => $value) {
                if (!empty($value)) {
                    $score += 5;
                }
            }
        }

        return [
            'score' => min($score, 20), // Max 20 points for Twitter cards
            'data' => $data,
            'recommendations' => $recommendations,
        ];
    }
}
