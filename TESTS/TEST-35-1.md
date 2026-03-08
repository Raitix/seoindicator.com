# TEST-35-1: Database Migrations for Local SEO

## Task Reference
- **Task:** TASK-35-1
- **Specification:** SPEC-035
- **Feature:** FEAT-035 (Local SEO & Google Business Profile Tracking)

---

## Overview

Tests for database migrations that create 7 tables required for the Local SEO feature. Tests verify schema structure, column types, indexes, foreign keys, unique constraints, and rollback functionality.

---

## Test Files

| File | Type | Tests | Description |
|------|------|-------|-------------|
| `backend/tests/Feature/Database/Migrations/LocalSeoMigrationTest.php` | Feature | 91 | Migration schema and rollback tests |
| `backend/tests/Unit/Database/Migrations/LocalSeoMigrationStructureTest.php` | Unit | 42 | Column type and constraint verification |

**Total:** 133 tests

---

## Test Scenarios

### business_locations Table Tests (BL)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| BL-01 | Table exists after migration | Run migration | Table `business_locations` exists |
| BL-02 | Column: id (primary key) | Check column | `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| BL-03 | Column: user_id | Check column | `user_id` BIGINT UNSIGNED NOT NULL |
| BL-04 | Column: site_id | Check column | `site_id` BIGINT UNSIGNED NOT NULL |
| BL-05 | Column: gbp_place_id | Check column | `gbp_place_id` VARCHAR(255) NULL |
| BL-06 | Column: name | Check column | `name` VARCHAR(255) NOT NULL |
| BL-07 | Column: address | Check column | `address` VARCHAR(500) NOT NULL |
| BL-08 | Column: phone | Check column | `phone` VARCHAR(50) NULL |
| BL-09 | Column: website | Check column | `website` VARCHAR(500) NULL |
| BL-10 | Column: category | Check column | `category` VARCHAR(255) NULL |
| BL-11 | Column: latitude | Check column | `latitude` DECIMAL(10, 8) NULL |
| BL-12 | Column: longitude | Check column | `longitude` DECIMAL(11, 8) NULL |
| BL-13 | Column: gbp_connected | Check column | `gbp_connected` BOOLEAN DEFAULT FALSE |
| BL-14 | Column: settings | Check column | `settings` JSON NULL |
| BL-15 | Column: timestamps | Check columns | `created_at`, `updated_at` TIMESTAMP |
| BL-16 | Column: soft deletes | Check column | `deleted_at` TIMESTAMP NULL |
| BL-17 | Index: user_id | Check index | Index on `user_id` |
| BL-18 | Index: site_id | Check index | Index on `site_id` |
| BL-19 | Index: gbp_place_id | Check index | Index on `gbp_place_id` |
| BL-20 | Foreign key: user_id | Check FK | `user_id` → `users.id` ON DELETE CASCADE |
| BL-21 | Foreign key: site_id | Check FK | `site_id` → `sites.id` ON DELETE CASCADE |
| BL-22 | Table dropped on rollback | Rollback migration | Table `business_locations` does not exist |

### gbp_insights Table Tests (GI)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GI-01 | Table exists after migration | Run migration | Table `gbp_insights` exists |
| GI-02 | Column: id (primary key) | Check column | `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| GI-03 | Column: location_id | Check column | `location_id` BIGINT UNSIGNED NOT NULL |
| GI-04 | Column: views | Check column | `views` INT UNSIGNED DEFAULT 0 |
| GI-05 | Column: searches | Check column | `searches` INT UNSIGNED DEFAULT 0 |
| GI-06 | Column: calls | Check column | `calls` INT UNSIGNED DEFAULT 0 |
| GI-07 | Column: directions | Check column | `directions` INT UNSIGNED DEFAULT 0 |
| GI-08 | Column: website_clicks | Check column | `website_clicks` INT UNSIGNED DEFAULT 0 |
| GI-09 | Column: photos_views | Check column | `photos_views` INT UNSIGNED DEFAULT 0 |
| GI-10 | Column: insights_date | Check column | `insights_date` DATE NOT NULL |
| GI-11 | Column: created_at | Check column | `created_at` TIMESTAMP |
| GI-12 | Unique index: location_id + insights_date | Check index | UNIQUE on `(location_id, insights_date)` |
| GI-13 | Index: insights_date | Check index | Index on `insights_date` |
| GI-14 | Foreign key: location_id | Check FK | `location_id` → `business_locations.id` ON DELETE CASCADE |
| GI-15 | Table dropped on rollback | Rollback migration | Table `gbp_insights` does not exist |

