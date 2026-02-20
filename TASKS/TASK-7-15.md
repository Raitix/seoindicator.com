# TASK-7-15: Report Enhancement for SERP Features

## STATUS
`To Prioritize`

## PRIORITY
`Low`

## RISK
`Low`

## Related
- Feature: FEAT-007
- Specification: SPEC-007
- Test: TESTS/SPEC-7-15.md

## Description
Enhance the reporting system (FEAT-004) to include SERP feature data. Reports should show feature statistics and allow filtering by feature presence.

## Acceptance Criteria
- [ ] PDF reports include SERP feature section (if enabled in template)
- [ ] Feature section shows: total features, breakdown by type, recent gains/losses
- [ ] CSV export includes serp_features column
- [ ] Report template can enable/disable feature section
- [ ] Feature icons rendered in PDF
- [ ] Feature presence shown in keyword table within report

## Implementation Notes
```typescript
// In PDF report generator
async renderSERPFeatures(doc: PDFDocument, data: ReportData): Promise<void> {
  if (!data.serpFeatures) return;
  
  doc.addPage();
  doc.fontSize(18).text('SERP Feature Summary');
  doc.moveDown();
  
  // Feature counts
  const { featureCounts, recentChanges } = data.serpFeatures;
  
  doc.fontSize(12).text('Features Overview:', { underline: true });
  for (const [type, count] of Object.entries(featureCounts)) {
    const meta = SERP_FEATURE_META[type as SERPFeatureType];
    doc.text(`${meta.icon} ${meta.name}: ${count} keywords`);
  }
  
  doc.moveDown();
  doc.text('Recent Changes:', { underline: true });
  for (const gain of recentChanges.gained) {
    doc.text(`🏆 Gained ${gain.feature} for "${gain.keyword}"`);
  }
  for (const loss of recentChanges.lost) {
    doc.text(`⚠️ Lost ${loss.feature} for "${loss.keyword}"`);
  }
}

// In CSV export
function addSERPFeatureColumn(row: string[], features: SERPFeature[]): string[] {
  const featureStr = features
    .filter(f => f.isPresent)
    .map(f => SERP_FEATURE_META[f.type].icon)
    .join('');
  return [...row, featureStr];
}
```

## Dependencies
- TASK-7-11 (Summary endpoint)
- FEAT-004 (Report system)

## Effort Estimate
Medium (3-4 hours)

---

## Comments
_Agent communication log_

### [2026-02-20 16:58] Agent: SpecSystemAnalyst
Created task for report enhancement. Post-MVP but adds value to reports. Priority Low as reports work without it.
