<?php

namespace App\Services\Gateway;

use App\Models\User;
use App\Models\PaymentMethod;
use Exception;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

/**
 * Stripe Gateway Service for TASK-26-8 and TASK-26-11.
 * 
 * Handles Stripe API interactions for payment methods.
 * This service can be mocked in tests via the service container.
 */
class StripeGatewayService
{
    protected ?StripeClient $stripe = null;

    public function __construct()
    {
        $secret = config('services.stripe.secret');
        if ($secret) {
            $this->stripe = new StripeClient($secret);
        }
    }

    /**
     * Get or create a Stripe customer for the user.
     *
     * @param User $user
     * @return string Stripe customer ID
     */
    public function getOrCreateCustomer(User $user): string
    {
        // Check if user already has a payment method with a customer ID
        $existingMethod = PaymentMethod::where('user_id', $user->id)->first();
        
        if ($existingMethod && $existingMethod->stripe_customer_id) {
            return $existingMethod->stripe_customer_id;
        }

        // Create new customer in Stripe
        if (!$this->stripe) {
            throw new Exception('Stripe is not configured');
        }

        $customer = $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        return $customer->id;
    }

    /**
     * Get payment methods for a user from Stripe.
     *
     * @param User $user
     * @return array<array{provider: string, brand: string|null, last_four: string|null, exp_month: int|null, exp_year: int|null, stripe_payment_method_id: string}>
     */
    public function getPaymentMethods(User $user): array
    {
        if (!$this->stripe) {
            return [];
        }

        $customerId = $this->getOrCreateCustomer($user);

        try {
            $paymentMethods = $this->stripe->paymentMethods->all([
                'customer' => $customerId,
                'type' => 'card',
            ]);

            $methods = [];
            foreach ($paymentMethods->data as $pm) {
                $methods[] = [
                    'provider' => 'stripe',
                    'brand' => $pm->card?->brand,
                    'last_four' => $pm->card?->last4,
                    'exp_month' => $pm->card?->exp_month,
                    'exp_year' => $pm->card?->exp_year,
                    'stripe_payment_method_id' => $pm->id,
                ];
            }

            return $methods;
        } catch (Exception $e) {
            Log::error('Failed to get payment methods from Stripe', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Delete (detach) a payment method from Stripe.
     *
     * @param string $paymentMethodId Stripe payment method ID (pm_...)
     * @return bool
     * @throws Exception
     */
    public function deletePaymentMethod(string $paymentMethodId): bool
    {
        if (!$this->stripe) {
            throw new Exception('Stripe is not configured');
        }

        try {
            $this->stripe->paymentMethods->detach($paymentMethodId);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete payment method from Stripe', [
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Attach a payment method to a customer.
     *
     * @param string $paymentMethodId
     * @param string $customerId
     * @return bool
     * @throws Exception
     */
    public function attachPaymentMethod(string $paymentMethodId, string $customerId): bool
    {
        if (!$this->stripe) {
            throw new Exception('Stripe is not configured');
        }

        try {
            $this->stripe->paymentMethods->attach($paymentMethodId, [
                'customer' => $customerId,
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('Failed to attach payment method to customer', [
                'payment_method_id' => $paymentMethodId,
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Set a payment method as the default for a customer.
     *
     * @param string $customerId
     * @param string $paymentMethodId
     * @return bool
     * @throws Exception
     */
    public function setDefaultPaymentMethod(string $customerId, string $paymentMethodId): bool
    {
        if (!$this->stripe) {
            throw new Exception('Stripe is not configured');
        }

        try {
            $this->stripe->customers->update($customerId, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('Failed to set default payment method', [
                'customer_id' => $customerId,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Get the owner user ID of a payment method.
     *
     * @param string $paymentMethodId The payment method ID
     * @return int|null User ID or null if not found
     */
    public function getPaymentMethodOwner(string $paymentMethodId): ?int
    {
        // Check local database first
        $paymentMethod = PaymentMethod::where('stripe_payment_method_id', $paymentMethodId)->first();
        
        if ($paymentMethod) {
            return $paymentMethod->user_id;
        }

        // If not in database, try to get from Stripe
        if (!$this->stripe) {
            return null;
        }

        try {
            $pm = $this->stripe->paymentMethods->retrieve($paymentMethodId);
            
            if ($pm->customer) {
                // Find the user by Stripe customer ID
                $userMethod = PaymentMethod::where('stripe_customer_id', $pm->customer)->first();
                
                return $userMethod?->user_id;
            }
        } catch (Exception $e) {
            Log::warning('Failed to get payment method owner from Stripe', [
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Sync payment methods from Stripe to local database.
     *
     * @param User $user
     * @return void
     */
    public function syncPaymentMethods(User $user): void
    {
        $customerId = $this->getOrCreateCustomer($user);
        $remoteMethods = $this->getPaymentMethods($user);

        // Get existing local methods
        $localMethods = PaymentMethod::where('user_id', $user->id)->get()->keyBy('stripe_payment_method_id');

        foreach ($remoteMethods as $remote) {
            $pmId = $remote['stripe_payment_method_id'];
            
            if ($localMethods->has($pmId)) {
                // Update existing
                $localMethods[$pmId]->update([
                    'brand' => $remote['brand'],
                    'last_four' => $remote['last_four'],
                    'exp_month' => $remote['exp_month'],
                    'exp_year' => $remote['exp_year'],
                ]);
            } else {
                // Create new
                PaymentMethod::create([
                    'user_id' => $user->id,
                    'stripe_customer_id' => $customerId,
                    'stripe_payment_method_id' => $pmId,
                    'provider' => $remote['provider'],
                    'brand' => $remote['brand'],
                    'last_four' => $remote['last_four'],
                    'exp_month' => $remote['exp_month'],
                    'exp_year' => $remote['exp_year'],
                    'is_default' => false,
                ]);
            }
        }

        // Remove local methods that no longer exist in Stripe
        $remoteIds = collect($remoteMethods)->pluck('stripe_payment_method_id');
        PaymentMethod::where('user_id', $user->id)
            ->whereNotNull('stripe_payment_method_id')
            ->whereNotIn('stripe_payment_method_id', $remoteIds)
            ->delete();
    }
}