### gbp_reviews Table Tests (GR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GR-01 | Table exists after migration | Run migration | Table `gbp_reviews` exists |
| GR-02 | Column: id (primary key) | Check column | `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| GR-03 | Column: location_id | Check column | `location_id` BIGINT UNSIGNED NOT NULL |
| GR-04 | Column: google_review_id | Check column | `google_review_id` VARCHAR(255) NOT NULL |
| GR-05 | Column: author_name | Check column | `author_name` VARCHAR(255) NULL |
| GR-06 | Column: author_photo | Check column | `author_photo` VARCHAR(500) NULL |
| GR-07 | Column: rating | Check column | `rating` TINYINT UNSIGNED NOT NULL |
| GR-08 | Column: review_text | Check column | `review_text` TEXT NULL |
| GR-09 | Column: review_text_sentiment | Check column | `review_text_sentiment` VARCHAR(20) NULL |
| GR-10 | Column: owner_response | Check column | `owner_response` TEXT NULL |
| GR-11 | Column: owner_response_date | Check column | `owner_response_date` TIMESTAMP NULL |
| GR-12 | Column: review_date | Check column | `review_date` TIMESTAMP NULL |
| GR-13 | Column: timestamps | Check columns | `created_at`, `updated_at` TIMESTAMP |
| GR-14 | Unique index: google_review_id | Check index | UNIQUE on `google_review_id` |
| GR-15 | Index: location_id | Check index | Index on `location_id` |
| GR-16 | Index: rating | Check index | Index on `rating` |
| GR-17 | Index: review_date | Check index | Index on `review_date` |
| GR-18 | Foreign key: location_id | Check FK | `location_id` → `business_locations.id` ON DELETE CASCADE |
| GR-19 | Table dropped on rollback | Rollback migration | Table `gbp_reviews` does not exist |

### local_rankings Table Tests (LR)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LR-01 | Table exists after migration | Run migration | Table `local_rankings` exists |
| LR-02 | Column: id (primary key) | Check column | `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| LR-03 | Column: location_id | Check column | `location_id` BIGINT UNSIGNED NOT NULL |
| LR-04 | Column: keyword_id | Check column | `keyword_id` BIGINT UNSIGNED NOT NULL |
| LR-05 | Column: search_location | Check column | `search_location` VARCHAR(255) NOT NULL |
| LR-06 | Column: search_lat | Check column | `search_lat` DECIMAL(10, 8) NULL |
| LR-07 | Column: search_lng | Check column | `search_lng` DECIMAL(11, 8) NULL |
| LR-08 | Column: local_pack_position | Check column | `local_pack_position` TINYINT UNSIGNED NULL |
| LR-09 | Column: local_organic_position | Check column | `local_organic_position` INT UNSIGNED NULL |
| LR-10 | Column: competitors_in_pack | Check column | `competitors_in_pack` JSON NULL |
| LR-11 | Column: checked_at | Check column | `checked_at` TIMESTAMP |
| LR-12 | Composite index: location_id + keyword_id | Check index | Index on `(location_id, keyword_id)` |
| LR-13 | Index: checked_at | Check index | Index on `checked_at` |
| LR-14 | Foreign key: location_id | Check FK | `location_id` → `business_locations.id` ON DELETE CASCADE |
| LR-15 | Foreign key: keyword_id | Check FK | `keyword_id` → `keywords.id` ON DELETE CASCADE |
| LR-16 | Table dropped on rollback | Rollback migration | Table `local_rankings` does not exist |

