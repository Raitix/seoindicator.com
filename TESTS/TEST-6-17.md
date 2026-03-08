# TEST-6-17: OpenAPI Specification Tests

## Overview
Tests for OpenAPI (Swagger) 3.0 specification validation.

## Test Files
- `backend/tests/Unit/OpenApi/OpenApiSpecTest.php` - Main spec validation tests
- `backend/tests/Unit/OpenApi/OpenApiSchemaTest.php` - Schema definition tests
- `backend/tests/Unit/OpenApi/OpenApiEndpointsTest.php` - Endpoint coverage tests

---

## Test Categories

### OAS-STRUCTURE: OpenAPI Structure Validation
| ID | Test | Description |
|----|------|-------------|
| OAS-001 | File exists | `backend/src/api/openapi.yaml` exists |
| OAS-002 | Valid YAML | File parses as valid YAML |
| OAS-003 | OpenAPI version | `openapi` field is "3.0.x" |
| OAS-004 | Info section | `info.title`, `info.version`, `info.description` present |
| OAS-005 | Servers section | At least one server defined |
| OAS-006 | Paths section | `paths` object exists |
| OAS-007 | Components section | `components` object exists |
| OAS-008 | Security schemes | `components.securitySchemes` defined |

### OAS-INFO: API Info Validation
| ID | Test | Description |
|----|------|-------------|
| INF-001 | Title present | `info.title` is non-empty string |
| INF-002 | Version format | `info.version` follows semver (x.y.z) |
| INF-003 | Description present | `info.description` describes API |
| INF-004 | Contact info | `info.contact` with name, email, url |
| INF-005 | License info | `info.license` with name, url |
| INF-006 | Terms of service | `info.termsOfService` URL |

### OAS-SERVERS: Server Configuration
| ID | Test | Description |
|----|------|-------------|
| SRV-001 | Production server | Server with production URL |
| SRV-002 | Staging server | Optional staging server |
| SRV-003 | Server variables | Variables for environment |
| SRV-004 | Server description | Each server has description |
| SRV-005 | URL format | All server URLs are valid |

### OAS-AUTH: Authentication Schemes
| ID | Test | Description |
|----|------|-------------|
| AUTH-001 | Bearer scheme | `BearerAuth` security scheme exists |
| AUTH-002 | Bearer type | Type is "http", scheme is "bearer" |
| AUTH-003 | Bearer format | `bearerFormat` is "JWT" |
| AUTH-004 | API Key scheme | `ApiKeyAuth` security scheme exists |
| AUTH-005 | API Key location | Type is "apiKey", in is "header" |
| AUTH-006 | API Key name | Name is "X-API-Key" |
| AUTH-007 | OAuth2 scheme | Optional OAuth2 scheme |
| AUTH-008 | Description present | All schemes have descriptions |

### OAS-PATHS: Path Definitions
| ID | Test | Description |
|----|------|-------------|
| PATH-001 | Paths are objects | Each path is an object |
| PATH-002 | HTTP methods | Methods are valid (get, post, put, delete, patch) |
| PATH-003 | Operation IDs | Each operation has unique `operationId` |
| PATH-004 | Summaries | Each operation has `summary` |
| PATH-005 | Descriptions | Each operation has `description` |
| PATH-006 | Tags | Each operation has at least one `tag` |
| PATH-007 | Responses | Each operation has `responses` object |
| PATH-008 | Parameters | Path/query parameters defined |
| PATH-009 | Request bodies | POST/PUT/PATCH have `requestBody` |
| PATH-010 | Security applied | Protected paths have `security` |

### OAS-RESPONSES: Response Definitions
| ID | Test | Description |
|----|------|-------------|
| RESP-001 | Success responses | 200/201 responses defined |
| RESP-002 | Error responses | 400/401/403/404/422/500 responses |
| RESP-003 | Response content | `content` with media types |
| RESP-004 | Response schemas | Schemas reference `$ref` |
| RESP-005 | Response descriptions | Each response has description |
| RESP-006 | Error schema | Common error response schema |

