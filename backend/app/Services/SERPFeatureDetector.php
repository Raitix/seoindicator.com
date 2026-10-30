<?php

namespace App\Services;

use App\Models\SerpFeature;
use App\Services\SERP\SERPResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * SERPFeatureDetector service for TASK-7-4.
 *
 * Detects SERP features from search results and determines if they
 * belong to the target URL. Used by RankCheckerService during ranking checks.
 */
class SERPFeatureDetector
{
    /**
     * Detect SERP features from a SERP result.
     *
     * @param string $keyword The search keyword
     * @param string|null $targetUrl The user's target URL to match
     * @param SERPResult|array $serpResult The SERP result from fetcher
     * @return array Array of detected feature data
     */
    public function detectFeatures(string $keyword, ?string $targetUrl, $serpResult): array
    {
        $features = [];
        $checkedAt = now();
        $targetDomain = $targetUrl ? $this->extractDomain($targetUrl) : null;

        // Normalize SERP result
        $serpData = $this->normalizeSerpResult($serpResult);

        // Detect each feature type
        foreach (SerpFeature::FEATURE_TYPES as $featureType) {
            $detectorMethod = 'detect' . $this->studly($featureType);
            
            if (method_exists($this, $detectorMethod)) {
                try {
                    $featureData = $this->$detectorMethod($serpData, $targetDomain);
                    $features[] = array_merge($featureData, [
                        'type' => $featureType,
                        'keyword' => $keyword,
                        'checked_at' => $checkedAt,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("SERP feature detection failed for {$featureType}", [
                        'keyword' => $keyword,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Add feature as not present on error
                    $features[] = [
                        'type' => $featureType,
                        'is_present' => false,
                        'keyword' => $keyword,
                        'checked_at' => $checkedAt,
                    ];
                }
            } else {
                // No detector method = feature not detected
                $features[] = [
                    'type' => $featureType,
                    'is_present' => false,
                    'keyword' => $keyword,
                    'checked_at' => $checkedAt,
                ];
            }
        }

        return $features;
    }

    /**
     * Detect featured snippet from SERP data.
     */
    protected function detectFeaturedSnippet(array $serpData, ?string $targetDomain): array
    {
        $answerBox = $serpData['answerBox'] ?? $serpData['answer_box'] ?? null;
        
        if (!$answerBox) {
            return ['is_present' => false];
        }

        // Check if it's a featured snippet (not just any answer box)
        $type = $answerBox['type'] ?? '';
        if (!in_array($type, ['featured_snippet', 'organic', 'paragraph', 'list', 'table'])) {
            return ['is_present' => false];
        }

        $url = $answerBox['url'] ?? $answerBox['link'] ?? null;
        $isPresent = $url && $targetDomain && $this->urlMatchesDomain($url, $targetDomain);

        return [
            'is_present' => $isPresent,
            'position' => $isPresent ? 1 : null,
            'url' => $isPresent ? $url : null,
            'title' => $answerBox['title'] ?? null,
            'snippet' => $answerBox['snippet'] ?? $answerBox['answer'] ?? null,
        ];
    }

    /**
     * Detect local pack from SERP data.
     */
    protected function detectLocalPack(array $serpData, ?string $targetDomain): array
    {
        $localPack = $serpData['localPack'] ?? $serpData['local_pack'] ?? $serpData['localResults'] ?? null;
        
        if (!$localPack || !is_array($localPack) || empty($localPack)) {
            return ['is_present' => false];
        }

        // Check if any local pack entry matches target domain
        foreach ($localPack as $index => $entry) {
            $url = $entry['url'] ?? $entry['link'] ?? $entry['website'] ?? null;
            if ($url && $targetDomain && $this->urlMatchesDomain($url, $targetDomain)) {
                return [
                    'is_present' => true,
                    'position' => $index + 1,
                    'url' => $url,
                    'title' => $entry['title'] ?? $entry['name'] ?? null,
                ];
            }
        }

        return ['is_present' => false];
    }

    /**
     * Detect image pack from SERP data.
     */
    protected function detectImagePack(array $serpData, ?string $targetDomain): array
    {
        $images = $serpData['images'] ?? $serpData['image_pack'] ?? $serpData['imageResults'] ?? null;
        
        if (!$images || !is_array($images) || empty($images)) {
            return ['is_present' => false];
        }

        // Check if any image links to target domain
        foreach ($images as $image) {
            $sourceUrl = $image['source'] ?? $image['host_url'] ?? $image['link'] ?? null;
            if ($sourceUrl && $targetDomain && $this->urlMatchesDomain($sourceUrl, $targetDomain)) {
                return [
                    'is_present' => true,
                    'url' => $sourceUrl,
                    'title' => $image['title'] ?? null,
                ];
            }
        }

        return ['is_present' => false];
    }

    /**
     * Detect video results from SERP data.
     */
    protected function detectVideo(array $serpData, ?string $targetDomain): array
    {
        $videos = $serpData['videos'] ?? $serpData['videoResults'] ?? null;
        
        if (!$videos || !is_array($videos) || empty($videos)) {
            return ['is_present' => false];
        }

        // Check if any video links to target domain
        foreach ($videos as $video) {
            $url = $video['url'] ?? $video['link'] ?? null;
            if ($url && $targetDomain && $this->urlMatchesDomain($url, $targetDomain)) {
                return [
                    'is_present' => true,
                    'url' => $url,
                    'title' => $video['title'] ?? null,
                ];
            }
        }

        return ['is_present' => false];
    }

    /**
     * Detect People Also Ask from SERP data.
     */
    protected function detectPeopleAlsoAsk(array $serpData, ?string $targetDomain): array
    {
        $paa = $serpData['peopleAlsoAsk'] ?? $serpData['people_also_ask'] ?? $serpData['relatedQuestions'] ?? null;
        
        if (!$paa || !is_array($paa) || empty($paa)) {
            return ['is_present' => false];
        }

        // Check if any PAA answer links to target domain
        foreach ($paa as $item) {
            $url = $item['url'] ?? $item['link'] ?? null;
            if ($url && $targetDomain && $this->urlMatchesDomain($url, $targetDomain)) {
                return [
                    'is_present' => true,
                    'url' => $url,
                    'title' => $item['title'] ?? $item['question'] ?? null,
                ];
            }
        }

        return ['is_present' => false];
    }

    /**
     * Detect knowledge panel from SERP data.
     */
    protected function detectKnowledgePanel(array $serpData, ?string $targetDomain): array
    {
        $knowledgePanel = $serpData['knowledgePanel'] ?? $serpData['knowledge_panel'] ?? $serpData['knowledgeGraph'] ?? null;
        
        if (!$knowledgePanel) {
            return ['is_present' => false];
        }

        // Knowledge panels typically don't link to the target site directly
        // Check if the entity matches the target brand/site
        $website = $knowledgePanel['website'] ?? $knowledgePanel['url'] ?? null;
        $title = $knowledgePanel['title'] ?? $knowledgePanel['name'] ?? null;

        if ($website && $targetDomain && $this->urlMatchesDomain($website, $targetDomain)) {
            return [
                'is_present' => true,
                'url' => $website,
                'title' => $title,
            ];
        }

        return ['is_present' => false];
    }

    /**
     * Detect sitelinks from SERP data.
     */
    protected function detectSitelinks(array $serpData, ?string $targetDomain): array
    {
        // Sitelinks are typically on the first organic result
        $results = $serpData['results'] ?? $serpData['organicResults'] ?? [];
        
        if (empty($results) || !isset($results[0])) {
            return ['is_present' => false];
        }

        $firstResult = $results[0];
        $sitelinks = $firstResult['sitelinks'] ?? $firstResult['links'] ?? null;
        
        if (!$sitelinks || !is_array($sitelinks) || empty($sitelinks)) {
            return ['is_present' => false];
        }

        // Check if first result is the target domain
        $firstUrl = $firstResult['url'] ?? $firstResult['link'] ?? null;
        if ($firstUrl && $targetDomain && $this->urlMatchesDomain($firstUrl, $targetDomain)) {
            return [
                'is_present' => true,
                'url' => $firstUrl,
                'title' => $firstResult['title'] ?? null,
            ];
        }

        return ['is_present' => false];
    }

    /**
     * Detect reviews/ratings from SERP data.
     */
    protected function detectReviews(array $serpData, ?string $targetDomain): array
    {
        $results = $serpData['results'] ?? $serpData['organicResults'] ?? [];
        
        foreach ($results as $result) {
            $url = $result['url'] ?? $result['link'] ?? null;
            $rating = $result['rating'] ?? $result['reviewRating'] ?? $result['aggregateRating'] ?? null;
            
            if ($url && $targetDomain && $this->urlMatchesDomain($url, $targetDomain) && $rating) {
                return [
                    'is_present' => true,
                    'url' => $url,
                    'title' => $result['title'] ?? null,
                    'snippet' => "Rating: {$rating}",
                ];
            }
        }

        return ['is_present' => false];
    }

    /**
     * Detect FAQ rich results from SERP data.
     */
    protected function detectFaq(array $serpData, ?string $targetDomain): array
    {
        $results = $serpData['results'] ?? $serpData['organicResults'] ?? [];
        
        foreach ($results as $result) {
            $url = $result['url'] ?? $result['link'] ?? null;
            $faq = $result['faq'] ?? $result['faqSnippet'] ?? $result['richSnippet']['faq'] ?? null;
            
            if ($url && $targetDomain && $this->urlMatchesDomain($url, $targetDomain) && $faq) {
                return [
                    'is_present' => true,
                    'url' => $url,
                    'title' => $result['title'] ?? null,
                ];
            }
        }

        return ['is_present' => false];
    }

    /**
     * Detect product listings from SERP data.
     */
    protected function detectProduct(array $serpData, ?string $targetDomain): array
    {
        $products = $serpData['products'] ?? $serpData['shoppingResults'] ?? $serpData['productListings'] ?? null;
        
        if (!$products || !is_array($products) || empty($products)) {
            return ['is_present' => false];
        }

        foreach ($products as $product) {
            $url = $product['url'] ?? $product['link'] ?? $product['merchantLink'] ?? null;
            if ($url && $targetDomain && $this->urlMatchesDomain($url, $targetDomain)) {
                return [
                    'is_present' => true,
                    'url' => $url,
                    'title' => $product['title'] ?? $product['name'] ?? null,
                    'snippet' => $product['price'] ?? null,
                ];
            }
        }

        return ['is_present' => false];
    }

    /**
     * Normalize SERP result to array format.
     *
     * @param SERPResult|array $serpResult
     * @return array
     */
    protected function normalizeSerpResult($serpResult): array
    {
        if (is_array($serpResult)) {
            return $serpResult;
        }

        // Handle SERPResult object
        if ($serpResult instanceof SERPResult) {
            $results = [];
            foreach ($serpResult->getResults() as $entry) {
                $results[] = [
                    'position' => $entry->getPosition(),
                    'url' => $entry->getUrl(),
                    'title' => $entry->getTitle(),
                    'snippet' => $entry->getSnippet(),
                ];
            }

            $data = [
                'keyword' => $serpResult->getKeyword(),
                'results' => $results,
            ];

            // Get SERP features if available
            if (method_exists($serpResult, 'getSerpFeatures')) {
                $features = $serpResult->getSerpFeatures();
                if (is_array($features)) {
                    $data = array_merge($data, $features);
                }
            }

            return $data;
        }

        return [];
    }

    /**
     * Extract domain from URL.
     */
    protected function extractDomain(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $url = trim(strtolower($url));
        
        if (!str_contains($url, '://')) {
            $url = 'https://' . $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        
        if (!$host) {
            return null;
        }

        // Remove www prefix
        $host = preg_replace('/^www\./', '', $host);
        
        return $host ?: null;
    }

    /**
     * Check if a URL matches a target domain.
     */
    protected function urlMatchesDomain(string $url, string $targetDomain): bool
    {
        $urlDomain = $this->extractDomain($url);
        
        if (!$urlDomain) {
            return false;
        }

        // Exact match
        if ($urlDomain === $targetDomain) {
            return true;
        }

        // Subdomain match
        if (str_ends_with($urlDomain, '.' . $targetDomain)) {
            return true;
        }

        return false;
    }

    /**
     * Convert snake_case to StudlyCase.
     */
    protected function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $value)));
    }
}
