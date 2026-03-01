@echo off
set CURL_CA_BUNDLE=C:\xampp\cacert.pem
set SSL_CERT_FILE=C:\xampp\cacert.pem
set CURL_CA_INFO=C:\xampp\cacert.pem
cd /d C:\Users\Raitis\.openclaw\workspace\PROJECT-SEOINDICATOR\backend
composer require laravel/sanctum laravel/socialite --no-interaction