### OAS-SCHEMAS: Component Schemas
| ID | Test | Description |
|----|------|-------------|
| SCH-001 | User schema | User entity schema defined |
| SCH-002 | Site schema | Site entity schema defined |
| SCH-003 | Keyword schema | Keyword entity schema defined |
| SCH-004 | Project schema | Project entity schema defined |
| SCH-005 | Report schema | Report entity schema defined |
| SCH-006 | Error schema | Error response schema |
| SCH-007 | Pagination schema | Paginated response schema |
| SCH-008 | Schema properties | Each schema has required properties |
| SCH-009 | Required fields | Required fields marked |
| SCH-010 | Type definitions | All properties have types |
| SCH-011 | Nullable fields | Nullable fields marked |
| SCH-012 | Example values | Schemas have examples |

### OAS-ENDPOINTS: Endpoint Coverage

#### Authentication Endpoints
| ID | Test | Endpoint |
|----|------|----------|
| EP-AUTH-001 | Register endpoint | POST /v1/auth/register |
| EP-AUTH-002 | Login endpoint | POST /v1/auth/login |
| EP-AUTH-003 | Logout endpoint | POST /v1/auth/logout |
| EP-AUTH-004 | User endpoint | GET /v1/auth/user |
| EP-AUTH-005 | Forgot password | POST /v1/auth/forgot-password |
| EP-AUTH-006 | Reset password | POST /v1/auth/reset-password |
| EP-AUTH-007 | Verify email | GET /v1/auth/verify-email/{id}/{hash} |
| EP-AUTH-008 | Resend verification | POST /v1/auth/resend-verification |
| EP-AUTH-009 | Google OAuth | POST /v1/auth/google |
| EP-AUTH-010 | Google callback | POST /v1/auth/google/callback |

#### Keywords Endpoints
| ID | Test | Endpoint |
|----|------|----------|
| EP-KW-001 | List keywords | GET /v1/keywords |
| EP-KW-002 | Create keyword | POST /v1/keywords |
| EP-KW-003 | Get keyword | GET /v1/keywords/{id} |
| EP-KW-004 | Update keyword | PUT /v1/keywords/{id} |
| EP-KW-005 | Delete keyword | DELETE /v1/keywords/{id} |
| EP-KW-006 | Keyword rankings | GET /v1/keywords/{id}/rankings |
| EP-KW-007 | Site keywords | GET /v1/sites/{siteId}/keywords |
| EP-KW-008 | Keyword history | GET /v1/keywords/{id}/history |
| EP-KW-009 | Check ranking | POST /v1/keywords/{id}/check |

#### Sites Endpoints
| ID | Test | Endpoint |
|----|------|----------|
| EP-SITE-001 | List sites | GET /v1/sites |
| EP-SITE-002 | Create site | POST /v1/sites |
| EP-SITE-003 | Get site | GET /v1/sites/{id} |
| EP-SITE-004 | Update site | PUT /v1/sites/{id} |
| EP-SITE-005 | Delete site | DELETE /v1/sites/{id} |
| EP-SITE-006 | Site dashboard | GET /v1/sites/{siteId}/dashboard |

#### Projects Endpoints
| ID | Test | Endpoint |
|----|------|----------|
| EP-PROJ-001 | List projects | GET /v1/projects |
| EP-PROJ-002 | Create project | POST /v1/projects |
| EP-PROJ-003 | Get project | GET /v1/projects/{id} |
| EP-PROJ-004 | Update project | PUT /v1/projects/{id} |
| EP-PROJ-005 | Delete project | DELETE /v1/projects/{id} |
| EP-PROJ-006 | Archive project | POST /v1/projects/{id}/archive |
| EP-PROJ-007 | Restore project | POST /v1/projects/{id}/restore |

