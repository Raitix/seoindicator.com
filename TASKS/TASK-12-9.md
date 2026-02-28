# TASK-12-9: GSC Connection Page Component

## STATUS
`To Test Locally`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Create the `GSCConnectionPage` React component for users to connect/disconnect Google Search Console properties via OAuth flow.

## Features
1. **Connect Button** - Initiates OAuth flow.
2. **Connected Properties List** - Shows all linked GSC properties.
3. **Disconnect Action** - Removes property connection.

## Code Example
```tsx
interface GSCConnectionPageProps {
  properties: GSCProperty[];
  onConnect: () => void;
  onDisconnect: (propertyId: number) => void;
}
```

## Acceptance Criteria
- [x] OAuth flow handled via redirect and callback.
- [x] Connected properties displayed with status indicators.
- [x] Disconnect action confirmed with modal.

## Dependencies
- TASK-12-7: Connection API endpoints.

## Effort Estimate
3-4 hours.

---

## Comments
### [2026-02-20 19:26] ProjectManager: Prioritization Complete
- Priority: Medium (correct - frontend depends on backend)
- Risk: Low (UI component, OAuth handled by backend)
- Dependencies: TASK-12-7 (Connection Controller API)
- Execution order: After TASK-12-7 is complete
- Can be developed in parallel with TASK-12-8 (Data Controller)
- Status: To Develop

### [2026-02-20 17:37] Agent: SpecSystemAnalyst
Frontend component for GSC OAuth connection management.

### [2026-02-28 22:45] Agent: Developer
**Implementation Complete**

**Frontend Changes:**
- Created `GSCConnectionPage` component at `app/settings/gsc/page.tsx`
- Created property selection page at `app/settings/gsc/select-property/page.tsx`
- Updated `lib/types/gsc.ts` with GSCProperty, connection request/response types
- Updated `lib/api/gsc.ts` with connection API functions (fetchGSCProperties, disconnectGSCProperty, getGSCOAuthUrl)

**Backend Changes:**
- Extended `GSCConnectionController` with OAuth redirect/callback endpoints:
  - GET `/api/gsc/oauth/redirect` - Initiates OAuth flow
  - GET `/api/gsc/oauth/callback` - Handles Google callback
  - POST `/api/gsc/oauth/select-property` - Selects property when multiple available
  - GET `/api/gsc/oauth/available-properties` - Lists available properties from session
- Added CSRF protection via state parameter stored in session
- Auto-connects single property, shows selection page for multiple

**Features:**
- Connect button initiates OAuth redirect flow
- Connected properties list with status badges (active/expired/revoked)
- Disconnect modal with confirmation
- OAuth callback handling with success/error banners
- Multiple property selection support

**Build Status:** Frontend build passes (Next.js 16.1.6)
**Test Status:** Backend tests pass (117 passed, 306 assertions)

**Status:** To Test Locally