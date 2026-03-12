<?php

$content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
$finfo = new finfo(FILEINFO_MIME_TYPE);
echo 'PNG content MIME: ' . $finfo->buffer($content) . PHP_EOL;

$content = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMCwsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EAAQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q==');
echo 'JPEG content MIME: ' . $finfo->buffer($content) . PHP_EOL;

$content = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"><rect fill="#000" width="100" height="50"/></svg>';
echo 'SVG content MIME: ' . $finfo->buffer($content) . PHP_EOL;
