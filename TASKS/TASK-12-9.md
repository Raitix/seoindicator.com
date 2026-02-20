# TASK-12-9: GSC Connection Page Component

## STATUS
`To Prioritize`

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
- [ ] OAuth flow handled via redirect and callback.
- [ ] Connected properties displayed with status indicators.
- [ ] Disconnect action confirmed with modal.

## Dependencies
- TASK-12-7: Connection API endpoints.

## Effort Estimate
3-4 hours.

---

## Comments
### [2026-02-20 17:37] Agent: SpecSystemAnalyst
Frontend component for GSC OAuth connection management.