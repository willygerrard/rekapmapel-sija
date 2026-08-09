<?php
/**
 * cron_guard.php
 * Pengaman bersama untuk semua script cron (cron_cleanup_semester.php, cron_pengingat.php, dll).
 *
 * Aturan:
 *  1. Jika dijalankan lewat CLI (php cron_xxx.php) -> DIIZINKAN (ini cara normal cron dijalankan).
 *  2. Jika diakses lewat WEB (browser) -> WAJIB menyertakan token rahasia & sesuai role kebijakan:
 *       - Query string:   ?token=<CRON_SECRET>
 *       - Header HTTP:    X-Cron-Token: <CRON_SECRET>
 *     Token dibaca dari environment variable CRON_SECRET (di .env), BUKAN hardcoded.
 *
 * Konfigurasi di .env:
 *   CRON_SECRET=string_acak_panjang
 *
 * Contoh akses via web (jika memang dibutuhkan):
 *   https://domain-andamu/cron_cleanup_semester.php?token=<CRON_SECRET>
 */

// Cegah direct access ke file guard ini
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'cron_guard.php') {
    http_response_code(403);
    exit('Akses ditolak.');
}

// 1. Jika via CLI -> selamat datang, langsung lanjut
if (PHP_SAPI === 'cli') {
    return;
}

// 2. Jika via web -> minta token valid
$secret = getenv('CRON_SECRET');

if ($secret === false || $secret === '') {
    http_response_code(500);
    exit('CRON_SECRET belum dikonfigurasi di .env. Akses cron via web ditolak.');
}

$tokenRequest = $_GET['token'] ?? ($_SERVER['HTTP_X_CRON_TOKEN'] ?? '');

// Pakai hash_equals untuk membandingkan tanpa timing attack
if (!is_string($tokenRequest) || !hash_equals($secret, $tokenRequest)) {
    http_response_code(403);
    exit('Token cron tidak valid.');
}

// Kalau lewat semua cek -> lanjut eksekusi cron
