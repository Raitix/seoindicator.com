# SEOIndicator.com Setup Instructions

## Issue Fixed: Domain Submission Not Working

The issue was that two critical configuration files were missing:
- `db_config.php` - Database connection settings
- `config.php` - API keys and site settings

## Files Created

✅ **db_config.php** - Database configuration file
✅ **config.php** - API keys and site settings
✅ **setup_database.sql** - Database schema
✅ **setup_database.php** - Database setup script

## Next Steps

### 1. Database Setup

You need to run the database setup script to create the required tables:

```bash
php setup_database.php
```

This will create the following tables:
- `ip_requests_log` - For rate limiting
- `jobs` - For storing analysis jobs
- `job_results` - For storing individual indicator results
- `data` - For general data logging

### 2. API Keys Configuration

Edit `config.php` and add your API keys:

```php
// OpenRouter API Configuration
define('OPENROUTER_API_KEY', 'your_actual_openrouter_api_key_here');

// Google PageSpeed Insights API Configuration
define('GOOGLE_PAGESPEED_API_KEY', 'your_actual_google_pagespeed_api_key_here');
```

### 3. Database Configuration

Edit `db_config.php` and update the database settings:

```php
// For local development (XAMPP/WAMP)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'seoindicator');

// For production (Hostinger)
// define('DB_HOST', 'your_hostinger_db_host');
// define('DB_USER', 'your_hostinger_db_username');
// define('DB_PASS', 'your_hostinger_db_password');
// define('DB_NAME', 'your_hostinger_db_name');
```

### 4. Test the Application

After completing the setup:

1. Make sure your web server (Apache/Nginx) is running
2. Make sure MySQL/MariaDB is running
3. Run the database setup script: `php setup_database.php`
4. Visit your application in the browser
5. Try submitting a domain for analysis

## What Was Fixed

The domain submission was failing because:

1. **Missing db_config.php**: The application couldn't connect to the database
2. **Missing config.php**: API keys and configuration constants were undefined
3. **Missing database tables**: The required tables for jobs and results didn't exist

## Database Schema

The application uses these main tables:

- **jobs**: Stores analysis job information
- **job_results**: Stores individual SEO indicator results
- **ip_requests_log**: Implements rate limiting (1 request per minute per IP)
- **data**: Legacy logging table

## Troubleshooting

If you still have issues:

1. Check PHP error logs
2. Verify database connection settings
3. Ensure all required PHP extensions are installed (mysqli, curl, json)
4. Check that the web server has write permissions to the application directory

## Production Deployment

For production deployment on Hostinger:

1. Update `db_config.php` with your Hostinger database credentials
2. Update `config.php` with your actual API keys
3. Set `display_errors` to `0` in `config.php`
4. Upload all files to your hosting account
5. Run the database setup script on the server