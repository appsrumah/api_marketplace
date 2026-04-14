<?php
// decrypt.php
require __DIR__.'/vendor/autoload.php';

// CARA 1: Jika APP_KEY dari .env dengan prefix "base64:"
// Contoh: base64:5BM1BXGOBrGeeqJMAWJZSzyzh5yPcCGOcOGPtUij65g=
$appKeyWithPrefix = "base64:9m73GQ25AbMFLsI63GPigTLjy3t/bQO8rBcvXPdezKw="; // Ganti dengan APP_KEY asli

// Extract key (hapus prefix "base64:")
$appKey = str_replace('base64:', '', $appKeyWithPrefix);

// Decode base64
$decodedKey = base64_decode($appKey);

// Cek panjang key
echo "Panjang key setelah decode: " . strlen($decodedKey) . " byte\n";
echo "Yang dibutuhkan: 32 byte untuk AES-256-CBC\n\n";

if (strlen($decodedKey) !== 32) {
    die("ERROR: Key tidak valid! Panjang harus 32 byte.\n");
}

// Buat encrypter
$encrypter = new Illuminate\Encryption\Encrypter($decodedKey, 'AES-256-CBC');

$encrypted = "eyJpdiI6IlJFMnNSb21QZnd6Y2FwVUpMOVowQXc9PSIsInZhbHVlIjoieGVCNERrc3FDSGhDZlFmL0QzWnRxRUtCQzhoZlArRFdLRTdtZXgyaXJRcTJpeHNmWEJ2NVhZQzNUd0NnbHExNXltM1Vld1ZEaFB0QXN0ejF6NzIrR1poTjErdjB2S3JtOWFSaG94eGR0YWMzazhjN1A3TlJQeHBRd3k0bjFmai9YVnBIZlpPd1o3L3Z6YjJDVmg4bWNBPT0iLCJtYWMiOiJjZDQ3Y2ZlMjk5ZDY2OWZiZDQxNDA4NWI4M2JlMzUzNGMwYmIyNjVhMWU0ZjM1NTQ5MzU0ZjM4OTllNmJiYTdiIiwidGFnIjoiIn0=";

try {
    $decrypted = $encrypter->decryptString($encrypted);
    echo "✅ HASIL DEKRIPSI: " . $decrypted . PHP_EOL;
} catch (Exception $e) {
    echo "❌ GAGAL: " . $e->getMessage() . PHP_EOL;
}
?>