<?php
$ch = curl_init('https://repo.packagist.org/packages.json');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CAINFO, 'C:\xampp\cacert.pem');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error: ' . curl_errno($ch) . ' - ' . curl_error($ch) . "\n";
} else {
    echo "Success! Got " . strlen($result) . " bytes\n";
}
curl_close($ch);
