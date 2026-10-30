<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SerpFeature;
use Illuminate\Http\JsonResponse;

/**
 * Metadata Controller for frontend configuration data.
 *
 * TASK-7-18: SERP Feature metadata for filter dropdowns.
 */
class MetadataController extends Controller
{
    /**
     * Get SERP feature types for filtering.
     *
     * GET /api/v1/metadata/serp-features
     *
     * @return JsonResponse
     */
    public function serpFeatures(): JsonResponse
    {
        $features = [];
        
        foreach (SerpFeature::FEATURE_TYPES as $type) {
            $meta = SerpFeature::FEATURE_META[$type] ?? [
                'name' => ucfirst(str_replace('_', ' ', $type)),
                'icon' => 'circle',
                'description' => '',
                'ctrImpact' => '',
            ];
            
            $features[] = [
                'value' => $type,
                'label' => $meta['name'],
                'icon' => $meta['icon'],
                'description' => $meta['description'],
                'ctrImpact' => $meta['ctrImpact'],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $features,
        ]);
    }

    /**
     * Get all metadata in one request.
     *
     * GET /api/v1/metadata
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'serpFeatures' => $this->getSerpFeaturesData(),
            ],
        ]);
    }

    /**
     * Get SERP features data array.
     *
     * @return array
     */
    protected function getSerpFeaturesData(): array
    {
        $features = [];
        
        foreach (SerpFeature::FEATURE_TYPES as $type) {
            $meta = SerpFeature::FEATURE_META[$type] ?? [
                'name' => ucfirst(str_replace('_', ' ', $type)),
                'icon' => 'circle',
                'description' => '',
                'ctrImpact' => '',
            ];
            
            $features[] = [
                'value' => $type,
                'label' => $meta['name'],
                'icon' => $meta['icon'],
                'description' => $meta['description'],
                'ctrImpact' => $meta['ctrImpact'],
            ];
        }

        return $features;
    }
}
