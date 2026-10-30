# TEST-19-1: Database Migration - Analysis Jobs Tables

## Overview
Test suite for validating the `analysis_jobs` and `analysis_job_results` database migrations.

---

## Test File
`tests/Unit/Migrations/CreateAnalysisJobsTableTest.php`

---

## Test Scenarios

### 1. Schema Structure Tests

#### 1.1 analysis_jobs Table Structure
- [ ] Table exists after migration
- [ ] Primary key `id` (bigint unsigned, auto-increment)
- [ ] `user_id` column (bigint unsigned, not null)
- [ ] `site_id` column (bigint unsigned, not null)
- [ ] `status` column (enum: pending, running, completed, cancelled, failed, default: pending)
- [ ] `total_indicators` column (int unsigned, not null, default: 0)
- [ ] `completed_indicators` column (int unsigned, not null, default: 0)
- [ ] `failed_indicators` column (int unsigned, not null, default: 0)
- [ ] `progress_percent` column (tinyint unsigned, not null, default: 0)
- [ ] `started_at` column (timestamp, nullable)
- [ ] `completed_at` column (timestamp, nullable)
- [ ] `cancelled_at` column (timestamp, nullable)
- [ ] `error_message` column (text, nullable)
- [ ] `created_at` column (timestamp, default: CURRENT_TIMESTAMP)
- [ ] `updated_at` column (timestamp, default: CURRENT_TIMESTAMP ON UPDATE)

#### 1.2 analysis_job_results Table Structure
- [ ] Table exists after migration
- [ ] Primary key `id` (bigint unsigned, auto-increment)
- [ ] `job_id` column (bigint unsigned, not null)
- [ ] `indicator_slug` column (varchar 100, not null)
- [ ] `indicator_name` column (varchar 255, not null)
- [ ] `status` column (enum: pending, running, completed, failed, skipped, default: pending)
- [ ] `score` column (decimal 5,2, nullable)
- [ ] `max_score` column (decimal 5,2, nullable)
- [ ] `percentage` column (tinyint unsigned, nullable)
- [ ] `data` column (json, nullable)
- [ ] `recommendations` column (json, nullable)
- [ ] `error_message` column (text, nullable)
- [ ] `started_at` column (timestamp, nullable)
- [ ] `completed_at` column (timestamp, nullable)
- [ ] `processing_time_ms` column (int unsigned, nullable)
- [ ] `created_at` column (timestamp)
- [ ] `updated_at` column (timestamp)

### 2. Foreign Key Constraints

#### 2.1 analysis_jobs Foreign Keys
- [ ] `user_id` references `users.id` with CASCADE delete
- [ ] `site_id` references `sites.id` with CASCADE delete

#### 2.2 analysis_job_results Foreign Keys
- [ ] `job_id` references `analysis_jobs.id` with CASCADE delete

#### 2.3 Cascade Delete Behavior
- [ ] Deleting user cascades to their analysis_jobs
- [ ] Deleting site cascades to its analysis_jobs
- [ ] Deleting job cascades to its results

### 3. Indexes

#### 3.1 analysis_jobs Indexes
- [ ] Index on `user_id`
- [ ] Index on `site_id`
- [ ] Index on `status`
- [ ] Index on `created_at`

#### 3.2 analysis_job_results Indexes
- [ ] Index on `job_id`
- [ ] Index on `status`
- [ ] Unique constraint on `(job_id, indicator_slug)`

### 4. Enum Validation

#### 4.1 analysis_jobs Status Enum
- [ ] Accepts 'pending' value
- [ ] Accepts 'running' value
- [ ] Accepts 'completed' value
- [ ] Accepts 'cancelled' value
- [ ] Accepts 'failed' value
- [ ] Rejects invalid status values

#### 4.2 analysis_job_results Status Enum
- [ ] Accepts 'pending' value
- [ ] Accepts 'running' value
- [ ] Accepts 'completed' value
- [ ] Accepts 'failed' value
- [ ] Accepts 'skipped' value
- [ ] Rejects invalid status values

### 5. Rollback Tests

#### 5.1 Migration Down
- [ ] `analysis_job_results` table dropped on rollback
- [ ] `analysis_jobs` table dropped on rollback
- [ ] Migration can be re-run after rollback

### 6. Data Integrity Tests

#### 6.1 JSON Column Storage
- [ ] `data` column stores valid JSON
- [ ] `data` column stores null
- [ ] `recommendations` column stores valid JSON
- [ ] `recommendations` column stores null
- [ ] JSON is retrievable as array

#### 6.2 Decimal Precision
- [ ] `score` column stores values like 85.50
- [ ] `max_score` column stores values like 100.00
- [ ] Decimal values preserve precision

#### 6.3 Unique Constraint Enforcement
- [ ] Duplicate (job_id, indicator_slug) rejected
- [ ] Same indicator_slug allowed for different job_id

### 7. Edge Cases

#### 7.1 Null Handling
- [ ] Nullable columns accept null
- [ ] Not null columns reject null

#### 7.2 Default Values
- [ ] `status` defaults to 'pending'
- [ ] `total_indicators` defaults to 0
- [ ] `completed_indicators` defaults to 0
- [ ] `failed_indicators` defaults to 0
- [ ] `progress_percent` defaults to 0

#### 7.3 Boundary Values
- [ ] `progress_percent` accepts 0
- [ ] `progress_percent` accepts 100
- [ ] `progress_percent` rejects > 100 (tinyint unsigned max 255)
- [ ] `percentage` accepts 0
- [ ] `percentage` accepts 100
- [ ] `percentage` accepts null

### 8. Security Tests

#### 8.1 SQL Injection Prevention
- [ ] Indicator slug with SQL characters handled safely
- [ ] Error message with SQL characters handled safely
- [ ] Indicator name with special characters handled safely

#### 8.2 XSS Prevention
- [ ] Indicator name with script tags stored safely
- [ ] Error message with script tags stored safely

---

## Test Implementation Notes

- Use Laravel's `Schema::hasTable()` and `Schema::hasColumn()` for structure tests
- Use `DB::statement()` to query `information_schema` for index verification
- Test with `RefreshDatabase` trait for isolation
- Use model factories for test data creation
- Verify foreign key constraints with actual delete operations

---

## Dependencies
- users table must exist
- sites table must exist

---

## Coverage Target
- 35+ test methods
- 80%+ code coverage on migration file

---

*Created by PreTester for TASK-19-1*