#### Reports Endpoints
| ID | Test | Endpoint |
|----|------|----------|
| EP-RPT-001 | List reports | GET /v1/sites/{siteId}/reports |
| EP-RPT-002 | Generate report | POST /v1/sites/{siteId}/reports/generate |
| EP-RPT-003 | Get report | GET /v1/reports/{reportId} |
| EP-RPT-004 | Delete report | DELETE /v1/reports/{reportId} |
| EP-RPT-005 | Download report | GET /v1/reports/{reportId}/download |
| EP-RPT-006 | Report templates | GET /v1/sites/{siteId}/report-templates |
| EP-RPT-007 | Create template | POST /v1/sites/{siteId}/report-templates |
| EP-RPT-008 | Scheduled reports | GET /v1/sites/{siteId}/scheduled-reports |

#### Audit Endpoints
| ID | Test | Endpoint |
|----|------|----------|
| EP-AUDIT-001 | Create audit | POST /v1/sites/{site}/audits |
| EP-AUDIT-002 | List audits | GET /v1/sites/{site}/audits |
| EP-AUDIT-003 | Get audit | GET /v1/audits/{audit} |
| EP-AUDIT-004 | Delete audit | DELETE /v1/audits/{audit} |
| EP-AUDIT-005 | Audit issues | GET /v1/audits/{auditId}/issues |
| EP-AUDIT-006 | Bulk update issues | PATCH /v1/audits/{auditId}/issues/bulk |
| EP-AUDIT-007 | Publish audit | POST /v1/audits/{id}/publish |
| EP-AUDIT-008 | Unpublish audit | DELETE /v1/audits/{id}/unpublish |
| EP-AUDIT-009 | Embed code | GET /v1/audits/{id}/embed-code |
| EP-AUDIT-010 | Audit analytics | GET /v1/audits/{id}/analytics |

#### Competitors Endpoints
| ID | Test | Endpoint |
|----|------|----------|
| EP-COMP-001 | List competitors | GET /v1/competitors |
| EP-COMP-002 | Create competitor | POST /v1/competitors |
| EP-COMP-003 | Get competitor | GET /v1/competitors/{id} |
| EP-COMP-004 | Update competitor | PUT /v1/competitors/{id} |
| EP-COMP-005 | Delete competitor | DELETE /v1/competitors/{id} |

#### Analysis Endpoints
| ID | Test | Endpoint |
|----|------|----------|
| EP-ANAL-001 | List jobs | GET /v1/sites/{siteId}/analysis/jobs |
| EP-ANAL-002 | Create job | POST /v1/sites/{siteId}/analysis/jobs |
| EP-ANAL-003 | Get job | GET /v1/analysis/jobs/{jobId} |
| EP-ANAL-004 | Start job | POST /v1/analysis/jobs/{jobId}/start |
| EP-ANAL-005 | Job stream | GET /v1/analysis/jobs/{jobId}/stream |
| EP-ANAL-006 | Job progress | GET /v1/analysis/jobs/{jobId}/progress |
| EP-ANAL-007 | Delete job | DELETE /v1/analysis/jobs/{jobId} |

#### AI Visibility Endpoints
| ID | Test | Endpoint |
|----|------|----------|
| EP-AI-001 | List projects | GET /v1/ai-visibility/projects |
| EP-AI-002 | Create project | POST /v1/ai-visibility/projects |
| EP-AI-003 | Get project | GET /v1/ai-visibility/projects/{id} |
| EP-AI-004 | Update project | PUT /v1/ai-visibility/projects/{id} |
| EP-AI-005 | Delete project | DELETE /v1/ai-visibility/projects/{id} |
| EP-AI-006 | Run queries | POST /v1/ai-visibility/projects/{id}/run |
| EP-AI-007 | Analytics | GET /v1/ai-visibility/projects/{id}/analytics |

