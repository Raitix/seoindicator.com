# TASK-35-1: Database Migrations for Local SEO

## STATUS
`In Development`

## SPECIFICATION
SPEC-035: Local SEO & Google Business Profile Tracking

## PRIORITY
`High`

## RISK
`Medium`

## Description
Create database migrations for all 7 tables required by the Local SEO feature: business_locations, gbp_insights, gbp_reviews, local_rankings, local_competitors, gbp_posts, and local_seo_alerts.

## Acceptance Criteria
- [ ] Migration for `business_locations` table with columns:
  - id, user_id, site_id, gbp_place_id, name, address, phone, website, category
  - latitude, longitude, gbp_connected, settings (JSON)
  - Timestamps and soft deletes
  - Indexes on user_id, site_id, gbp_place_id
  - Foreign keys to users and sites
- [ ] Migration for `gbp_insights` table with columns:
  - id, location_id, views, searches, calls, directions, website_clicks, photos_views, insights_date
  - Unique constraint on (location_id, insights_date)
  - Foreign key to business_locations
- [ ] Migration for `gbp_reviews` table with columns:
  - id, location_id, google_review_id, author_name, author_photo, rating, review_text
  - review_text_sentiment, owner_response, owner_response_date, review_date
  - Unique index on google_review_id
  - Indexes on location_id, rating, review_date
- [ ] Migration for `local_rankings` table with columns:
  - id, location_id, keyword_id, search_location, search_lat, search_lng
  - local_pack_position, local_organic_position, competitors_in_pack (JSON), checked_at
  - Indexes on (location_id, keyword_id), checked_at
- [ ] Migration for `local_competitors` table with columns:
  - id, location_id, competitor_name, competitor_gbp_id, competitor_address
  - competitor_rating, competitor_review_count
  - Index on location_id
- [ ] Migration for `gbp_posts` table with columns:
  - id, location_id, google_post_id, post_type, title, content, media_url
  - call_to_action, call_to_action_url, published_at
  - Unique index on google_post_id
- [ ] Migration for `local_seo_alerts` table with columns:
  - id, user_id, location_id, alert_type, severity, title, message, data (JSON)
  - is_read, timestamps
  - Indexes on user_id, location_id, alert_type, is_read
- [ ] All migrations run successfully without errors
- [ ] Rollback tested for each migration

## Dependencies
- None (foundation task)

## Effort Estimate
4 hours

## Technical Notes
- Use Laravel's migration conventions
- DECIMAL for lat/lng to maintain precision
- JSON columns for flexible data storage (settings, competitors_in_pack, data)
- Soft deletes on business_locations for data preservation
- Consider adding composite indexes for common query patterns

---

*Created by SpecSystemAnalyst on 2026-03-08*

## Comments

### ProjectManager Session (2026-03-08 20:28 GMT+2)
- **Priority:** HIGH (confirmed) - Foundation task enabling ALL 23 downstream tasks in SPEC-035
- **Risk:** MEDIUM (confirmed) - Standard Laravel migrations, 7 tables with relationships/indexes
- **Dependencies:** None - Foundation task, users/sites/keywords tables exist ✅
- **Downstream Impact:** Enables TASK-35-2 through TASK-35-24 (entire feature pipeline)

**Dependency Chain Analysis:**
```
TASK-35-1 (Migrations) 
  → TASK-35-2 (BusinessLocation Model)
  → TASK-35-3 (GBP Models)
  → TASK-35-4 (LocalRanking Model)
  → TASK-35-5+ (Services, APIs, Frontend)
```

**Technical Assessment:**
- 7 tables: business_locations, gbp_insights, gbp_reviews, local_rankings, local_competitors, gbp_posts, local_seo_alerts
- DECIMAL(10,8)/DECIMAL(11,8) for lat/lng precision ✅
- JSON columns for flexible data (settings, competitors_in_pack, data) ✅
- Soft deletes on business_locations for data preservation ✅
- Composite indexes for common query patterns ✅
- Foreign keys with ON DELETE CASCADE for referential integrity ✅

**TDD Ready:** PreTester CAN design tests immediately:
1. Migration creates all 7 tables
2. Indexes are created correctly
3. Foreign keys reference correct tables
4. Unique constraints enforced
5. Column types match specification
6. Rollback removes all tables cleanly

**Key Lesson:** Foundation migration tasks that enable entire feature pipelines should be HIGH priority because:
1. They have no dependencies (can start immediately)
2. They enable all downstream work (models, services, APIs)
3. Tests can verify schema structure before any business logic
4. Database schema is the single source of truth for data models

**Status:** `To Write Tests` - Ready for PreTester to design migration tests

### PreTester Session (2026-03-08 21:00 GMT+2)
- **Status:** `To Develop` - Tests designed and ready for Developer
- **Test Files Created:**
  - `TESTS/TEST-35-1.md` - Test documentation with 133 test scenarios
  - `backend/tests/Feature/Database/Migrations/LocalSeoMigrationTest.php` - 91 feature tests
  - `backend/tests/Unit/Database/Migrations/LocalSeoMigrationStructureTest.php` - 42 unit tests
- **Test Coverage:**
  - All 7 tables verified for existence after migration
  - All columns verified for correct type, length, nullable, default values
  - All indexes verified (single, composite, unique)
  - All foreign keys verified with ON DELETE CASCADE
  - JSON columns verified for correct type
  - Decimal precision verified for lat/lng columns (10,8 and 11,8)
  - Soft deletes verified on business_locations
  - Timestamps verified on all appropriate tables
  - Rollback tests included (tables dropped cleanly)

**Test Organization:**
| Table | Tests | Coverage |
|-------|-------|----------|
| business_locations | 22 | BL-01 to BL-22 |
| gbp_insights | 15 | GI-01 to GI-15 |
| gbp_reviews | 19 | GR-01 to GR-19 |
| local_rankings | 16 | LR-01 to LR-16 |
| local_competitors | 12 | LC-01 to LC-12 |
| gbp_posts | 16 | GP-01 to GP-16 |
| local_seo_alerts | 18 | LA-01 to LA-18 |
| Rollback/Integration | 5 | RB-01 to RB-04, INT-08 to INT-12 |

**Notes for Developer:**
1. Run tests with: `php artisan test --filter LocalSeoMigration`
2. Integration tests (INT-08 to INT-12) are skipped pending model implementation
3. Tests use Doctrine Schema Manager for introspection
4. Column length assertions verify VARCHAR lengths
5. Decimal precision assertions verify (precision, scale)

**Key Decisions:**
- Used DECIMAL(10,8) for latitude (range -90 to 90)
- Used DECIMAL(11,8) for longitude (range -180 to 180)
- JSON columns allow flexible data storage
- Soft deletes only on business_locations (preserve historical data)
- ON DELETE CASCADE on all foreign keys for referential integrity
