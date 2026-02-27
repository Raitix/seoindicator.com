# TASK-16-4: Tool Embedding System

## STATUS
`To Write Tests`

## PRIORITY
`High`

## RISK
`Low`

## Related
- Feature: [FEAT-016](../FEATURES/FEAT-016.md)
- Specification: [SPEC-016](../SPECIFICATIONS/SPEC-016.md)

---

## Description
Create the embedding system that allows tools to be embedded within SEO guide pages. This includes a reusable tool widget component, URL input handling, loading states, and result display.

---

## Acceptance Criteria
- [ ] Create reusable `<ToolWidget>` React component
- [ ] Support URL input with validation
- [ ] Display loading state during analysis
- [ ] Show results in organized, readable format
- [ ] Handle errors gracefully with user-friendly messages
- [ ] Support tool configuration (which tool to show)
- [ ] Add "Analyze Another" button to reset
- [ ] Responsive design for mobile/tablet/desktop
- [ ] Embed tools in relevant guide pages:
  - Meta Tag Analyzer → /seo-guides/meta-description
  - Keyword Density → /seo-guides/keyword-optimization
- [ ] Unit tests for component behavior

---

## Technical Details

### Component Props
```typescript
interface ToolWidgetProps {
  tool: 'meta-tag-analyzer' | 'keyword-density' | 'mobile-friendly' | 'social-preview';
  title: string;
  description: string;
  placeholder?: string;
  showHelp?: boolean;
}
```

### Embedding Pattern
```tsx
// In guide page MDX or React component
<ToolWidget 
  tool="meta-tag-analyzer" 
  title="Try It: Meta Tag Analyzer"
  description="Enter any URL to analyze its meta tags instantly"
/>
```

### Loading States
1. Idle - Show URL input
2. Loading - Show spinner with "Analyzing..." text
3. Success - Show results
4. Error - Show error message with retry button

### Error Messages
| Error Code | User Message |
|------------|--------------|
| INVALID_URL | "Please enter a valid URL (e.g., https://example.com)" |
| FETCH_FAILED | "Could not access the page. Please check the URL and try again." |
| TIMEOUT | "Analysis took too long. Please try again." |
| RATE_LIMITED | "You've made too many requests. Please wait a moment." |

---

## Dependencies
- TASK-16-2 (Meta Tag Analyzer)
- TASK-16-3 (Keyword Density Checker)

---

## Effort Estimate
**Medium** (4-6 hours)

---

## Comments
_Agent communication log_

### [2026-02-27] Agent: SpecSystemAnalyst
Created task. This is the frontend integration task that makes tools available in guide pages.
