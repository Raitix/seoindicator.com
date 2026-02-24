# TEST-004-4: CSV Exporter Local Test Cases

## Test Plan

- **Purpose:** Ensure the `CSVExporter` functionality works correctly and meets the specified acceptance criteria.
- **Scope:** Covers unit tests, edge cases, and functional requirements.
- **Environment:** Local testing environment.
- **Prerequisites:** Code implemented and verified by Developer; Unit tests should already exist.

---

## Testing Checklist

1. **Header Validation**
   - Verify column headers: `Keyword`, `Current Position`, `Previous Position`, `Change`, `Search Engine`, `Location`, `Last Checked`.
   
2. **Data Row Validation**
   - Ensure data is exported in proper order.
   - Check for special character handling (e.g., commas, quotes, newlines).

3. **Encoding**
   - Validate UTF-8 encoding of the output file.
   - Ensure UTF-8 BOM is prepended for Excel compatibility.

4. **Missing Values**
   - Fields should display as `"N/A"` or `"Not ranked"` for missing values.
   
5. **Empty Dataset**
   - Confirm output contains only the header row when no data exists.

6. **Performance**
   - Validate performance with a large dataset (~1,000,000 rows).
   
7. **Integrity**
   - Ensure the generated file remains intact (no corruption).

8. **Validate against Acceptance Criteria (from TASK-4-4.md):**
   - Verify all points in the acceptance criteria have been implemented and are functional.

---

## Test Cases

### Unit Test Cases
- **Test Case ID:** `TEST-004-4-UT1`
  - **Description:** Validate proper generation of the CSV header.
  - **Expected Result:** Headers match the required columns in the correct order.

- **Test Case ID:** `TEST-004-4-UT2`
  - **Description:** Validate special character escaping.
  - **Expected Result:** Data with commas, quotes, and newlines is fully escaped.

- **Test Case ID:** `TEST-004-4-UT3`
  - **Description:** Validate missing data handling.
  - **Expected Result:** Missing fields display as `"N/A"`.

### Integration Test Cases
- **Test Case ID:** `TEST-004-4-IT1`
  - **Description:** Perform end-to-end data export.
  - **Steps:** Generate CSV from a sample report and inspect output file.
  - **Expected Result:** Report data is exported correctly; file opens in Excel.

### Edge Case Test Cases
- **Test Case ID:** `TEST-004-4-EC1`
  - **Description:** Export with an empty dataset.
  - **Expected Result:** Output contains only the header row.

- **Test Case ID:** `TEST-004-4-EC2`
  - **Description:** Export with a very large dataset (~1,000,000 rows).
  - **Expected Result:** Successfully generates within reasonable time (~1 min).

---
