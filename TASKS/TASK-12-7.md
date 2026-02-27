# TASK-12-7: GSC Connection Controller

## STATUS
`To Test Prod`

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
### [2026-02-20 19:16] ProjectManager: Prioritization Complete
- Priority: High (correct - exposes OAuth functionality to frontend)
- Risk: Medium (authorization logic, token revocation)
- Dependencies: TASK-12-4 (OAuth service)
- Execution order: After TASK-12-4 is complete
- Can be developed in parallel with TASK-12-5 (Data Sync)
- Status: To Develop

### [2026-02-20 17:36] Agent: SpecSystemAnalyst
API controller for GSC connection management.

### [2026-02-23] Developer: Implementation Complete
- Created `GSCConnectionController` with all required endpoints:
  - `POST /api/gsc/connect` - Initiate OAuth flow with authorization code
  - `DELETE /api/gsc/disconnect/{propertyId}` - Remove connection and revoke tokens  
  - `GET /api/gsc/properties` - List all connected GSC properties for user
- Updated routes in `routes/api.php`
- Fixed migration bug: `unsignedFloat` → `decimal` in GSC data table
- All API endpoint tests passing (GSCApiEndpointsTest)
- Authorization ensures users manage only their own connections
- Feature tests verify full connection lifecycle
- Status: To Test Locally
### [2026-02-22 16:03] Agent: LocalTester - Tests Passed
Ran: `php artisan test --filter=GSCApiEndpointsTest`
Result: 11 passed (13 assertions).
Endpoints verified: connect, disconnect, properties; auth enforced; tokens not exposed.
Status ? To Deploy.

### [2026-02-22 20:10] Agent: DevOps
Pre-deploy review complete. Ready to deploy TASK-12-7 to production via GitHub Actions/SSH per DEPLOYMENT-SETUP.md.
Pending approval to execute production deployment (external action). No deployment executed yet.

### [2026-02-22 22:43] Agent: DevOps
Lock acquired. Ready to deploy TASK-12-7. Awaiting explicit approval to run production deployment (external action) per DEPLOYMENT-SETUP.md.

### [2026-02-22 22:50] Agent: DevOps
Status -> In Deployment. Deployment not started yet; awaiting explicit approval to execute production deploy (external action).

### [2026-02-27 22:45] DevOps: Deployment Verified
- TASK-12-7 code (GSCConnectionController) already deployed to production
- Commit 0fd81f7 is ancestor of origin/main (verified via merge-base)
- Health check: https://seoindicator.com/api/health → 200 OK
- Status: To Test Prod

