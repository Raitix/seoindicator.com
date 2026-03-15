<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use Illuminate\Http\JsonResponse;

/**
 * Credit Package Controller.
 *
 * Handles public listing of available credit packages for purchase.
 *
 * @see TASK-26-26
 * @see FEAT-026
 */
class CreditPackageController extends Controller
{
    /**
     * List all active credit packages.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $packages = CreditPackage::active()
            ->ordered()
            ->get()
            ->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'credits' => (float) $package->credits,
                    'price_usd' => (float) $package->price_usd,
                    'bonus_percent' => $package->bonus_percent,
                    'effective_credits' => $package->effective_credits,
                    'is_popular' => $package->is_popular,
                    'formatted_price' => $package->formatted_price,
                    'formatted_credits' => $package->formatted_credits,
                    'savings_percentage' => $package->savings_percentage,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }

    /**
     * Get a single credit package.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $package = CreditPackage::active()->find($id);

        if (!$package) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PACKAGE_NOT_FOUND',
                    'message' => 'The requested package could not be found.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $package->id,
                'name' => $package->name,
                'credits' => (float) $package->credits,
                'price_usd' => (float) $package->price_usd,
                'bonus_percent' => $package->bonus_percent,
                'effective_credits' => $package->effective_credits,
                'is_popular' => $package->is_popular,
                'formatted_price' => $package->formatted_price,
                'formatted_credits' => $package->formatted_credits,
                'savings_percentage' => $package->savings_percentage,
            ],
        ]);
    }
}