### local_competitors Table Tests (LC)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LC-01 | Table exists after migration | Run migration | Table `local_competitors` exists |
| LC-02 | Column: id (primary key) | Check column | `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| LC-03 | Column: location_id | Check column | `location_id` BIGINT UNSIGNED NOT NULL |
| LC-04 | Column: competitor_name | Check column | `competitor_name` VARCHAR(255) NOT NULL |
| LC-05 | Column: competitor_gbp_id | Check column | `competitor_gbp_id` VARCHAR(255) NULL |
| LC-06 | Column: competitor_address | Check column | `competitor_address` VARCHAR(500) NULL |
| LC-07 | Column: competitor_rating | Check column | `competitor_rating` DECIMAL(2, 1) NULL |
| LC-08 | Column: competitor_review_count | Check column | `competitor_review_count` INT UNSIGNED DEFAULT 0 |
| LC-09 | Column: timestamps | Check columns | `created_at`, `updated_at` TIMESTAMP |
| LC-10 | Index: location_id | Check index | Index on `location_id` |
| LC-11 | Foreign key: location_id | Check FK | `location_id` → `business_locations.id` ON DELETE CASCADE |
| LC-12 | Table dropped on rollback | Rollback migration | Table `local_competitors` does not exist |

### gbp_posts Table Tests (GP)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| GP-01 | Table exists after migration | Run migration | Table `gbp_posts` exists |
| GP-02 | Column: id (primary key) | Check column | `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| GP-03 | Column: location_id | Check column | `location_id` BIGINT UNSIGNED NOT NULL |
| GP-04 | Column: google_post_id | Check column | `google_post_id` VARCHAR(255) NOT NULL |
| GP-05 | Column: post_type | Check column | `post_type` VARCHAR(50) NOT NULL |
| GP-06 | Column: title | Check column | `title` VARCHAR(255) NULL |
| GP-07 | Column: content | Check column | `content` TEXT NULL |
| GP-08 | Column: media_url | Check column | `media_url` VARCHAR(500) NULL |
| GP-09 | Column: call_to_action | Check column | `call_to_action` VARCHAR(100) NULL |
| GP-10 | Column: call_to_action_url | Check column | `call_to_action_url` VARCHAR(500) NULL |
| GP-11 | Column: published_at | Check column | `published_at` TIMESTAMP NULL |
| GP-12 | Column: created_at | Check column | `created_at` TIMESTAMP |
| GP-13 | Unique index: google_post_id | Check index | UNIQUE on `google_post_id` |
| GP-14 | Index: location_id | Check index | Index on `location_id` |
| GP-15 | Foreign key: location_id | Check FK | `location_id` → `business_locations.id` ON DELETE CASCADE |
| GP-16 | Table dropped on rollback | Rollback migration | Table `gbp_posts` does not exist |

