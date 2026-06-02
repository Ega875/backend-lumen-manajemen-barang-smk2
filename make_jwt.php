<?php
require 'vendor/autoload.php';

use Firebase\JWT\JWT;

// Kunci rahasia untuk menandatangani token
$key = "kunci_rahasia_super_aman_anda_123";

// Data atau klaim yang ingin dimasukkan ke dalam token
$payload = [
    'iss'  => 'http://domain-anda.com', // Penerbit token
    'aud'  => 'http://domain-anda.com', // Penerima token
    'iat'  => time(),                   // Waktu token dibuat
    'nbf'  => time(),                   // Waktu token mulai berlaku
    'exp'  => time() + 3600,            // Waktu kedaluwarsa (1 jam)
    'data' => [
        'userId' => 45,
        'role'   => 'admin'
    ]
];

// Generate token menggunakan algoritma HS256
$jwt = JWT::encode($payload, $key, 'HS256');

// Cetak token ke terminal
echo $jwt . "\n";

