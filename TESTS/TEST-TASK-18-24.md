# TEST-TASK-18-24: AriaChecker Indicator Tests

## Overview
Comprehensive test coverage for AriaChecker indicator that analyzes ARIA accessibility attributes.

## Test File Location
`backend/tests/Unit/Services/SEO/Checkers/AriaCheckerTest.php`

## Test Summary
| Category | Tests | Description |
|----------|-------|-------------|
| SI | 6 | Service Instantiation |
| LANDMARK | 10 | Landmark Role Checks |
| FORM | 12 | Form Label Validation |
| BUTTON | 10 | Button Accessibility |
| ICON_BTN | 8 | Icon-Only Button Detection |
| ARIA_HIDDEN | 6 | aria-hidden Misuse |
| SCORE | 8 | Score Calculation |
| SUGGEST | 10 | Suggestions Generation |
| ERROR | 5 | Error Handling |
| EDGE | 8 | Edge Cases |
| SEC | 3 | Security |
| AC | 10 | Acceptance Criteria |
| **Total** | **96** | |

## Test Categories

### SI: Service Instantiation (6 tests)
| Test ID | Description |
|---------|-------------|
| SI-001 | Service can be instantiated |
| SI-002 | Implements IndicatorCheckerInterface |
| SI-003 | id() returns 'aria' |
| SI-004 | name() returns 'ARIA Labels' |
| SI-005 | category() returns 'accessibility' |
| SI-006 | weight() returns 0.04 |

### LANDMARK: Landmark Role Checks (10 tests)
| Test ID | Description |
|---------|-------------|
| LANDMARK-001 | Detects main landmark |
| LANDMARK-002 | Detects nav landmark |
| LANDMARK-003 | Detects aside landmark |
| LANDMARK-004 | Detects header landmark |
| LANDMARK-005 | Detects footer landmark |
| LANDMARK-006 | Detects form landmark |
| LANDMARK-007 | Detects search landmark |
| LANDMARK-008 | Missing main landmark reduces score |
| LANDMARK-009 | Multiple landmarks counted correctly |
| LANDMARK-010 | Landmarks in details |

### FORM: Form Label Validation (12 tests)
| Test ID | Description |
|---------|-------------|
| FORM-001 | Input with label[for] passes |
| FORM-002 | Input with aria-label passes |
| FORM-003 | Input with aria-labelledby passes |
| FORM-004 | Input without label fails check |
| FORM-005 | Textarea label validation |
| FORM-006 | Select element label validation |
| FORM-007 | Checkbox label validation |
| FORM-008 | Radio button label validation |
| FORM-009 | Hidden input excluded |
| FORM-010 | Multiple inputs with labels |
| FORM-011 | Form labels counted in details |
| FORM-012 | Form without inputs handled |

### BUTTON: Button Accessibility (10 tests)
| Test ID | Description |
|---------|-------------|
| BUTTON-001 | Button with text passes |
| BUTTON-002 | Button with aria-label passes |
| BUTTON-003 | Button with aria-labelledby passes |
| BUTTON-004 | Button without accessible name fails |
| BUTTON-005 | Submit button with value passes |
| BUTTON-006 | Reset button validation |
| BUTTON-007 | Disabled button excluded |
| BUTTON-008 | Button with icon and sr-only text |
| BUTTON-009 | Link styled as button |
| BUTTON-010 | Button count in details |

### ICON_BTN: Icon-Only Button Detection (8 tests)
| Test ID | Description |
|---------|-------------|
| ICON_BTN-001 | Detects icon-only button without aria-label |
| ICON_BTN-002 | Icon button with aria-label passes |
| ICON_BTN-003 | SVG icon button detection |
| ICON_BTN-004 | Icon font button detection |
|