# SPEC-025: User Accounts + Dashboard + Analysis History

## STATUS
`To Break Into Tasks`

## PRIORITY
`High`

## RISK
`High`

## Related
- Feature: [FEAT-025](../FEATURES/FEAT-025.md)
- Tasks: (To be created by SpecSystemAnalyst)
- Integrates with: SPEC-016 (Tools Infrastructure), FEAT-011 (Team Management)

---

## Overview

Implement comprehensive user authentication system (email + Google OAuth), an enhanced dashboard with analysis history, and per-analysis cost tracking. This feature establishes the core user management infrastructure for the application.

---

## Architecture

### System Design
```
┌─────────────────────────────────────────────────────────────────┐
│                    Frontend (Next.js)                           │
├─────────────────────────────────────────────────────────────────┤
│   Pages:                                                        │
│   ├── /login                    - Email/Google login form       │
│   ├── /register                 - Email registration form       │
│   ├── /forgot-password          - Password reset request        │
│   ├── /reset-password           - Password reset confirmation   │
│   ├── /verify-email             - Email verification notice     │
│   └── /dashboard                - Enhanced dashboard            │
├─────────────────────────────────────────────────────────────────┤
│   Components:                                                   │
│   ├── AuthProvider              - Auth context provider         │
│   ├── LoginForm                 - Email login form              │
│   ├── GoogleLoginButton         - Google OAuth button           │
│   ├── DashboardStats            - Quick stats cards             │
│   ├── AnalysisHistoryList       - Analysis history table        │
│   ├── CostSummaryCard           - Cost tracking display         │
│   └── UserProfileDropdown       - User menu                     │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                   Backend API (Laravel)                         │
├─────────────────────────────────────────────────────────────────┤
│    Controllers:                                                 │
│    ├── AuthController           - Login/register/logout         │
│    ├── GoogleAuthController     - Google OAuth flow             │
│    ├── PasswordResetController  - Password reset flow           │
│    ├── EmailVerificationController - Email verification         │
│    ├── DashboardController      - Dashboard data aggregation    │
│    └── AnalysisHistoryController - Analysis history CRUD        │
├─────────────────────────────────────────────────────────────────┤
│    Services:                                                    │
│    ├── AuthService              - Core auth logic               │
│    ├── GoogleOAuthService       - Google OAuth integration      │
│    ├── CostTrackingService      - Analysis cost calculation     │
│    └── DashboardAggregator      - Dashboard data assembly       │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Database (MySQL)                             │
├─────────────────────────────────────────────────────────────────┤
│   Tables:                                                       │
│   ├── users (existing)          - User accounts                 │
│   ├── accounts (existing)       - Team/organization accounts    │
│   ├── social_accounts (new)     - OAuth provider connections    │
│   ├── analysis_history (new)    - Tool analysis records         │
│   └── analysis_costs (new)      - Cost tracking records         │
└─────────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│               External Services (FREE)                          │
├─────────────────────────────────────────────────────────────────┤
│   - Google OAuth 2.0 (FREE tier - 100 OAuth logins/day)        │
│   - Laravel Sanctum (FREE - SPA authentication)                 │
│   - Laravel Socialite (FREE - OAuth abstraction)                │
└─────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Table: social_accounts (OAuth Provider Connections)

```sql
CREATE TABLE social_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL,           -- 'google', 'github', etc.
    provider_id VARCHAR(255) NOT NULL,        -- Unique ID from provider
    provider_token TEXT NULL,                 -- Encrypted OAuth token
    provider_refresh_token TEXT NULL,         -- Encrypted refresh token
    provider_token_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_provider_user (provider, provider_id),
    INDEX idx_user_provider (user_id, provider)
);
```

### Table: analysis_history

```sql
CREATE TABLE analysis_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    site_id BIGINT UNSIGNED NULL,
    tool_name VARCHAR(100) NOT NULL,          -- 'meta-tag-analyzer', 'keyword-density', etc.
    input_url VARCHAR(2083) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    results JSON NULL,                        -- Analysis results (tool-specific)
    error_message TEXT NULL,
    analysis_duration_ms INT UNSIGNED NULL,
    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE SET NULL,
    INDEX idx_user_analyzed (user_id, analyzed_at DESC),
    INDEX idx_tool_name (tool_name),
    INDEX idx_status (status)
);
```

### Table: analysis_costs

```sql
CREATE TABLE analysis_costs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    analysis_id BIGINT UNSIGNED NOT NULL,
    cost_type VARCHAR(50) NOT NULL,           -- 'api_call', 'compute', 'storage'
    cost_units DECIMAL(10,4) NOT NULL DEFAULT 0,
    cost_currency VARCHAR(3) DEFAULT 'USD',
    cost_amount DECIMAL(10,6) NOT NULL DEFAULT 0,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (analysis_id) REFERENCES analysis_history(id) ON DELETE CASCADE,
    INDEX idx_analysis_id (analysis_id),
    INDEX idx_cost_type (cost_type)
);
```

### Table: user_preferences (Optional Enhancement)

```sql
CREATE TABLE user_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    timezone VARCHAR(50) DEFAULT 'UTC',
    date_format VARCHAR(20) DEFAULT 'YYYY-MM-DD',
    email_notifications TINYINT(1) DEFAULT 1,
    theme VARCHAR(20) DEFAULT 'light',
    dashboard_layout JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## API Endpoints

### Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/register` | Register new user with email |
| POST | `/api/v1/auth/login` | Login with email/password |
| POST | `/api/v1/auth/logout` | Logout current session |
| POST | `/api/v1/auth/google` | Initiate Google OAuth |
| POST | `/api/v1/auth/google/callback` | Handle Google OAuth callback |
| POST | `/api/v1/auth/forgot-password` | Request password reset |
| POST | `/api/v1/auth/reset-password` | Reset password with token |
| GET | `/api/v1/auth/verify-email/{id}/{hash}` | Verify email address |
| POST | `/api/v1/auth/resend-verification` | Resend verification email |
| GET | `/api/v1/auth/user` | Get current authenticated user |

### Dashboard Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/dashboard` | Get dashboard summary data |
| GET | `/api/v1/dashboard/stats` | Get quick stats cards data |
| GET | `/api/v1/dashboard/recent-analyses` | Get recent analyses |
| GET | `/api/v1/dashboard/cost-summary` | Get cost summary by period |

### Analysis History Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/analyses` | List analysis history (paginated) |
| GET | `/api/v1/analyses/{id}` | Get single analysis details |
| DELETE | `/api/v1/analyses/{id}` | Delete analysis record |
| GET | `/api/v1/analyses/{id}/costs` | Get cost breakdown for analysis |

---

## Detailed API Specifications

### Register User
`POST /api/v1/auth/register`

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePassword123!",
  "password_confirmation": "SecurePassword123!"
}
```

**Validation Rules:**
| Field | Rules |
|-------|-------|
| `name` | Required, string, max 255 chars |
| `email` | Required, email, unique:users |
| `password` | Required, min 8, confirmed, strong (letters + numbers) |

**Response (201):**
```json
{
  "success": true,
  "message": "Registration successful. Please verify your email.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified": false
    }
  }
}
```

### Login
`POST /api/v1/auth/login`

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "SecurePassword123!",
  "remember": true
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified": true,
      "role": "user"
    },
    "token": "1|abc123..."
  }
}
```

### Google OAuth Flow

**Step 1: Initiate OAuth**
`POST /api/v1/auth/google`

**Response (200):**
```json
{
  "redirect_url": "https://accounts.google.com/o/oauth2/auth?..."
}
```

**Step 2: Handle Callback**
`POST /api/v1/auth/google/callback`

**Request Body:**
```json
{
  "code": "4/0AeanS0...",
  "state": "csrf_token_here"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified": true,
      "avatar": "https://lh3.googleusercontent.com/..."
    },
    "token": "1|abc123...",
    "is_new_user": false
  }
}
```

