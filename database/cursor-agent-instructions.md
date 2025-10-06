# Cursor Agent Instructions for Database Management

## Quick Start for Cursor Agents

### 1. Database Connection
The project uses MySQL database with configuration in `db_config.php`:

```php
// Current local configuration (credentials in db_config.php)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '[LOCAL_PASSWORD]');
define('DB_NAME', 'seo');
```

### 2. Current Database Schema

#### Existing Tables (Production Ready)
- **`ip_requests_log`** - Rate limiting (ip_address, last_request_time)
- **`data`** - SEO analysis results (id, ip, domain, score_has, score_max, dt_created, dt_updated)

#### Planned Tables (Ready for Migration)
- **`jobs`** - Background job management
- **`job_results`** - Individual indicator results

### 3. How to Apply Database Changes

#### Option A: Use Migration System (Recommended)
```bash
# Check current status
php database/migrate.php status

# Apply all pending migrations
php database/migrate.php migrate

# Apply specific migration
php database/migrate.php migrate 001

# Create backup before changes
php database/migrate.php backup
```

#### Option B: Manual SQL Execution
```sql
-- Connect to database using credentials from db_config.php
-- Execute SQL from migration files in database/migrations/
```

### 4. For Production Deployment

#### Step 1: Update Production Credentials
Update `db_config.php` with Hostinger database credentials:
```php
define('DB_HOST', '[HOSTINGER_DB_HOST]');
define('DB_USER', '[HOSTINGER_DB_USER]');
define('DB_PASS', '[HOSTINGER_DB_PASS]');
define('DB_NAME', '[HOSTINGER_DB_NAME]');
```

#### Step 2: Apply Migrations
```bash
# Backup production database first
php database/migrate.php backup production_backup_$(date +%Y%m%d_%H%M%S).sql

# Apply migrations
php database/migrate.php migrate
```

#### Step 3: Verify Changes
```sql
-- Check tables exist
SHOW TABLES;

-- Verify schema
DESCRIBE jobs;
DESCRIBE job_results;
DESCRIBE api_logs;
```

### 5. Safety Checklist

#### Before Making Changes
- [ ] **Backup production database** via Hostinger control panel
- [ ] **Test migrations locally** with production data copy
- [ ] **Verify table structures** match expected schema
- [ ] **Check for data dependencies** before dropping columns

#### During Deployment
- [ ] **Run during low-traffic periods**
- [ ] **Monitor application logs** during deployment
- [ ] **Have rollback plan** ready
- [ ] **Test critical functionality** after deployment

### 6. Common Operations

#### Add New Table
1. Create migration file: `database/migrations/XXX_description.sql`
2. Create rollback file: `database/migrations/XXX_description_rollback.sql`
3. Test locally: `php database/migrate.php migrate`
4. Apply to production: `php database/migrate.php migrate`

#### Modify Existing Table
1. Create migration with ALTER statements
2. Include rollback with reverse ALTER statements
3. Test thoroughly before production

#### Add Indexes
```sql
-- In migration file
ALTER TABLE table_name ADD INDEX index_name (column_name);
```

#### Data Migration
1. Use `/database/data-migrations/` for data transformations
2. Always backup before data changes
3. Verify data integrity after migration

### 7. Troubleshooting

#### Connection Issues
1. Check Hostinger database settings
2. Verify remote connections are enabled
3. Confirm credentials are correct
4. Test connection with simple query

#### Migration Failures
1. Check error logs in `/database/logs/migration_errors.log`
2. Verify database permissions
3. Ensure no conflicting schema changes
4. Rollback if necessary: `php database/migrate.php rollback`

#### Performance Issues
1. Monitor slow query log
2. Add appropriate indexes
3. Optimize table structures
4. Consider query optimization

### 8. File Structure
```
database/
├── README.md                    # Complete documentation
├── migrate.php                  # Migration tool
├── cursor-agent-instructions.md # This file
├── migrations/                  # SQL migration files
│   ├── 001_create_jobs_table.sql
│   ├── 001_create_jobs_table_rollback.sql
│   ├── 002_create_job_results_table.sql
│   ├── 002_create_job_results_table_rollback.sql
│   ├── 003_create_api_logs_table.sql
│   └── 003_create_api_logs_table_rollback.sql
├── logs/                        # Migration logs
└── backups/                     # Database backups
```

### 9. Quick Commands Reference

```bash
# Status check
php database/migrate.php status

# Apply all migrations
php database/migrate.php migrate

# Rollback last migration
php database/migrate.php rollback

# Create backup
php database/migrate.php backup

# Apply specific migration
php database/migrate.php migrate 001
```

### 10. Production Database Credentials
**Note:** Update these with actual Hostinger credentials before production deployment:

```php
// Production configuration (update with real values)
define('DB_HOST', '[HOSTINGER_DB_HOST]');
define('DB_USER', '[HOSTINGER_DB_USER]');
define('DB_PASS', '[HOSTINGER_DB_PASSWORD]');
define('DB_NAME', '[HOSTINGER_DB_NAME]');
```

## Summary
This system provides a safe, versioned approach to database management. Always backup before changes, test locally first, and use the migration system for consistency between environments.
