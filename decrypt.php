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

$encrypted = "eyJpdiI6IlhwbUJEWGxMSStucVYzOGM0aFZha3c9PSIsInZhbHVlIjoiZEtXZWRDQzdMeUNVTFJPUldxdDVpWWhjakd3aG0rVlNRSXRrUmMzTUdhTzJXVTFtMVpFOWhNOExSakpFT2F5Q3diQmd2MzF3NERNNXdDSy9ML3lGb0txTmJRNUhZVHlTdThiNE1sZ0lodStMa1pHL0xKZ3E0K2JiZzNPeWpwUW9ISlVYLzFubTRJakM1NVVhcXE2MUVZSHZYTkhwbEtoeG9hc096SUVxblRBM25YVEQrYXpQYnlheXNBTFc4RWI0ZDl5QnliMk1ZMVV5b3RIanFjdzVEdE9zb0FQWEh3TkxlYjI1dUpvSHBIWHJSVFJsVlVsL0RWVmZWcjBnN3NwSCIsIm1hYyI6IjY2MWI1ODM5NTQ2ZmQ5ZjBlZDdlMTM3NDQ5NmFlZjFhYjM5ZjJmN2RlN2E1ZWNlZWE1YjI5NWU0ODg4ZjgxYjkiLCJ0YWciOiIifQ==";

try {
    $decrypted = $encrypter->decryptString($encrypted);
    echo "✅ HASIL DEKRIPSI: " . $decrypted . PHP_EOL;
} catch (Exception $e) {
    echo "❌ GAGAL: " . $e->getMessage() . PHP_EOL;
}
?>