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

$encrypted = "eyJpdiI6InBTM3I3YkYwRk8vSUxSUmVJOGk2eEE9PSIsInZhbHVlIjoiQ01ZNUZ3NEhvZHArbk9sL3NSTGphWm1OSXRVTlRNNS9Dd1ZPSEt2VzBIK0ljZUwyUWVxTGtmSjNpdFdNL1c2S2JkV3A3d0tTdWRLbHNkOHRuMUtyS09nTXNmK3RWaWdUbVY5SENsRXZneDE0b09MelNnSmo2aU9ZdjZLZWpBZTd6elNVV0lKaUt1V0I1elNXaVhsRE9mQVgvR3hYZVJkVWQxMFQwMldLR3ZDUGNnTER0cWhzakM1RUtzVmNYam41b3JVZ2xFeWMya1c2a2crS25iT2FhZUJNeE1RcStHbjRCMERWeWxlSlU5RlNmY01zQmFCTXprbk1nelRSS2JNYSIsIm1hYyI6IjRlNWE0ZjIwYzRiMWY0MjI4YTFlOTQ1NGMxZjg0YzZjMGIwODVkMTQ3N2QxYmFlYzkwMjdmMzFkZDYwNmJjN2QiLCJ0YWciOiIifQ==";

try {
    $decrypted = $encrypter->decryptString($encrypted);
    echo "✅ HASIL DEKRIPSI: " . $decrypted . PHP_EOL;
} catch (Exception $e) {
    echo "❌ GAGAL: " . $e->getMessage() . PHP_EOL;
}
?>