#### Public Endpoints
| ID | Test | Endpoint |
|----|------|----------|
| EP-PUB-001 | Health check | GET /health |
| EP-PUB-002 | Public audit | GET /v1/public-audit/{hash} |
| EP-PUB-003 | Lead capture | POST /v1/public-audit/{hash}/lead |
| EP-PUB-004 | Badge config | GET /v1/public-audit/{hash}/badge |
| EP-PUB-005 | Badge impression | POST /v1/badge/impression |
| EP-PUB-006 | Badge click | POST /v1/badge/click |
| EP-PUB-007 | PWA guide | GET /v1/pwa/guide |
| EP-PUB-008 | Models list | GET /v1/models |
| EP-PUB-009 | Model pricing | GET /v1/models/{id}/pricing |
| EP-PUB-010 | AI models | GET /v1/ai/models |
| EP-PUB-011 | Tools list | GET /v1/tools |
| EP-PUB-012 | Analyze tool | POST /v1/tools/analyze |
| EP-PUB-013 | Indicators | GET /indicators |

#### Admin Endpoints
| ID | Test | Endpoint |
|----|------|----------|
| EP-ADM-001 | Admin stats | GET /v1/admin/stats |
| EP-ADM-002 | User list | GET /v1/admin/users |
| EP-ADM-003 | Create user | POST /v1/admin/users |
| EP-ADM-004 | Get user | GET /v1/admin/users/{id} |
| EP-ADM-005 | Update user | PUT /v1/admin/users/{id} |
| EP-ADM-006 | Delete user | DELETE /v1/admin/users/{id} |
| EP-ADM-007 | Health overview | GET /v1/admin/health/overview |
| EP-ADM-008 | Queue status | GET /v1/admin/health/queues |
| EP-ADM-009 | DB metrics | GET /v1/admin/health/database |
| EP-ADM-010 | Alerts | GET /v1/admin/health/alerts |
| EP-ADM-011 | Credits overview | GET /v1/admin/credits/overview |
| EP-ADM-012 | Transactions | GET /v1/admin/credits/transactions |

#### Payment Endpoints
| ID | Test | Endpoint |
|----|------|----------|
| EP-PAY-001 | Payment methods | GET /v1/payments/methods |
| EP-PAY-002 | Delete method | DELETE /v1/payments/methods/{id} |
| EP-PAY-003 | PayPal order | POST /v1/payments/paypal/order |
| EP-PAY-004 | PayPal capture | POST /v1/payments/paypal/capture |
| EP-PAY-005 | PayPal webhook | POST /v1/payments/paypal/webhook |
| EP-PAY-006 | Credit transactions | GET /v1/credits/transactions |

#### Webhook Endpoints
| ID | Test | Endpoint |
|----|------|----------|
| EP-WH-001 | List webhooks | GET /v1/webhooks |
| EP-WH-002 | Create webhook | POST /v1/webhooks |
| EP-WH-003 | Get webhook | GET /v1/webhooks/{id} |
| EP-WH-004 | Update webhook | PUT /v1/webhooks/{id} |
| EP-WH-005 | Delete webhook | DELETE /v1/webhooks/{id} |
| EP-WH-006 | Test webhook | POST /v1/webhooks/{id}/test |

### OAS-VALIDATION: Schema Validation
| ID | Test | Description |
|----|------|-------------|
| VAL-001 | Valid OpenAPI | Spec validates against OpenAPI 3.0 schema |
| VAL-002 | No duplicate operation IDs | All operation IDs unique |
| VAL-003 | No duplicate tags | All tags unique |
| VAL-004 | Valid refs | All `$ref` values resolve |
| VAL-005 | Valid paths | All paths start with `/` |
| VAL-006 | Valid methods | All methods are valid HTTP methods |
| VAL-007 | Valid status codes | All status codes are valid HTTP codes |
| VAL-008 | Valid media types | All media types are valid MIME types |

### OAS-SECURITY: Security Documentation
| ID | Test | Description |
|----|------|-------------|
| SEC-001 | Security applied | Protected endpoints have security |
| SEC-002 | Public endpoints | Public endpoints have no security |
| SEC-003 | Security scopes | OAuth scopes defined if OAuth used |
| SEC-004 | API key permissions | API key scopes documented |
| SEC-005 | Rate limits | Rate limits documented in description |

