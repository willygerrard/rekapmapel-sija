<?php
$host = 'db';
$db   = 'db_rekapmapel';
$user = 'rekapmapel_user';
$pass = 'ganti_password_kuat_juga';
$port = '3306';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database GAGAL Karena: " . $e->getMessage());
}