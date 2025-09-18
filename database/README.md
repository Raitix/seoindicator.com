# Database Management for SEOIndicator.com

## Overview
This directory contains database schema management, migration scripts, and documentation for the SEOIndicator.com project.

## Database Schema

### Current Production Tables

#### 1. `ip_requests_log` - Rate Limiting
```sql
CREATE TABLE ip_requests_log (
    ip_address VARCHAR(45) PRIMARY KEY,
    last_request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2. `data` - SEO Analysis Results
```sql
CREATE TABLE data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    score_has INT NOT NULL,
    score_max INT NOT NULL,
    dt_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dt_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(domain),
    INDEX(dt_created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Future Tables (Planned)

#### 3. `jobs` - Background Job Management
```sql
CREATE TABLE jobs (
    job_id VARCHAR(64) PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    normalized_domain VARCHAR(255) NULL,
    final_url VARCHAR(2048) NULL,
    page_content MEDIUMTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cancelled TINYINT(1) DEFAULT 0,
    INDEX(session_id),
    INDEX(domain),
    INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4. `job_results` - Individual Indicator Results
```sql
CREATE TABLE job_results (
    job_id VARCHAR(64) NOT NULL,
    indicator VARCHAR(64) NOT NULL,
    status ENUM('pending','running','done','error') NOT NULL DEFAULT 'pending',
    score TINYINT NULL,
    details MEDIUMTEXT NULL,
    suggestion MEDIUMTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(job_id, indicator),
    INDEX(job_id),
    INDEX(status),
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Environment Configuration

### Local Development
- **Host:** localhost
- **Database:** seo
- **User:** root
- **Config:** `db_config.php` (contains local credentials)

### Production (Hostinger)
- **Host:** [Production DB Host from Hostinger]
- **Database:** [Production DB Name]
- **User:** [Production DB User]
- **Config:** Update `db_config.php` for production

## Migration Strategy

### For Cursor Agents
1. **Read this documentation** to understand current schema
2. **Use migration scripts** in `/database/migrations/` for changes
3. **Test locally first** before applying to production
4. **Backup production** before any schema changes

### Migration Commands
```bash
# Apply all pending migrations
php database/migrate.php

# Apply specific migration
php database/migrate.php --version=001

# Check migration status
php database/migrate.php --status

# Rollback last migration
php database/migrate.php --rollback
```

## Safety Guidelines

### Before Making Changes
1. **Backup production database** via Hostinger control panel
2. **Test migrations locally** with production data copy
3. **Verify table structures** match expected schema
4. **Check for data dependencies** before dropping columns

### Production Deployment
1. **Run migrations during low-traffic periods**
2. **Monitor application logs** during deployment
3. **Have rollback plan** ready
4. **Test critical functionality** after deployment

## Connection Details for Cursor Agents

### Local Database
```php
// db_config.php (current - contains local credentials)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '[LOCAL_PASSWORD]');
define('DB_NAME', 'seo');
```

### Production Database
```php
// Update db_config.php for production
define('DB_HOST', '[HOSTINGER_DB_HOST]');
define('DB_USER', '[HOSTINGER_DB_USER]');
define('DB_PASS', '[HOSTINGER_DB_PASS]');
define('DB_NAME', '[HOSTINGER_DB_NAME]');
```

## Common Operations

### Schema Changes
- Use migration scripts in `/database/migrations/`
- Each migration should be reversible
- Test thoroughly before production deployment

### Data Migrations
- Use `/database/data-migrations/` for data transformations
- Always backup before data changes
- Verify data integrity after migration

### Index Management
- Add indexes for performance optimization
- Monitor query performance after index changes
- Consider impact on write operations

## Troubleshooting

### Connection Issues
1. Check Hostinger database settings
2. Verify remote connections are enabled
3. Confirm credentials are correct
4. Test connection with simple query

### Migration Failures
1. Check error logs in `/database/logs/`
2. Verify database permissions
3. Ensure no conflicting schema changes
4. Rollback if necessary

### Performance Issues
1. Monitor slow query log
2. Add appropriate indexes
3. Optimize table structures
4. Consider query optimization
