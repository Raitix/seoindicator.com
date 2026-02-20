# TASK-12-7: GSC Connection Controller

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Medium`

## Description
Develop `GSCConnectionController` to manage GSC OAuth connections via API endpoints.

## Endpoints
1. **POST /api/gsc/connect** - Initiate OAuth flow with authorization code.
2. **DELETE /api/gsc/disconnect/{propertyId}** - Remove connection and revoke tokens.
3. **GET /api/gsc/properties** - List all connected GSC properties for user.

## Code Example
```php
class GSCConnectionController extends Controller
{
    public function connect(Request $request);
    public function disconnect(int $propertyId);
    public function properties();
}
```

## Acceptance Criteria
- [ ] All connection endpoints implemented and documented.
- [ ] Authorization ensures users manage only their connections.
- [ ] Feature tests verify full connection lifecycle.

## Dependencies
- TASK-12-4: OAuth service must be ready.

## Effort Estimate
3-4 hours.

---

## Comments
### [2026-02-20 17:36] Agent: SpecSystemAnalyst
API controller for GSC connection management.