# TASK-9-2: Annotation Category Enum

## STATUS
`To Prioritize`

## PRIORITY
`Medium`

## RISK
`Low`

## Description
Implement `AnnotationCategory` as a PHP enum to define categories of annotations (e.g., "Content Update", "Algorithm Update"). Align categories with frontend dropdown options.

## Development Steps
1. Create the enum class:
```php
enum AnnotationCategory: string
{
    case CONTENT_UPDATE = 'content_update';
    case LINK_BUILDING = 'link_building';
    case TECHNICAL_FIX = 'technical_fix';
    case ALGORITHM_UPDATE = 'algorithm_update';
    case COMPETITOR_ACTION = 'competitor_action';
    case SEASONAL = 'seasonal';
    case MANUAL_ACTION = 'manual_action';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::CONTENT_UPDATE => 'Content Update',
            self::LINK_BUILDING => 'Link Building',
            self::TECHNICAL_FIX => 'Technical Fix',
            self::ALGORITHM_UPDATE => 'Algorithm Update',
            self::COMPETITOR_ACTION => 'Competitor Action',
            self::SEASONAL => 'Seasonal',
            self::MANUAL_ACTION => 'Manual Action',
            self::OTHER => 'Other',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::CONTENT_UPDATE => '✏️',
            self::LINK_BUILDING => '🔗',
            self::TECHNICAL_FIX => '🔧',
            self::ALGORITHM_UPDATE => '🤖',
            self::COMPETITOR_ACTION => '👀',
            self::SEASONAL => '📅',
            self::MANUAL_ACTION => '⚠️',
            self::OTHER => '📝',
        };
    }
}
```

2. Align frontend categories dropdown with the backend enum.

## Acceptance Criteria
- [ ] PHP enum defines categories matching specification.
- [ ] Category `label` and `icon` methods support dynamic rendering.

## Effort Estimate
1-2 hours.

---

## Comments
### [2026-02-20 17:50] Agent: SpecSystemAnalyst
Created task for defining annotation categories and ensuring alignment between backend and frontend.