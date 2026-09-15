<?php
include 'koneksi.php';
session_start();

// Cuma admin yang boleh akses endpoint ini
if (!isset($_SESSION['is_login']) || $_SESSION['is_login'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

$siswa_id = $_GET['siswa_id'] ?? null;

if (!$siswa_id || !ctype_digit((string) $siswa_id)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'siswa_id tidak valid']);
    exit();
}

$stmt = $pdo->prepare(
    "SELECT foto_dokumen, diupload_at
     FROM rekap_tugas
     WHERE siswa_id = ? AND foto_dokumen IS NOT NULL
     ORDER BY diupload_at DESC"
);
$stmt->execute([$siswa_id]);
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format tanggal biar enak dibaca di modal, dan pastikan path foto konsisten
foreach ($riwayat as &$r) {
    $r['diupload_at'] = $r['diupload_at'] ? date('d M Y, H:i', strtotime($r['diupload_at'])) : '';
}
unset($r);

header('Content-Type: application/json');
echo json_encode($riwayat);
