<?php

namespace Database\Seeders;

use App\Models\CreditPackage;
use Illuminate\Database\Seeder;

/**
 * Credit Package Seeder.
 *
 * Seeds the credit_packages table with predefined pricing tiers.
 * 
 * Task: TASK-26-3
 * Feature: FEAT-026 (Billing, Credits & Model Selection)
 */
class CreditPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Starter',
                'credits' => 5.00,
                'price_usd' => 5.00,
                'bonus_percent' => 0,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Basic',
                'credits' => 10.00,
                'price_usd' => 10.00,
                'bonus_percent' => 5,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Pro',
                'credits' => 25.00,
                'price_usd' => 25.00,
                'bonus_percent' => 10,
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'credits' => 50.00,
                'price_usd' => 50.00,
                'bonus_percent' => 15,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        // Use updateOrCreate for idempotency and cross-database compatibility
        foreach ($packages as $package) {
            CreditPackage::updateOrCreate(
                ['name' => $package['name']],
                $package
            );
        }
    }
}
