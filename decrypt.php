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

$encrypted = "eyJpdiI6ImZhY2Q0dGtpZWIrSEFDbTlzOU1FQVE9PSIsInZhbHVlIjoibmdMbDEySnBMWFlqbVpmMkRwVldUYVZoTEwzWnhxL21HNFM1UGxxWnJpV0FjR1NjTWhLQUV3VDVqdnFmVExmdkF2NHVIL2pMOFVnZDNKT0JBNHhURTcvL0pFWjlHUFh6eElIQmpYN01xdElUb3B6MzlMMjBnQ3NHWFFVc2N6Sllac1FDMzN1eWloZzRabzZ0OEN6S0xBPT0iLCJtYWMiOiIwOTAxYWQyYTliNTUyMTE3MmQ5Y2I3ZGM0ZTBhYjMzZWMxNmViY2Y3ZmIxMTRiYWFmYTUxNTQxOWI5Y2M1ZGZiIiwidGFnIjoiIn0=";

try {
    $decrypted = $encrypter->decryptString($encrypted);
    echo "✅ HASIL DEKRIPSI: " . $decrypted . PHP_EOL;
} catch (Exception $e) {
    echo "❌ GAGAL: " . $e->getMessage() . PHP_EOL;
}
?>