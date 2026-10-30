#!/bin/bash
# Production test script for TASK-8-7: Project CRUD Endpoints
# Run on production server

cd /home/u949031532/domains/seoindicator.com/public_html/backend

echo "=== PRODUCTION TEST: TASK-8-7 Project CRUD Endpoints ==="
echo ""

# Run the PHPUnit tests for ProjectController
php artisan test --filter=ProjectController --stop-on-failure