### Dashboard Summary
`GET /api/v1/dashboard`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "stats": {
      "totalAnalyses": 127,
      "thisMonth": 23,
      "thisWeek": 8,
      "today": 2
    },
    "costSummary": {
      "totalCost": 12.45,
      "thisMonthCost": 3.20,
      "currency": "USD"
    },
    "recentAnalyses": [
      {
        "id": 127,
        "toolName": "meta-tag-analyzer",
        "inputUrl": "https://example.com",
        "status": "completed",
        "analyzedAt": "2026-02-28T10:00:00Z"
      }
    ],
    "quickInsights": {
      "mostUsedTool": "meta-tag-analyzer",
      "avgAnalysisTime": 1.2,
      "successRate": 98.4
    }
  }
}
```

### Analysis History List
`GET /api/v1/analyses`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `perPage` | int | 15 | Results per page (max: 100) |
| `tool` | string | null | Filter by tool name |
| `status` | string | null | Filter by status |
| `from` | date | null | Filter from date |
| `to` | date | null | Filter to date |

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 127,
      "toolName": "meta-tag-analyzer",
      "inputUrl": "https://example.com",
      "status": "completed",
      "results": {
        "title": "Example Domain",
        "metaDescription": "This domain is for use in illustrative examples...",
        "h1Count": 1
      },
      "cost": {
        "total": 0.05,
        "currency": "USD"
      },
      "analysisDurationMs": 1234,
      "analyzedAt": "2026-02-28T10:00:00Z"
    }
  ],
  "meta": {
    "total": 127,
    "page": 1,
    "perPage": 15,
    "lastPage": 9
  }
}
```

---

## Cost Tracking System

### Cost Calculation Logic

```php
class CostTrackingService
{
    /**
     * Cost rates per tool/operation (configurable)
     */
    private array $costRates = [
        'meta-tag-analyzer' => [
            'base' => 0.01,
            'per_request' => 0.005,
        ],
        'keyword-density' => [
            'base' => 0.01,
            'per_1000_words' => 0.001,
        ],
        'api_call' => [
            'external_serp' => 0.02,
            'gsc_api' => 0.001,
        ],
    ];

    /**
     * Calculate and record cost for an analysis
     */
    public function recordCost(AnalysisHistory $analysis, array $metrics): void
    {
        $toolName = $analysis->tool_name;
        $rate = $this->costRates[$toolName] ?? ['base' => 0.01];
        
        // Calculate base cost
        $baseCost = $rate['base'];
        
        // Calculate variable cost based on metrics
        $variableCost = $this->calculateVariableCost($toolName, $metrics);
        
        // Record costs
        AnalysisCost::create([
            'analysis_id' => $analysis->id,
            'cost_type' => 'compute',
            'cost_units' => 1,
            'cost_amount' => $baseCost,
            'description' => "Base cost for {$toolName}",
        ]);
        
        if ($variableCost > 0) {
            AnalysisCost::create([
                'analysis_id' => $analysis->id,
                'cost_type' => 'variable',
                'cost_units' => $metrics['units'] ?? 1,
                'cost_amount' => $variableCost,
                'description' => $metrics['description'] ?? 'Variable cost',
            ]);
        }
    }

    /**
     * Get cost summary for a user
     */
    public function getUserCostSummary(int $userId, ?string $period = null): array
    {
        $query = AnalysisCost::whereHas('analysis', fn($q) => $q->where('user_id', $userId));
        
        if ($period) {
            $query->where('created_at', '>=', now()->parse($period));
        }
        
        return [
            'total' => $query->sum('cost_amount'),
            'by_type' => $query->selectRaw('cost_type, SUM(cost_amount) as total')
                ->groupBy('cost_type')
                ->pluck('total', 'cost_type'),
        ];
    }
}
```

### MVP Cost Model (FREE Tier)

Since the project requires FREE tools only, the cost tracking is primarily for:

1. **Transparency**: Users see resource usage
2. **Future monetization**: Architecture supports paid tiers
3. **Self-monitoring**: Track API usage patterns

For MVP, all costs are **estimated/notional costs** based on:
- CPU time
- Memory usage
- External API calls (Google Suggest is free)
- Storage used

