# SPEC-026: Billing, Credits & Model Selection

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`High`

## Overview
Implement a credit-based billing system with Stripe and PayPal payment integrations. Users can purchase credits, view their balance in USD, and select AI models for analysis with transparent pricing display. Integrates with OpenRouter for model selection and cost calculation.

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           Frontend (React/Next.js)                       │
├─────────────────────────────────────────────────────────────────────────┤
│  Pricing Page  │  Credit Balance  │  Model Picker  │  Payment Flow     │
└────────┬───────┴────────┬──────────┴────────┬───────┴────────┬──────────┘
         │                │                  │                │
         ▼                ▼                  ▼                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                            Backend API (Laravel)                         │
├─────────────────────────────────────────────────────────────────────────┤
│  CreditService  │  PaymentGateway  │  ModelPricing  │  WebhookHandler   │
└────────┬───────┴────────┬──────────┴────────┬───────┴────────┬──────────┘
         │                │                  │                │
         ▼                ▼                  ▼                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          External Services                               │
├─────────────────────────────────────────────────────────────────────────┤
│  Stripe API     │  PayPal API      │  OpenRouter API                    │
└─────────────────────────────────────────────────────────────────────────┘
```

### Data Flow

1. **Credit Purchase Flow:**
   - User selects credit package → Frontend calls payment intent API
   - Backend creates payment intent (Stripe/PayPal) → Returns client secret
   - User completes payment → Webhook received → Credits added to balance
   - Transaction recorded with payment details

2. **Model Selection Flow:**
   - Frontend requests available models → Backend fetches from OpenRouter
   - Models returned with pricing per analysis
   - User selects model → Cost deducted from balance on analysis
   - Usage tracked for analytics

## Database Schema

### Credits Table
```sql
CREATE TABLE credits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_purchased DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_spent DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_credits_user_unique (user_id)
);
```

### Credit Transactions Table
```sql
CREATE TABLE credit_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    balance_after DECIMAL(10, 2) NOT NULL,
    type ENUM('purchase', 'usage', 'refund', 'bonus') NOT NULL,
    description VARCHAR(500) NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    payment_provider VARCHAR(20) NULL,
    payment_id VARCHAR(255) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_transactions_user_id (user_id),
    INDEX idx_transactions_type (type),
    INDEX idx_transactions_created_at (created_at),
    INDEX idx_transactions_payment (payment_provider, payment_id)
);
```

### Payment Methods Table
```sql
CREATE TABLE payment_methods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider ENUM('stripe', 'paypal') NOT NULL,
    provider_customer_id VARCHAR(255) NOT NULL,
    provider_method_id VARCHAR(255) NULL,
    is_default TINYINT(1) DEFAULT 0,
    last_four VARCHAR(4) NULL,
    brand VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_payment_methods_user_provider (user_id, provider)
);
```

### Credit Packages Table (Configurable pricing tiers)
```sql
CREATE TABLE credit_packages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    credits DECIMAL(10, 2) NOT NULL,
    price_usd DECIMAL(10, 2) NOT NULL,
    bonus_percent INT UNSIGNED DEFAULT 0,
    is_popular TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### AI Models Table (Cached from OpenRouter)
```sql
CREATE TABLE ai_models (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(100) NOT NULL,
    model_id VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    cost_per_1k_input DECIMAL(10, 6) NOT NULL DEFAULT 0.000000,
    cost_per_1k_output DECIMAL(10, 6) NOT NULL DEFAULT 0.000000,
    context_window INT UNSIGNED NOT NULL DEFAULT 4096,
    is_free_tier TINYINT(1) DEFAULT 0,
    free_tier_limit INT UNSIGNED NULL,
    capabilities JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_synced_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY idx_ai_models_provider_model (provider, model_id),
    INDEX idx_ai_models_active (is_active)
);
```

### User Model Preferences Table
```sql
CREATE TABLE user_model_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    model_id BIGINT UNSIGNED NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (model_id) REFERENCES ai_models(id) ON DELETE CASCADE,
    UNIQUE KEY idx_user_model_unique (user_id, model_id)
);
```