### local_seo_alerts Table Tests (LA)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| LA-01 | Table exists after migration | Run migration | Table `local_seo_alerts` exists |
| LA-02 | Column: id (primary key) | Check column | `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY |
| LA-03 | Column: user_id | Check column | `user_id` BIGINT UNSIGNED NOT NULL |
| LA-04 | Column: location_id | Check column | `location_id` BIGINT UNSIGNED NOT NULL |
| LA-05 | Column: alert_type | Check column | `alert_type` VARCHAR(50) NOT NULL |
| LA-06 | Column: severity | Check column | `severity` VARCHAR(20) NOT NULL |
| LA-07 | Column: title | Check column | `title` VARCHAR(255) NOT NULL |
| LA-08 | Column: message | Check column | `message` TEXT NOT NULL |
| LA-09 | Column: data | Check column | `data` JSON NULL |
| LA-10 | Column: is_read | Check column | `is_read` BOOLEAN DEFAULT FALSE |
| LA-11 | Column: created_at | Check column | `created_at` TIMESTAMP |
| LA-12 | Index: user_id | Check index | Index on `user_id` |
| LA-13 | Index: location_id | Check index | Index on `location_id` |
| LA-14 | Index: alert_type | Check index | Index on `alert_type` |
| LA-15 | Index: is_read | Check index | Index on `is_read` |
| LA-16 | Foreign key: user_id | Check FK | `user_id` → `users.id` ON DELETE CASCADE |
| LA-17 | Foreign key: location_id | Check FK | `location_id` → `business_locations.id` ON DELETE CASCADE |
| LA-18 | Table dropped on rollback | Rollback migration | Table `local_seo_alerts` does not exist |

### Rollback Tests (RB)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| RB-01 | All tables created | Run up migration | All 7 tables exist |
| RB-02 | All tables dropped | Rollback migration | All 7 tables do not exist |
| RB-03 | Fresh migration after rollback | Migrate again | All tables exist again |
| RB-04 | Multiple rollback cycles | Migrate, rollback, repeat | No errors, tables consistent |

### Integration Tests (INT)

| ID | Scenario | Input | Expected Output |
|----|----------|-------|-----------------|
| INT-01 | Foreign key cascade delete | Delete user | All related `business_locations` deleted |
| INT-02 | Foreign key cascade delete | Delete site | All related `business_locations` deleted |
| INT-03 | Foreign key cascade delete | Delete location | All `gbp_insights`, `gbp_reviews`, etc. deleted |
| INT-04 | Foreign key cascade delete | Delete keyword | All related `local_rankings` deleted |
| INT-05 | Unique constraint on gbp_insights | Insert duplicate (location_id, insights_date) | SQL error (duplicate entry) |
| INT-06 | Unique constraint on gbp_reviews | Insert duplicate google_review_id | SQL error (duplicate entry) |
| INT-07 | Unique constraint on gbp_posts | Insert duplicate google_post_id | SQL error (duplicate entry) |
| INT-08 | Latitude precision | Insert latitude 45.12345678 | Stored with 8 decimal precision |
| INT-09 | Longitude precision | Insert longitude -93.123456789 | Stored with 8 decimal precision |
| INT-10 | JSON column: settings | Insert valid JSON | Stored and retrieved correctly |
| INT-11 | JSON column: competitors_in_pack | Insert valid JSON | Stored and retrieved correctly |
| INT-12 | JSON column: data | Insert valid JSON | Stored and retrieved correctly |

---

## Column Type Mappings

| Column Type | MySQL Type | Laravel Type |
|-------------|------------|--------------|
| `id` | BIGINT UNSIGNED | `$table->id()` |
| `user_id` | BIGINT UNSIGNED | `$table->foreignId('user_id')` |
| `site_id` | BIGINT UNSIGNED | `$table->foreignId('site_id')` |
| `location_id` | BIGINT UNSIGNED | `$table->foreignId('location_id')` |
| `keyword_id` | BIGINT UNSIGNED | `$table->foreignId('keyword_id')` |
| `gbp_place_id` | VARCHAR(255) | `$table->string('gbp_place_id', 255)->nullable()` |
| `name` | VARCHAR(255) | `$table->string('name')` |
| `address` | VARCHAR(500) | `$table->string('address', 500)` |
| `phone` | VARCHAR(50) | `$table->string('phone', 50)->nullable()` |
| `website` | VARCHAR(500) | `$table->string('website', 500)->nullable()` |
| `category` | VARCHAR(255) | `$table->string('category')->nullable()` |
| `latitude` | DECIMAL(10, 8) | `$table->decimal('latitude', 10, 8)->nullable()` |
| `longitude` | DECIMAL(11, 8) | `$table->decimal('longitude', 11, 8)->nullable()` |
| `gbp_connected` | BOOLEAN | `$table->boolean('gbp_connected')->default(false)` |
| `settings` | JSON | `$table->json('settings')->nullable()` |
| `views` | INT UNSIGNED | `$table->unsignedInteger('views')->default(0)` |
| `searches` | INT UNSIGNED | `$table->unsignedInteger('searches')->default(0)` |
| `calls` | INT UNSIGNED | `$table->unsignedInteger('calls')->default(0)` |
| `directions` | INT UNSIGNED | `$table->unsignedInteger('directions')->default(0)` |
| `website_clicks` | INT UNSIGNED | `$table->unsignedInteger('website_clicks')->default(0)` |
| `photos_views` | INT UNSIGNED | `$table->unsignedInteger('photos_views')->default(0)` |
| `insights_date` | DATE | `$table->date('insights_date')` |
| `google_review_id` | VARCHAR(255) | `$table->string('google_review_id', 255)` |
| `author_name` | VARCHAR(255) | `$table->string('author_name')->nullable()` |
| `author_photo` | VARCHAR(500) | `$table->string('author_photo', 500)->nullable()` |
| `rating` | TINYINT UNSIGNED | `$table->unsignedTinyInteger('rating')` |
| `review_text` | TEXT | `$table->text('review_text')->nullable()` |
| `review_text_sentiment` | VARCHAR(20) | `$table->string('review_text_sentiment', 20)->nullable()` |
| `owner_response` | TEXT | `$table->text('owner_response')->nullable()` |
| `owner_response_date` | TIMESTAMP | `$table->timestamp('owner_response_date')->nullable()` |
| `review_date` | TIMESTAMP | `$table->timestamp('review_date')->nullable()` |
| `search_location` | VARCHAR(255) | `$table->string('search_location')` |
| `search_lat` | DECIMAL(10, 8) | `$table->decimal('search_lat', 10, 8)->nullable()` |
| `search_lng` | DECIMAL(11, 8) | `$table->decimal('search_lng', 11, 8)->nullable()` |
| `local_pack_position` | TINYINT UNSIGNED | `$table->unsignedTinyInteger('local_pack_position')->nullable()` |
| `local_organic_position` | INT UNSIGNED | `$table->unsignedInteger('local_organic_position')->nullable()` |
| `competitors_in_pack` | JSON | `$table->json('competitors_in_pack')->nullable()` |
| `checked_at` | TIMESTAMP | `$table->timestamp('checked_at')` |
| `competitor_name` | VARCHAR(255) | `$table->string('competitor_name')` |
| `competitor_gbp_id` | VARCHAR(255) | `$table->string('competitor_gbp_id')->nullable()` |
| `competitor_address` | VARCHAR(500) | `$table->string('competitor_address', 500)->nullable()` |
| `competitor_rating` | DECIMAL(2, 1) | `$table->decimal('competitor_rating', 2, 1)->nullable()` |
| `competitor_review_count` | INT UNSIGNED | `$table->unsignedInteger('competitor_review_count')->default(0)` |
| `google_post_id` | VARCHAR(255) | `$table->string('google_post_id', 255)` |
| `post_type` | VARCHAR(50) | `$table->string('post_type', 50)` |
| `title` | VARCHAR(255) | `$table->string('title')->nullable()` |
| `content` | TEXT | `$table->text('content')->nullable()` |
| `media_url` | VARCHAR(500) | `$table->string('media_url', 500)->nullable()` |
| `call_to_action` | VARCHAR(100) | `$table->string('call_to_action', 100)->nullable()` |
| `call_to_action_url` | VARCHAR(500) | `$table->string('call_to_action_url', 500)->nullable()` |
| `published_at` | TIMESTAMP | `$table->timestamp('published_at')->nullable()` |
| `alert_type` | VARCHAR(50) | `$table->string('alert_type', 50)` |
| `severity` | VARCHAR(20) | `$table->string('severity', 20)` |
| `message` | TEXT | `$table->text('message')` |
| `data` | JSON | `$table->json('data')->nullable()` |
| `is_read` | BOOLEAN | `$table->boolean('is_read')->default(false)` |
| `timestamps` | TIMESTAMP | `$table->timestamps()` |
| `soft_deletes` | TIMESTAMP | `$table->softDeletes()` |

---

## Test Execution

```bash
# Run all Local SEO migration tests
php artisan test --filter LocalSeoMigration

