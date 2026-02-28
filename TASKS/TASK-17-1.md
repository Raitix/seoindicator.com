# TASK-17-1: Database Migration - User Onboarding Table

## STATUS
`Done`

## PRIORITY
`High`

## RISK
`Low`

## Specification
- **SPEC-017**: User Onboarding & Activation Flow

## Dependencies
- None (standalone database migration)

## Description
Create the `user_onboarding` database table to track user onboarding progress. This table stores the state of each user's onboarding journey, including their current step, completed steps, and data collected during the wizard.

## Acceptance Criteria
- [x] Migration creates `user_onboarding` table with all required columns
- [x] `id` column as BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT
- [x] `user_id` column as BIGINT UNSIGNED NOT NULL with FK to users.id ON DELETE CASCADE
- [x] `project_id` column as BIGINT UNSIGNED NULL with FK to projects.id ON DELETE SET NULL
- [x] `current_step` column as TINYINT UNSIGNED DEFAULT 1
- [x] `completed_steps` column as JSON NULL
- [x] `website_url` column as VARCHAR(500) NULL
- [x] `first_keyword` column as VARCHAR(255) NULL
- [x] `keyword_id` column as BIGINT UNSIGNED NULL with FK to keywords.id ON DELETE SET NULL
- [x] `alert_preferences` column as JSON NULL
- [x] `completed_at` column as TIMESTAMP NULL
- [x] `skipped_at` column as TIMESTAMP NULL
- [x] `created_at` and `updated_at` timestamps
- [x] Unique constraint on `user_id` (one onboarding per user)
- [x] Index on `completed_at`
- [x] Migration runs successfully in both SQLite (dev) and MySQL (prod)
- [x] Model created with proper fillable/casts properties
- [x] Factory created for testing

## Effort Estimate
`S` (2-4 hours)

## Technical Details

### Migration Schema
```sql
CREATE TABLE user_onboarding (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    current_step TINYINT UNSIGNED DEFAULT 1,
    completed_steps JSON NULL,
    website_url VARCHAR(500) NULL,
    first_keyword VARCHAR(255) NULL,
    keyword_id BIGINT UNSIGNED NULL,
    alert_preferences JSON NULL,
    completed_at TIMESTAMP NULL,
    skipped_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE SET NULL,
    
    UNIQUE KEY idx_user_onboarding_user (user_id),
    INDEX idx_user_onboarding_completed (completed_at)
);
```

### Model Properties
- `$fillable`: user_id, project_id, current_step, completed_steps, website_url, first_keyword, keyword_id, alert_preferences, completed_at, skipped_at
- `$casts`: completed_steps => 'array', alert_preferences => 'array', completed_at => 'datetime', skipped_at => 'datetime'

## Testing Requirements
- Unit test for model creation
- Unit test for JSON casting
- Unit test for user relationship
- Feature test for migration rollback

## Notes
This is the foundation task for the onboarding feature. All other tasks depend on this database schema.

---

## Comments

### 2026-02-28 13:23 - DevOps
- Verified deployment: Migration and model already deployed to production in commit `e7f3fbb`
- All acceptance criteria verified:
  - Migration file: `backend/database/migrations/2026_02_28_100000_create_user_onboarding_table.php`
  - Model file: `backend/app/Models/UserOnboarding.php`
  - Factory file: `backend/database/factories/UserOnboardingFactory.php`
- Status updated to Done

---

*Created by SpecSystemAnalyst on 2026-02-28*
