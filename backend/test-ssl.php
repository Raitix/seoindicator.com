<?php
// Disable SSL verification for curl
$ch = curl_init('https://repo.packagist.org/p2/laravel/sanctum.json');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
if ($result === false) {
    echo 'Curl error: ' . curl_error($ch) . "\n";
} else {
    echo 'OK - ' . strlen($result) . ' bytes downloaded' . "\n";
    file_put_contents(__DIR__ . '/test-sanctum.json', $result);
}
curl_close($ch);