# Run specific test file
php artisan test tests/Feature/Database/Migrations/LocalSeoMigrationTest.php

# Run with coverage
php artisan test --filter LocalSeoMigration --coverage
```

---

## Coverage Targets

| Category | Target | Description |
|----------|--------|-------------|
| Table Creation | 100% | All 7 tables created |
| Column Types | 100% | All columns correct type |
| Indexes | 100% | All indexes created |
| Foreign Keys | 100% | All FK constraints created |
| Unique Constraints | 100% | All unique constraints enforced |
| Rollback | 100% | All tables dropped on rollback |
| Cascade Delete | 100% | All cascades work correctly |

---

## Dependencies

- `users` table (exists)
- `sites` table (exists)
- `keywords` table (exists)
- Laravel Schema Builder
- PHPUnit

---

## Notes for Developer

1. **Migration Order:** Tables must be created in order due to foreign key dependencies:
   1. `business_locations` (depends on users, sites)
   2. `gbp_insights` (depends on business_locations)
   3. `gbp_reviews` (depends on business_locations)
   4. `local_rankings` (depends on business_locations, keywords)
   5. `local_competitors` (depends on business_locations)
   6. `gbp_posts` (depends on business_locations)
   7. `local_seo_alerts` (depends on users, business_locations)

2. **Lat/Lng Precision:** DECIMAL(10, 8) for latitude (-90 to 90) and DECIMAL(11, 8) for longitude (-180 to 180) provides sufficient precision for GPS coordinates (up to ~1.1mm accuracy).

3. **JSON Columns:** Used for flexible data storage where schema may evolve:
   - `business_locations.settings` - Location-specific configuration
   - `local_rankings.competitors_in_pack` - Dynamic competitor data
   - `local_seo_alerts.data` - Alert-specific payload

4. **Soft Deletes:** Only on `business_locations` to preserve location data for historical analysis even after "deletion".

5. **Rollback Testing:** Always test rollback after any migration changes.

---

## Comments

### PreTester Session (2026-03-08 21:00 GMT+2)
- Created test documentation for TASK-35-1
- 133 tests covering all 7 tables
- Tests verify schema structure, columns, indexes, FKs, unique constraints, and rollback
- All acceptance criteria from task covered
- Ready for Developer implementation

---

*Created by PreTester agent on 2026-03-08*
