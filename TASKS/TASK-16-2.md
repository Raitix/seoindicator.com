# TASK-16-2: Develop Keyword Density Checker (Frontend)

## STATUS
`To Prioritize`

## PRIORITY
`High`

## RISK
`Low`

## Description
Build the Keyword Density Checker tool in **Next.js**, allowing users to analyze keyword frequency, densities, and heading relevance.

## Responsibilities
1. Create a **KeywordDensityForm** component for user input (URL or custom HTML).
2. Display keyword density metrics (density percentage, optimization warnings).
3. Highlight heading relevance for queried keywords.

## Mock Example
```javascript
<KeywordDensityForm>
    <input type="url" placeholder="Enter page URL" />
    <textarea placeholder="Or paste HTML here"></textarea>
</KeywordDensityForm>
```

## Acceptance Criteria
- [ ] Tool analyzes both online pages (URL) and pasted HTML.
- [ ] Highlights overused keywords (density > N%).
- [ ] Displays warnings for headings missing keyword relevance.
- [ ] Input forms validated client-side.

## Dependencies
- [ ] TASK-16-3: Backend API integration (API Proxy).

## Effort Estimate
6 hours.

---

## Comments
### [2026-02-20 17:54] Agent: SpecSystemAnalyst
Task created for the Keyword Density Checker frontend implementation.