<?php

// Create a valid 50x20 PNG image using raw bytes
// This avoids GD dependency

// PNG signature
$signature = "\x89PNG\r\n\x1a\n";

// IHDR chunk (image header)
$width = 50;
$height = 20;
$bitDepth = 8;
$colorType = 2; // RGB
$compression = 0;
$filter = 0;
$interlace = 0;

$ihdrData = pack('NNCCCCC', $width, $height, $bitDepth, $colorType, $compression, $filter, $interlace);
$ihdrCrc = crc32(chr(0x00) . 'IHDR' . $ihdrData); // Include chunk type in CRC
$ihdrChunk = pack('N', 13) . 'IHDR' . $ihdrData . pack('N', $ihdrCrc);

// IDAT chunk (image data)
// For simplicity, create a solid color image (no compression - raw deflate)
// This is a very basic uncompressed PNG

// Create raw image data (3 bytes per pixel for RGB, plus filter byte per row)
$rawData = '';
for ($y = 0; $y < $height; $y++) {
    $rawData .= "\x00"; // Filter type: None
    for ($x = 0; $x < $width; $x++) {
        $rawData .= "\x00\x00\x00"; // Black pixel (RGB)
    }
}

// Compress using deflate (store mode - no actual compression)
$compressed = "\x78\x01"; // Zlib header
$compressed .= $rawData;
$compressed .= pack('V', adler32($rawData)); // Adler-32 checksum

$idatCrc = crc32(chr(0x00) . 'IDAT' . $compressed);
$idatChunk = pack('N', strlen($compressed)) . 'IDAT' . $compressed . pack('N', $idatCrc);

// IEND chunk (image end)
$iendCrc = crc32(chr(0x00) . 'IEND');
$iendChunk = pack('N', 0) . 'IEND' . pack('N', $iendCrc);

// Combine all chunks
$png = $signature . $ihdrChunk . $idatChunk . $iendChunk;

// Output as base64
echo "PNG base64 (50x20 black):\n";
echo base64_encode($png) . "\n";
echo "Size: " . strlen($png) . " bytes\n";

// Verify it's valid
$finfo = new finfo(FILEINFO_MIME_TYPE);
echo "MIME: " . $finfo->buffer($png) . "\n";

// Check dimensions
$temp = sys_get_temp_dir() . '/test_50x20.png';
file_put_contents($temp, $png);
$size = getimagesize($temp);
echo "Dimensions: {$size[0]}x{$size[1]}\n";
unlink($temp);
