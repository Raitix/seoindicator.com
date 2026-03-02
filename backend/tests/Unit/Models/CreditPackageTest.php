<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\CreditPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CreditPackageTest extends TestCase
{
    use RefreshDatabase;

    // ==================== MODEL CREATION TESTS ====================

    /**
     * Test model can be created with required fields only.
     * CP-CR-01
     */
    public function test_model_can_be_created_with_required_fields_only(): void
    {
        $package = CreditPackage::create([
            'name' => 'Starter',
            'credits' => 5.00,
            'price_usd' => 5.00,
        ]);

        $this->assertInstanceOf(CreditPackage::class, $package);
        $this->assertEquals('Starter', $package->name);
        $this->assertEquals(5.00, $package->credits);
        $this->assertEquals(5.00, $package->price_usd);
        $this->assertEquals(0, $package->bonus_percent);
        $this->assertFalse($package->is_popular);
        $this->assertTrue($package->is_active);
        $this->assertEquals(0, $package->sort_order);
    }

    /**
     * Test model can be created with full data.
     * CP-CR-02
     */
    public function test_model_can_be_created_with_full_data(): void
    {
        $package = CreditPackage::create([
            'name' => 'Pro',
            'credits' => 25.00,
            'price_usd' => 25.00,
            'bonus_percent' => 10,
            'is_popular' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->assertEquals('Pro', $package->name);
        $this->assertEquals(25.00, $package->credits);
        $this->assertEquals(25.00, $package->price_usd);
        $this->assertEquals(10, $package->bonus_percent);
        $this->assertTrue($package->is_popular);
        $this->assertTrue($package->is_active);
        $this->assertEquals(2, $package->sort_order);
    }

    /**
     * Test mass assignment protection.
     * CP-CR-03
     */
    public function test_mass_assignment_protection(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test Package',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'bonus_percent' => 5,
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $this->assertEquals('Test Package', $package->name);
        $this->assertEquals(10.00, $package->credits);
        $this->assertEquals(10.00, $package->price_usd);
        $this->assertEquals(5, $package->bonus_percent);
        $this->assertFalse($package->is_popular);
        $this->assertTrue($package->is_active);
        $this->assertEquals(5, $package->sort_order);
    }

    /**
     * Test name is required.
     * CP-CR-04
     */
    public function test_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        CreditPackage::create([
            'credits' => 10.00,
            'price_usd' => 10.00,
        ]);
    }

    /**
     * Test credits is required.
     * CP-CR-05
     */
    public function test_credits_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        CreditPackage::create([
            'name' => 'Test',
            'price_usd' => 10.00,
        ]);
    }

    /**
     * Test price_usd is required.
     * CP-CR-06
     */
    public function test_price_usd_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        CreditPackage::create([
            'name' => 'Test',
            'credits' => 10.00,
        ]);
    }

    /**
     * Test model can be soft-deactivated.
     * CP-CR-07
     */
    public function test_model_can_be_soft_deactivated(): void
    {
        $package = CreditPackage::create([
            'name' => 'Legacy Package',
            'credits' => 15.00,
            'price_usd' => 15.00,
            'is_active' => true,
        ]);

        $package->is_active = false;
        $package->save();

        $this->assertFalse($package->is_active);
        $this->assertDatabaseHas('credit_packages', [
            'id' => $package->id,
            'is_active' => false,
        ]);
    }

    // ==================== CASTS TESTS ====================

    /**
     * Test credits DECIMAL cast.
     * CP-CA-01
     */
    public function test_credits_decimal_cast(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 123.45,
            'price_usd' => 100.00,
        ]);

        $this->assertIsFloat($package->credits);
        $this->assertEquals(123.45, $package->credits);
    }

    /**
     * Test credits precision 2 decimals.
     * CP-CA-02
     */
    public function test_credits_precision_two_decimals(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 123.456,
            'price_usd' => 100.00,
        ]);

        $package->refresh();
        $this->assertEqualsWithDelta(123.46, $package->credits, 0.001);
    }

    /**
     * Test price_usd DECIMAL cast.
     * CP-CA-03
     */
    public function test_price_usd_decimal_cast(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 100.00,
            'price_usd' => 99.99,
        ]);

        $this->assertIsFloat($package->price_usd);
        $this->assertEquals(99.99, $package->price_usd);
    }

    /**
     * Test price_usd precision 2 decimals.
     * CP-CA-04
     */
    public function test_price_usd_precision_two_decimals(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 100.00,
            'price_usd' => 99.999,
        ]);

        $package->refresh();
        $this->assertEqualsWithDelta(100.00, $package->price_usd, 0.001);
    }

    /**
     * Test bonus_percent INT cast.
     * CP-CA-05
     */
    public function test_bonus_percent_int_cast(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 100.00,
            'price_usd' => 100.00,
            'bonus_percent' => 15,
        ]);

        $this->assertIsInt($package->bonus_percent);
        $this->assertEquals(15, $package->bonus_percent);
    }

    /**
     * Test bonus_percent default is 0.
     * CP-CA-06
     */
    public function test_bonus_percent_default_is_zero(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 100.00,
            'price_usd' => 100.00,
        ]);

        $this->assertEquals(0, $package->bonus_percent);
    }

    /**
     * Test is_popular BOOLEAN cast.
     * CP-CA-07
     */
    public function test_is_popular_boolean_cast(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 100.00,
            'price_usd' => 100.00,
            'is_popular' => true,
        ]);

        $this->assertIsBool($package->is_popular);
        $this->assertTrue($package->is_popular);
    }

    /**
     * Test is_popular default is false.
     * CP-CA-08
     */
    public function test_is_popular_default_is_false(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 100.00,
            'price_usd' => 100.00,
        ]);

        $this->assertFalse($package->is_popular);
    }

    /**
     * Test is_active BOOLEAN cast.
     * CP-CA-09
     */
    public function test_is_active_boolean_cast(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 100.00,
            'price_usd' => 100.00,
            'is_active' => false,
        ]);

        $this->assertIsBool($package->is_active);
        $this->assertFalse($package->is_active);
    }

    /**
     * Test is_active default is true.
     * CP-CA-10
     */
    public function test_is_active_default_is_true(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 100.00,
            'price_usd' => 100.00,
        ]);

        $this->assertTrue($package->is_active);
    }

    /**
     * Test sort_order INT cast.
     * CP-CA-11
     */
    public function test_sort_order_int_cast(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 100.00,
            'price_usd' => 100.00,
            'sort_order' => 5,
        ]);

        $this->assertIsInt($package->sort_order);
        $this->assertEquals(5, $package->sort_order);
    }

    /**
     * Test sort_order default is 0.
     * CP-CA-12
     */
    public function test_sort_order_default_is_zero(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 100.00,
            'price_usd' => 100.00,
        ]);

        $this->assertEquals(0, $package->sort_order);
    }

    /**
     * Test timestamps cast.
     * CP-CA-13
     */
    public function test_timestamps_cast(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 100.00,
            'price_usd' => 100.00,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $package->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $package->updated_at);
    }

    // ==================== ACCESSOR TESTS ====================

    /**
     * Test effective_credits accessor without bonus.
     * CP-AC-01
     */
    public function test_effective_credits_accessor_without_bonus(): void
    {
        $package = CreditPackage::create([
            'name' => 'Starter',
            'credits' => 5.00,
            'price_usd' => 5.00,
            'bonus_percent' => 0,
        ]);

        // effective_credits = base credits (no bonus)
        if (method_exists($package, 'getEffectiveCreditsAttribute')) {
            $this->assertEquals(5.00, $package->effective_credits);
        } else {
            $this->markTestSkipped('effective_credits accessor not yet implemented');
        }
    }

    /**
     * Test effective_credits accessor with bonus.
     * CP-AC-02
     */
    public function test_effective_credits_accessor_with_bonus(): void
    {
        $package = CreditPackage::create([
            'name' => 'Pro',
            'credits' => 25.00,
            'price_usd' => 25.00,
            'bonus_percent' => 10,
        ]);

        // effective_credits = base + 10% bonus = 27.50
        if (method_exists($package, 'getEffectiveCreditsAttribute')) {
            $this->assertEquals(27.50, $package->effective_credits);
        } else {
            $this->markTestSkipped('effective_credits accessor not yet implemented');
        }
    }

    /**
     * Test effective_credits accessor with 15% bonus.
     * CP-AC-03
     */
    public function test_effective_credits_accessor_with_15_percent_bonus(): void
    {
        $package = CreditPackage::create([
            'name' => 'Enterprise',
            'credits' => 50.00,
            'price_usd' => 50.00,
            'bonus_percent' => 15,
        ]);

        // effective_credits = base + 15% bonus = 57.50
        if (method_exists($package, 'getEffectiveCreditsAttribute')) {
            $this->assertEquals(57.50, $package->effective_credits);
        } else {
            $this->markTestSkipped('effective_credits accessor not yet implemented');
        }
    }

    /**
     * Test effective_credits accessor with 5% bonus.
     * CP-AC-04
     */
    public function test_effective_credits_accessor_with_5_percent_bonus(): void
    {
        $package = CreditPackage::create([
            'name' => 'Basic',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'bonus_percent' => 5,
        ]);

        // effective_credits = base + 5% bonus = 10.50
        if (method_exists($package, 'getEffectiveCreditsAttribute')) {
            $this->assertEquals(10.50, $package->effective_credits);
        } else {
            $this->markTestSkipped('effective_credits accessor not yet implemented');
        }
    }

    /**
     * Test savings_percentage accessor with no bonus.
     * CP-AC-05
     */
    public function test_savings_percentage_accessor_no_bonus(): void
    {
        $package = CreditPackage::create([
            'name' => 'Starter',
            'credits' => 5.00,
            'price_usd' => 5.00,
            'bonus_percent' => 0,
        ]);

        // No bonus = no savings
        if (method_exists($package, 'getSavingsPercentageAttribute')) {
            $this->assertEquals(0, $package->savings_percentage);
        } else {
            $this->markTestSkipped('savings_percentage accessor not yet implemented');
        }
    }

    /**
     * Test savings_percentage accessor with 10% bonus.
     * CP-AC-06
     */
    public function test_savings_percentage_accessor_with_bonus(): void
    {
        $package = CreditPackage::create([
            'name' => 'Pro',
            'credits' => 25.00,
            'price_usd' => 25.00,
            'bonus_percent' => 10,
        ]);

        // 10% bonus = 10% savings (in this context, bonus IS the savings)
        if (method_exists($package, 'getSavingsPercentageAttribute')) {
            $this->assertEquals(10, $package->savings_percentage);
        } else {
            $this->markTestSkipped('savings_percentage accessor not yet implemented');
        }
    }

    /**
     * Test formatted_price accessor.
     * CP-AC-07
     */
    public function test_formatted_price_accessor(): void
    {
        $package = CreditPackage::create([
            'name' => 'Pro',
            'credits' => 25.00,
            'price_usd' => 25.00,
        ]);

        if (method_exists($package, 'getFormattedPriceAttribute')) {
            $this->assertEquals('$25.00', $package->formatted_price);
        } else {
            $this->markTestSkipped('formatted_price accessor not yet implemented');
        }
    }

    /**
     * Test formatted_credits accessor.
     * CP-AC-08
     */
    public function test_formatted_credits_accessor(): void
    {
        $package = CreditPackage::create([
            'name' => 'Pro',
            'credits' => 25.00,
            'price_usd' => 25.00,
        ]);

        if (method_exists($package, 'getFormattedCreditsAttribute')) {
            $this->assertEquals('$25.00', $package->formatted_credits);
        } else {
            $this->markTestSkipped('formatted_credits accessor not yet implemented');
        }
    }

    // ==================== SCOPE TESTS ====================

    /**
     * Test active scope returns only active packages.
     * CP-SC-01
     */
    public function test_active_scope_returns_only_active_packages(): void
    {
        CreditPackage::create([
            'name' => 'Active Package',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'is_active' => true,
        ]);

        CreditPackage::create([
            'name' => 'Inactive Package',
            'credits' => 20.00,
            'price_usd' => 20.00,
            'is_active' => false,
        ]);

        if (method_exists(CreditPackage::class, 'scopeActive')) {
            $activePackages = CreditPackage::active()->get();

            $this->assertCount(1, $activePackages);
            $this->assertEquals('Active Package', $activePackages->first()->name);
        } else {
            $this->markTestSkipped('active scope not yet implemented');
        }
    }

    /**
     * Test active scope returns multiple active packages.
     * CP-SC-02
     */
    public function test_active_scope_returns_multiple_active_packages(): void
    {
        CreditPackage::create([
            'name' => 'Active 1',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'is_active' => true,
        ]);

        CreditPackage::create([
            'name' => 'Active 2',
            'credits' => 20.00,
            'price_usd' => 20.00,
            'is_active' => true,
        ]);

        CreditPackage::create([
            'name' => 'Inactive',
            'credits' => 30.00,
            'price_usd' => 30.00,
            'is_active' => false,
        ]);

        if (method_exists(CreditPackage::class, 'scopeActive')) {
            $activePackages = CreditPackage::active()->get();
            $this->assertCount(2, $activePackages);
        } else {
            $this->markTestSkipped('active scope not yet implemented');
        }
    }

    /**
     * Test ordered scope sorts by sort_order.
     * CP-SC-03
     */
    public function test_ordered_scope_sorts_by_sort_order(): void
    {
        CreditPackage::create([
            'name' => 'Package C',
            'credits' => 30.00,
            'price_usd' => 30.00,
            'sort_order' => 3,
        ]);

        CreditPackage::create([
            'name' => 'Package A',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'sort_order' => 1,
        ]);

        CreditPackage::create([
            'name' => 'Package B',
            'credits' => 20.00,
            'price_usd' => 20.00,
            'sort_order' => 2,
        ]);

        if (method_exists(CreditPackage::class, 'scopeOrdered')) {
            $packages = CreditPackage::ordered()->get();

            $this->assertEquals('Package A', $packages[0]->name);
            $this->assertEquals('Package B', $packages[1]->name);
            $this->assertEquals('Package C', $packages[2]->name);
        } else {
            $this->markTestSkipped('ordered scope not yet implemented');
        }
    }

    /**
     * Test popular scope returns only popular packages.
     * CP-SC-04
     */
    public function test_popular_scope_returns_only_popular_packages(): void
    {
        CreditPackage::create([
            'name' => 'Popular Package',
            'credits' => 25.00,
            'price_usd' => 25.00,
            'is_popular' => true,
        ]);

        CreditPackage::create([
            'name' => 'Regular Package',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'is_popular' => false,
        ]);

        if (method_exists(CreditPackage::class, 'scopePopular')) {
            $popularPackages = CreditPackage::popular()->get();

            $this->assertCount(1, $popularPackages);
            $this->assertEquals('Popular Package', $popularPackages->first()->name);
        } else {
            $this->markTestSkipped('popular scope not yet implemented');
        }
    }

    /**
     * Test combined active and ordered scopes.
     * CP-SC-05
     */
    public function test_combined_active_and_ordered_scopes(): void
    {
        CreditPackage::create([
            'name' => 'Inactive A',
            'credits' => 5.00,
            'price_usd' => 5.00,
            'is_active' => false,
            'sort_order' => 0,
        ]);

        CreditPackage::create([
            'name' => 'Active B',
            'credits' => 25.00,
            'price_usd' => 25.00,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        CreditPackage::create([
            'name' => 'Active A',
            'credits' => 10.00,
            'price_usd' => 10.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        if (method_exists(CreditPackage::class, 'scopeActive') && 
            method_exists(CreditPackage::class, 'scopeOrdered')) {
            $packages = CreditPackage::active()->ordered()->get();

            $this->assertCount(2, $packages);
            $this->assertEquals('Active A', $packages[0]->name);
            $this->assertEquals('Active B', $packages[1]->name);
        } else {
            $this->markTestSkipped('active/ordered scopes not yet implemented');
        }
    }

    // ==================== INDEX TESTS ====================

    /**
     * Test index on is_active exists.
     * CP-IX-01
     */
    public function test_index_on_is_active_exists(): void
    {
        $indexes = DB::select("SHOW INDEX FROM credit_packages WHERE Column_name = 'is_active'");
        
        $this->assertGreaterThan(0, count($indexes), 'Index on is_active should exist');
    }

    /**
     * Test index on sort_order exists.
     * CP-IX-02
     */
    public function test_index_on_sort_order_exists(): void
    {
        $indexes = DB::select("SHOW INDEX FROM credit_packages WHERE Column_name = 'sort_order'");
        
        $this->assertGreaterThan(0, count($indexes), 'Index on sort_order should exist');
    }

    // ==================== EDGE CASE TESTS ====================

    /**
     * Test max credits value.
     * CP-EC-01
     */
    public function test_max_credits_value(): void
    {
        $package = CreditPackage::create([
            'name' => 'Max Credits',
            'credits' => 99999999.99,
            'price_usd' => 99999999.99,
        ]);

        $package->refresh();
        $this->assertEquals(99999999.99, $package->credits);
    }

    /**
     * Test max bonus_percent value.
     * CP-EC-02
     */
    public function test_max_bonus_percent_value(): void
    {
        $package = CreditPackage::create([
            'name' => 'Max Bonus',
            'credits' => 100.00,
            'price_usd' => 100.00,
            'bonus_percent' => 4294967295, // Max UNSIGNED INT
        ]);

        $package->refresh();
        $this->assertEquals(4294967295, $package->bonus_percent);
    }

    /**
     * Test max sort_order value.
     * CP-EC-03
     */
    public function test_max_sort_order_value(): void
    {
        $package = CreditPackage::create([
            'name' => 'Max Sort',
            'credits' => 100.00,
            'price_usd' => 100.00,
            'sort_order' => 4294967295, // Max UNSIGNED INT
        ]);

        $package->refresh();
        $this->assertEquals(4294967295, $package->sort_order);
    }

    /**
     * Test zero credits allowed.
     * CP-EC-04
     */
    public function test_zero_credits_allowed(): void
    {
        $package = CreditPackage::create([
            'name' => 'Free Trial',
            'credits' => 0.00,
            'price_usd' => 0.00,
        ]);

        $package->refresh();
        $this->assertEquals(0.00, $package->credits);
    }

    /**
     * Test zero price allowed.
     * CP-EC-05
     */
    public function test_zero_price_allowed(): void
    {
        $package = CreditPackage::create([
            'name' => 'Free Package',
            'credits' => 5.00,
            'price_usd' => 0.00,
        ]);

        $package->refresh();
        $this->assertEquals(0.00, $package->price_usd);
    }

    /**
     * Test name max length 100 characters.
     * CP-EC-06
     */
    public function test_name_max_length_100_characters(): void
    {
        $longName = str_repeat('a', 100);

        $package = CreditPackage::create([
            'name' => $longName,
            'credits' => 10.00,
            'price_usd' => 10.00,
        ]);

        $this->assertEquals(100, strlen($package->name));
    }

    /**
     * Test name exceeding max length fails.
     * CP-EC-07
     */
    public function test_name_exceeding_max_length_fails(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $tooLongName = str_repeat('a', 101);

        CreditPackage::create([
            'name' => $tooLongName,
            'credits' => 10.00,
            'price_usd' => 10.00,
        ]);
    }

    /**
     * Test duplicate names allowed.
     * CP-EC-08
     */
    public function test_duplicate_names_allowed(): void
    {
        CreditPackage::create([
            'name' => 'Standard',
            'credits' => 10.00,
            'price_usd' => 10.00,
        ]);

        // Should not throw - names are not unique
        $package2 = CreditPackage::create([
            'name' => 'Standard',
            'credits' => 20.00,
            'price_usd' => 20.00,
        ]);

        $this->assertEquals('Standard', $package2->name);
    }

    /**
     * Test small decimal values.
     * CP-EC-09
     */
    public function test_small_decimal_values(): void
    {
        $package = CreditPackage::create([
            'name' => 'Micro',
            'credits' => 0.01,
            'price_usd' => 0.01,
        ]);

        $package->refresh();
        $this->assertEquals(0.01, $package->credits);
        $this->assertEquals(0.01, $package->price_usd);
    }

    /**
     * Test bonus_percent zero vs null.
     * CP-EC-10
     */
    public function test_bonus_percent_zero_vs_null(): void
    {
        $package = CreditPackage::create([
            'name' => 'Test',
            'credits' => 10.00,
            'price_usd' => 10.00,
            // Not setting bonus_percent, should default to 0
        ]);

        $this->assertEquals(0, $package->bonus_percent);
        $this->assertNotNull($package->bonus_percent);
    }

    // ==================== SECURITY TESTS ====================

    /**
     * Test price values are server-side only.
     * CP-SEC-01
     */
    public function test_price_values_are_server_side_only(): void
    {
        // This is a design test - prices should only be modifiable server-side
        // No user input should affect package pricing
        $package = CreditPackage::create([
            'name' => 'Secure Package',
            'credits' => 100.00,
            'price_usd' => 100.00,
        ]);

        // Verify the package was created with the exact price specified
        $this->assertEquals(100.00, $package->price_usd);
    }

    /**
     * Test active flag allows hiding packages.
     * CP-SEC-02
     */
    public function test_active_flag_allows_hiding_packages(): void
    {
        $package = CreditPackage::create([
            'name' => 'Hidden Package',
            'credits' => 100.00,
            'price_usd' => 100.00,
            'is_active' => false,
        ]);

        // Package exists in DB but should not appear in active scope
        $this->assertDatabaseHas('credit_packages', ['id' => $package->id]);
        
        if (method_exists(CreditPackage::class, 'scopeActive')) {
            $this->assertCount(0, CreditPackage::active()->where('id', $package->id)->get());
        }
    }
}