---

## Frontend Components

### AuthProvider (Context)
```typescript
interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  loginWithGoogle: () => Promise<void>;
  logout: () => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
}

// Wraps entire app, provides auth state
```

### Dashboard Page Structure
```tsx
// app/dashboard/page.tsx
export default function DashboardPage() {
  return (
    <DashboardLayout>
      <WelcomeCard />
      <StatsGrid>
        <TotalAnalysesCard />
        <ThisMonthCard />
        <CostSummaryCard />
        <SuccessRateCard />
      </StatsGrid>
      <RecentAnalysesSection />
      <QuickActionsSection />
    </DashboardLayout>
  );
}
```

### Analysis History Page
```tsx
// app/dashboard/history/page.tsx
export default function HistoryPage() {
  return (
    <DashboardLayout>
      <HistoryFilters />
      <AnalysisHistoryTable />
      <Pagination />
    </DashboardLayout>
  );
}
```

---

## Security Implementation

### Authentication Security

| Security Measure | Implementation |
|------------------|----------------|
| Password Hashing | bcrypt (Laravel default) |
| Session Management | Laravel Sanctum (SPA auth) |
| CSRF Protection | Sanctum CSRF cookie |
| Rate Limiting | 5 login attempts per minute |
| Brute Force Protection | Lockout after 5 failed attempts |
| Password Strength | Min 8 chars, letters + numbers required |
| Token Expiration | Configurable (default: 7 days) |

### Google OAuth Security

| Security Measure | Implementation |
|------------------|----------------|
| State Parameter | CSRF protection in OAuth flow |
| Token Encryption | Laravel `encrypted` cast |
| Token Storage | Encrypted in database |
| Scope Minimization | Only request `email`, `profile` |
| Redirect URI Validation | Whitelist in config |

### Authorization

| Endpoint Type | Authorization |
|---------------|---------------|
| Public | `/login`, `/register`, `/forgot-password` |
| Authenticated | `/dashboard`, `/analyses/*` |
| User Isolation | Users can only access their own analyses |

### IDOR Protection

```php
class AnalysisHistoryController
{
    public function show(AnalysisHistory $analysis)
    {
        // Ensure user owns this analysis
        if ($analysis->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to analysis.');
        }
        
        return new AnalysisResource($analysis);
    }
}
```

---

## Integration Points

### With Existing Features

| Feature | Integration |
|---------|-------------|
| FEAT-011 (Team Management) | Users belong to accounts; team access to analyses |
| FEAT-016 (Tools Infrastructure) | Tools create analysis history records |
| TASK-16-1 (API Proxy) | Proxy usage tracked in analysis history |

### Tool Analysis Hook

```php
// In APIProxyController after tool execution
class APIProxyController
{
    public function analyze(Request $request)
    {
        // ... perform analysis ...
        
        // Record in analysis history
        $analysis = AnalysisHistory::create([
            'user_id' => auth()->id(),
            'tool_name' => $request->tool,
            'input_url' => $request->url,
            'status' => 'completed',
            'results' => $results,
            'analysis_duration_ms' => $duration,
        ]);
        
        // Track costs
        app(CostTrackingService::class)->recordCost($analysis, [
            'units' => 1,
            'description' => 'Tool analysis',
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $results,
            'analysis_id' => $analysis->id,
        ]);
    }
}
```

---

## Rollout Plan

### Phase 1: Core Authentication
1. Install Laravel Sanctum & Socialite
2. Create social_accounts migration
3. Implement AuthController (register, login, logout)
4. Implement password reset flow
5. Implement email verification

### Phase 2: Google OAuth
6. Configure Google OAuth credentials
7. Implement GoogleAuthController
8. Create GoogleOAuthService
9. Handle account linking (email match)

### Phase 3: Analysis History
10. Create analysis_history migration
11. Create analysis_costs migration
12. Implement AnalysisHistory model/repository
13. Implement CostTrackingService
14. Integrate with existing tools (hook into APIProxyController)

