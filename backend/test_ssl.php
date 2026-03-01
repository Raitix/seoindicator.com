<?php
echo "Testing SSL with Avast CA added...\n\n";

$ch = curl_init('https://api.github.com/rate_limit');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CAINFO, 'C:\xampp\cacert.pem');
curl_setopt($ch, CURLOPT_VERBOSE, true);
$result = curl_exec($ch);
if ($result === false) {
    echo "Curl error: " . curl_error($ch) . "\n";
    echo "Curl error code: " . curl_errno($ch) . "\n";
} else {
    echo "SUCCESS! Response length: " . strlen($result) . "\n";
}
curl_close($ch);