### OAS-ERRORS: Error Documentation
| ID | Test | Description |
|----|------|-------------|
| ERR-001 | 400 response | Bad request response defined |
| ERR-002 | 401 response | Unauthorized response defined |
| ERR-003 | 403 response | Forbidden response defined |
| ERR-004 | 404 response | Not found response defined |
| ERR-005 | 422 response | Validation error response defined |
| ERR-006 | 429 response | Rate limited response defined |
| ERR-007 | 500 response | Server error response defined |
| ERR-008 | Error schema | Common error schema used |

### OAS-TAGS: Tag Organization
| ID | Test | Description |
|----|------|-------------|
| TAG-001 | Tags defined | `tags` section exists |
| TAG-002 | Tag names | Each tag has `name` |
| TAG-003 | Tag descriptions | Each tag has `description` |
| TAG-004 | Endpoint tags | All endpoints reference valid tags |
| TAG-005 | Tag grouping | Logical grouping (Auth, Keywords, etc.) |

### OAS-EXAMPLES: Example Values
| ID | Test | Description |
|----|------|-------------|
| EX-001 | Request examples | Request bodies have examples |
| EX-002 | Response examples | Responses have examples |
| EX-003 | Schema examples | Schemas have example values |
| EX-004 | Example format | Examples are valid JSON |

### OAS-DOCS: Documentation Generation
| ID | Test | Description |
|----|------|-------------|
| DOC-001 | Swagger UI compatible | Spec works with Swagger UI |
| DOC-002 | Redoc compatible | Spec works with Redoc |
| DOC-003 | No parsing errors | External tools parse spec |
| DOC-004 | Valid JSON output | YAML converts to valid JSON |

---

## Test Data

### Expected Security Schemes
```yaml
securitySchemes:
  BearerAuth:
    type: http
    scheme: bearer
    bearerFormat: JWT
    description: JWT token authentication
  ApiKeyAuth:
    type: apiKey
    in: header
    name: X-API-Key
    description: API key authentication
```

### Expected Error Schema
```yaml
ErrorResponse:
  type: object
  required:
    - message
    - status
  properties:
    message:
      type: string
    status:
      type: integer
    errors:
      type: array
      items:
        type: object
        properties:
          field:
            type: string
          message:
            type: string
```

### Expected Pagination Schema
```yaml
PaginationMeta:
  type: object
  properties:
    current_page:
      type: integer
    per_page:
      type: integer
    total:
      type: integer
    last_page:
      type: integer
```

---

## Dependencies
- `symfony/yaml` package (for YAML parsing)
- `league/openapi-psr7-validator` (optional, for validation)

---

## Acceptance Criteria Tests
| ID | Criteria | Test |
|----|----------|------|
| AC-001 | OpenAPI spec created | OAS-001 |
| AC-002 | All endpoints documented | EP-* tests |
| AC-003 | Schemas defined | SCH-* tests |
| AC-004 | Valid OpenAPI format | VAL-001 |
| AC-005 | Documentation generation ready | DOC-* tests |

---

## Total Tests
- OAS-STRUCTURE: 8 tests
- OAS-INFO: 6 tests
- OAS-SERVERS: 5 tests
- OAS-AUTH: 8 tests
- OAS-PATHS: 10 tests
- OAS-RESPONSES: 6 tests
- OAS-SCHEMAS: 12 tests
- OAS-ENDPOINTS: 90+ tests (covering all routes)
- OAS-VALIDATION: 8 tests
- OAS-SECURITY: 5 tests
- OAS-ERRORS: 8 tests
- OAS-TAGS: 5 tests
- OAS-EXAMPLES: 4 tests
- OAS-DOCS: 4 tests
- AC: 5 tests

**Total: 180+ test scenarios**

---

## Created
2026-03-08 by PreTester