### Phase 4: Dashboard
15. Implement DashboardController
16. Create dashboard API endpoints
17. Create frontend auth context/provider
18. Create login/register pages
19. Create enhanced dashboard page
20. Create analysis history page

### Phase 5: Testing & Polish
21. Unit tests for auth services
22. Feature tests for API endpoints
23. Integration tests for OAuth flow
24. Frontend component tests
25. Security audit (IDOR, CSRF, etc.)

---

## Tests

### Unit Tests

| Test File | Tests | Description |
|-----------|-------|-------------|
| `AuthServiceTest` | 15 | Registration, login, password validation |
| `GoogleOAuthServiceTest` | 10 | OAuth URL generation, callback handling |
| `CostTrackingServiceTest` | 8 | Cost calculation, recording, summary |
| `DashboardAggregatorTest` | 6 | Dashboard data assembly |

### Feature Tests

| Test File | Tests | Description |
|-----------|-------|-------------|
| `AuthControllerTest` | 20 | Register, login, logout, validation |
| `GoogleAuthControllerTest` | 12 | OAuth flow, account linking |
| `PasswordResetControllerTest` | 10 | Reset request, confirmation |
| `DashboardControllerTest` | 8 | Dashboard endpoints, authorization |
| `AnalysisHistoryControllerTest` | 15 | CRUD, filtering, pagination, IDOR |

### Security Tests

| Test | Description |
|------|-------------|
| SQL Injection | All inputs use parameterized queries |
| XSS | Input sanitization, output escaping |
| CSRF | Sanctum CSRF protection |
| IDOR | User isolation on all resources |
| Brute Force | Rate limiting on login |
| Session Fixation | Session regeneration on login |

---

## Dependencies

### Backend
- Laravel Sanctum (^4.0) - SPA authentication
- Laravel Socialite (^5.0) - OAuth abstraction
- Google OAuth 2.0 credentials (FREE tier)

### Frontend
- Next.js Auth utilities
- React Hook Form (form handling)
- Zod (validation schemas)

### Existing Infrastructure
- Users table (existing)
- Accounts table (existing for team management)
- API Proxy (TASK-16-1)

---

## Risk Assessment

| Risk | Level | Mitigation |
|------|-------|------------|
| **Authentication Bypass** | High | Use battle-tested Laravel Sanctum, follow best practices |
| **OAuth Token Exposure** | High | Encrypt tokens at rest, use HTTPS only |
| **IDOR Vulnerabilities** | High | Explicit ownership checks on all resources |
| **Brute Force Attacks** | Medium | Rate limiting, account lockout |
| **Session Hijacking** | Medium | Secure cookies, token rotation, HTTPS |
| **Cost Tracking Accuracy** | Low | Estimated costs for MVP, refine in post-MVP |

### High-Risk Justification
This feature is marked **High Risk** because:
1. **Authentication is security-critical** - Any vulnerability exposes all user data
2. **OAuth integration** - External dependency with security implications
3. **User data handling** - PII (email, name) requires proper protection
4. **Cost tracking** - Billing-adjacent functionality

---

## Comments
_Agent communication log_

### [2026-02-28 11:10 GMT+2] Agent: Architect
Created specification SPEC-025 for User Accounts + Dashboard + Analysis History (FEAT-025).

**Key Architectural Decisions:**
1. **Laravel Sanctum**: Industry-standard SPA authentication, battle-tested and secure.
2. **Laravel Socialite**: Abstraction layer for OAuth providers, supports multiple providers.
3. **Analysis History**: New table to track all tool analyses with results storage.
4. **Cost Tracking**: Estimated/notional costs for MVP (FREE tier), architecture supports future monetization.
5. **User Isolation**: Explicit ownership checks prevent IDOR vulnerabilities.

**Risk Confirmed**: High risk due to authentication security concerns. SecurityAuditor MUST review before deployment.

**Free Tools Compliance**: 
- Google OAuth is FREE (100 logins/day tier)
- Laravel Sanctum/Socialite are FREE
- No paid external services required

Ready for SpecSystemAnalyst to break into tasks.

---

*Created by Architect from FEAT-025*