## API Endpoints

### Credit Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/credits/balance` | Get current credit balance |
| GET | `/api/v1/credits/transactions` | List transaction history |
| POST | `/api/v1/credits/purchase` | Initiate credit purchase |

### Payment Processing

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/payments/stripe/intent` | Create Stripe payment intent |
| POST | `/api/v1/payments/paypal/order` | Create PayPal order |
| POST | `/api/v1/payments/stripe/webhook` | Handle Stripe webhooks |
| POST | `/api/v1/payments/paypal/webhook` | Handle PayPal webhooks |
| GET | `/api/v1/payments/methods` | List saved payment methods |
| DELETE | `/api/v1/payments/methods/{id}` | Remove payment method |

### Model Selection

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/models` | List available AI models |
| GET | `/api/v1/models/{id}` | Get model details with pricing |
| POST | `/api/v1/models/preferences` | Set default model |
| GET | `/api/v1/models/pricing` | Get pricing overview page data |

### Credit Packages

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/packages` | List available credit packages |

## Service Layer

### CreditService
```php
class CreditService
{
    public function getBalance(User $user): float;
    public function addCredits(User $user, float $amount, string $type, array $metadata = []): CreditTransaction;
    public function deductCredits(User $user, float $amount, string $referenceType, int $referenceId): CreditTransaction;
    public function hasSufficientBalance(User $user, float $amount): bool;
    public function getTransactionHistory(User $user, int $perPage = 15): LengthAwarePaginator;
}
```

### PaymentGatewayService (Interface)
```php
interface PaymentGatewayInterface
{
    public function createPaymentIntent(User $user, float $amount, string $currency = 'USD'): array;
    public function capturePayment(string $paymentId): bool;
    public function refundPayment(string $paymentId, float $amount): bool;
    public function handleWebhook(Request $request): PaymentResult;
    public function getCustomer(User $user): ?string;
    public function createCustomer(User $user): string;
}
```

### StripeGatewayService
```php
class StripeGatewayService implements PaymentGatewayInterface
{
    // Stripe SDK integration
    // Payment Intent creation
    // Webhook signature verification
    // Customer management
}
```

### PayPalGatewayService
```php
class PayPalGatewayService implements PaymentGatewayInterface
{
    // PayPal SDK integration
    // Order creation and capture
    // Webhook verification
    // Customer management
}
```

### ModelPricingService
```php
class ModelPricingService
{
    public function getAvailableModels(): Collection;
    public function getModelPricing(string $modelId): array;
    public function calculateAnalysisCost(string $modelId, int $estimatedTokens): float;
    public function syncModelsFromOpenRouter(): int;
    public function getUserDefaultModel(User $user): ?AIModel;
}
```

## Security Considerations

### Payment Security
| Measure | Implementation |
|---------|----------------|
| PCI Compliance | Stripe/PayPal handle card data (no direct storage) |
| Webhook Verification | Signature verification for all webhooks |
| Idempotency | Idempotency keys for payment operations |
| Amount Validation | Server-side amount validation before processing |
| Double-spend Prevention | Transaction locks during credit operations |

### Credit System Security
| Measure | Implementation |
|---------|----------------|
| Balance Integrity | Database transactions with optimistic locking |
| Audit Trail | All transactions logged with metadata |
| Rate Limiting | Max 10 purchase attempts per hour |
| Fraud Detection | Unusual pattern detection (post-MVP) |

### API Security
| Measure | Implementation |
|---------|----------------|
| Authentication | Bearer token required on all endpoints |
| Authorization | User can only access their own credits |
| Input Validation | Strict validation on all inputs |
| CSRF Protection | Stateless API with token auth |

## Frontend Components

### Pricing Page
- Credit packages grid with pricing
- "Popular" badge highlighting
- Bonus credits display
- Model pricing comparison table
- FAQ section

### Credit Balance Component
- Current balance display (USD format)
- Quick "Add Credits" button
- Transaction history link
- Low balance warning (< $2.00)

### Model Picker Component
- Model selection dropdown/modal
- Cost per analysis display
- Free tier indicator
- Model capability badges (fast, advanced, etc.)
- Default model selection

### Payment Flow
- Package selection
- Payment method choice (Stripe/PayPal)
- Stripe Elements integration
- PayPal Smart Buttons
- Success/failure handling
- Email receipt

## Task Breakdown

### Phase 1: Database & Models (4 tasks)
1. **TASK-26-1**: Create credits and transactions tables migration
2. **TASK-26-2**: Create Credit model and repository
3. **TASK-26-3**: Create credit packages table with seed data
4. **TASK-26-4**: Create AI models table with OpenRouter sync

### Phase 2: Credit System (3 tasks)
5. **TASK-26-5**: Implement CreditService with balance management
6. **TASK-26-6**: Create credit balance API endpoint
7. **TASK-26-7**: Create transaction history API endpoint

### Phase 3: Stripe Integration (4 tasks)
8. **TASK-26-8**: Implement StripeGatewayService
9. **TASK-26-9**: Create Stripe payment intent endpoint
10. **TASK-26-10**: Implement Stripe webhook handler
11. **TASK-26-11**: Create payment methods management

### Phase 4: PayPal Integration (4 tasks)
12. **TASK-26-12**: Implement PayPalGatewayService
13. **TASK-26-13**: Create PayPal order endpoint
14. **TASK-26-14**: Implement PayPal webhook handler
15. **TASK-26-15**: Add PayPal Smart Buttons support

### Phase 5: Model Selection (4 tasks)
16. **TASK-26-16**: Implement ModelPricingService
17. **TASK-26-17**: Create models list API endpoint
18. **TASK-26-18**: Create model pricing API endpoint
19. **TASK-26-19**: Implement user model preferences

### Phase 6: Frontend (5 tasks)
20. **TASK-26-20**: Create pricing page with credit packages
21. **TASK-26-21**: Create credit balance component
22. **TASK-26-22**: Create model picker component
23. **TASK-26-23**: Implement Stripe payment flow
24. **TASK-26-24**: Implement PayPal payment flow

### Phase 7: Testing & Documentation (3 tasks)
25. **TASK-26-25**: Write unit tests for credit system
26. **TASK-26-26**: Write integration tests for payments
27. **TASK-26-27**: Update documentation

## Dependencies

### External Services
- **Stripe API**: Payment processing, customer management
- **PayPal API**: Alternative payment method
- **OpenRouter API**: Model pricing data sync

### Internal Dependencies
- User authentication system (existing)
- API middleware (existing)

## Environment Variables

```env
# Stripe Configuration
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# PayPal Configuration
PAYPAL_CLIENT_ID=...
PAYPAL_CLIENT_SECRET=...
PAYPAL_WEBHOOK_ID=...
PAYPAL_MODE=live

