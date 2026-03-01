@echo off
set SSL_CERT_FILE=C:\Users\Raitis\cacert.pem
set CURL_CA_BUNDLE=C:\Users\Raitis\cacert.pem
cd /d C:\Users\Raitis\.openclaw\workspace\PROJECT-SEOINDICATOR\backend
composer require laravel/sanctum laravel/socialite --no-interaction
