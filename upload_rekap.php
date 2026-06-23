<?php
include 'koneksi.php';
session_start();
if (!isset($_SESSION['is_login']) || $_SESSION['is_login'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$kelas   = $_SESSION['kelas'] ?? '';
$nama    = $_SESSION['nama'] ?? $_SESSION['username'] ?? '';

if (!$user_id) {
    die("Sesi tidak valid. Silakan login ulang.");
}

$pesan = '';
$pesan_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_dokumen'])) {
    $file = $_FILES['foto_dokumen'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $pesan = "Gagal upload, coba lagi.";
        $pesan_type = 'danger';
    } else {
        $ekstensi_boleh = ['jpg', 'jpeg', 'png'];
        $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($ekstensi, $ekstensi_boleh)) {
            $pesan = "Format file tidak didukung. Gunakan JPG atau PNG.";
            $pesan_type = 'danger';
        } elseif ($file['size'] > $max_size) {
            $pesan = "Ukuran file terlalu besar. Maksimal 5MB.";
            $pesan_type = 'danger';
        } else {
            // Validasi tambahan: pastikan benar-benar file gambar (bukan cuma ekstensi)
            $info_gambar = @getimagesize($file['tmp_name']);
            if ($info_gambar === false) {
                $pesan = "File bukan gambar yang valid.";
                $pesan_type = 'danger';
            } else {
                $nama_file_baru = 'rekap_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $ekstensi;
                $tujuan = 'uploads/' . $nama_file_baru;

                if (move_uploaded_file($file['tmp_name'], $tujuan)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO rekap_tugas (siswa_id, kelas, foto_dokumen, diupload_at) VALUES (?, ?, ?, NOW())");
                        $stmt->execute([$user_id, $kelas, $tujuan]);

                        // Kirim notifikasi WA ke ortu
                        $stmt_wa = $pdo->prepare("SELECT no_wa_ortu FROM users WHERE id = ?");
                        $stmt_wa->execute([$user_id]);
                        $no_wa = $stmt_wa->fetchColumn();

                        if ($no_wa) {
                            require_once 'fonnte.php';
                            $tanggal = date('d M Y, H:i');
                            $pesan_wa = "📋 *RekapMapel SIJA*\n\n" . htmlspecialchars($nama) . " (Kelas " . htmlspecialchars($kelas) . ") telah mengupload rekap tugas pada $tanggal.\n\nTerima kasih atas perhatiannya.";
                            kirimWA($no_wa, $pesan_wa);
                        }

                        $pesan = "✅ Rekap tugas berhasil diupload! Notifikasi sudah dikirim ke orang tua/wali.";
                        $pesan_type = 'success';
                    } catch (PDOException $e) {
                        $pesan = "Gagal simpan ke database: " . htmlspecialchars($e->getMessage());
                        $pesan_type = 'danger';
                    }
                } else {
                    $pesan = "Gagal memindahkan file ke server.";
                    $pesan_type = 'danger';
                }
            }
        }
    }
}

// Ambil riwayat upload siswa ini
$riwayat = $pdo->prepare("SELECT * FROM rekap_tugas WHERE siswa_id = ? ORDER BY diupload_at DESC LIMIT 10");
$riwayat->execute([$user_id]);
$riwayat_list = $riwayat->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Rekap - RekapMapel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container px-4">
            <span class="navbar-brand fw-bold">📋 RekapMapel</span>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary fw-medium small">
                    👋 Hai, <strong class="text-dark"><?= htmlspecialchars($nama) ?></strong>
                    <span class="badge bg-secondary"><?= htmlspecialchars($kelas) ?></span>
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Keluar</a>
            </div>
        </div>
    </nav>

    <!-- KONTEN -->
    <div class="container mt-5 mb-5" style="max-width: 600px;">

        <?php if ($pesan): ?>
        <div class="alert alert-<?= $pesan_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($pesan) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="card-title mb-0 fw-bold">📤 Upload Rekap Tugas</h5>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-3">
                    Upload foto dokumen rekap tugas yang sudah ditandatangani guru mapel.
                    Notifikasi akan otomatis dikirim ke orang tua/wali via WhatsApp.
                </p>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Foto Dokumen (JPG/PNG, maks 5MB)</label>
                        <input type="file" class="form-control" name="foto_dokumen" accept="image/jpeg,image/png" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                        <i class="bi bi-cloud-upload"></i> Upload & Kirim Notifikasi
                    </button>
                </form>
            </div>
        </div>

        <!-- RIWAYAT -->
        <h6 class="fw-bold mb-3">📜 Riwayat Upload Kamu</h6>
        <?php if (empty($riwayat_list)): ?>
            <p class="text-muted small">Belum ada riwayat upload.</p>
        <?php else: ?>
        <div class="list-group">
            <?php foreach ($riwayat_list as $r): ?>
            <a href="<?= htmlspecialchars($r['foto_dokumen']) ?>" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span><i class="bi bi-image"></i> Rekap <?= date('d M Y', strtotime($r['diupload_at'])) ?></span>
                <small class="text-muted"><?= date('H:i', strtotime($r['diupload_at'])) ?></small>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>