# OpenRouter Configuration
OPENROUTER_API_KEY=...

# Credit System
MIN_CREDIT_PURCHASE=5.00
CREDIT_CURRENCY=USD
```

## Risk Assessment

### High Risk Areas
1. **Payment Processing**: Financial transactions require careful handling
2. **Webhook Security**: Must verify signatures to prevent fraud
3. **Balance Consistency**: Concurrent operations must be handled correctly
4. **External Dependencies**: Stripe/PayPal downtime could affect users

### Mitigation Strategies
1. **Idempotency Keys**: Prevent duplicate charges
2. **Transaction Locking**: Use database transactions for balance updates
3. **Webhook Retries**: Handle out-of-order webhook delivery
4. **Fallback Messaging**: Graceful degradation when providers are down

## Rollout Plan

1. **Alpha**: Internal testing with Stripe test mode
2. **Beta**: Limited user testing with small transactions
3. **Production**: Full rollout with monitoring
4. **Post-Launch**: PayPal integration after Stripe is stable

## Comments

### 2026-02-28 - Architect
- Created initial technical specification
- High risk due to payment processing
- Stripe implementation first, PayPal as secondary option
- OpenRouter integration for dynamic model pricing
- Minimum $5 purchase to reduce transaction fees impact

---
*Created by Architect from FEAT-026*
