<?php
include 'koneksi.php';
session_start();

// 1. Gembok Keamanan: Pastikan sing mlebu bener-bener Admin
if (!isset($_SESSION['is_login']) || $_SESSION['is_login'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

$nama_admin = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Admin Lab';

// --- PROSES TOMBOL: SENTIL WA (Kirim Notif Manual soko Admin) ---
$pesan_alert = '';
$pesan_type = '';

if (isset($_GET['sentil_id'])) {
    $siswa_id = $_GET['sentil_id'];
    
    // Ambil data siswa lan nomer WA ortu
    $stmt_siswa = $pdo->prepare("SELECT nama, kelas, no_wa_ortu FROM users WHERE id = ? AND role = 'siswa'");
    $stmt_siswa->execute([$siswa_id]);
    $siswa = $stmt_siswa->fetch();
    
    if ($siswa && $siswa['no_wa_ortu']) {
        require_once 'fonnte.php';
        $pesan_wa = "🔔 *PENGINGAT: RekapMapel SIJA*\n\n" . "Diberitahukan kepada Orang Tua/Wali dari * " . htmlspecialchars($siswa['nama']) . " (Kelas " . htmlspecialchars($siswa['kelas']) . ")* bahwa yang bersangkutan *BELUM* mengupload foto dokumen rekap tugas untuk minggu ini.\n\nMohon agar segera diingatkan. Terima kasih.";
        
        // Jalakno fungsi Fonnte-mu
        kirimWA($siswa['no_wa_ortu'], $pesan_wa);
        
        $pesan_alert = "✅ Berhasil menyentil Orang Tua " . htmlspecialchars($siswa['nama']) . " via WhatsApp!";
        $pesan_type = "success";
    } else {
        $pesan_alert = "❌ Gagal menyentil, nomor WA Orang Tua tidak ditemukan.";
        $pesan_type = "danger";
    }
}

// 2. Query Taktis: Ambil kabeh siswa + Hitung total foto sing wis diupload + Ambil foto terakhir
// Menggunakan LEFT JOIN antarane tabel users lan rekap_tugas
$query_siswa = "
    SELECT 
        u.id, u.nama, u.kelas, u.no_wa_ortu,
        COUNT(r.id) AS total_upload,
        MAX(r.diupload_at) AS terakhir_upload,
        (SELECT r2.foto_dokumen FROM rekap_tugas r2 WHERE r2.siswa_id = u.id ORDER BY r2.diupload_at DESC LIMIT 1) AS foto_terakhir
    FROM users u
    LEFT JOIN rekap_tugas r ON u.id = r.siswa_id
    WHERE u.role = 'siswa'
    GROUP BY u.id
    ORDER BY u.kelas ASC, u.nama ASC
";
$stmt = $pdo->query($query_siswa);
$daftar_siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Query Ringkesan Widget Box (Statistik)
$total_siswa_binaan = count($daftar_siswa);
$total_seluruh_foto = $pdo->query("SELECT COUNT(*) FROM rekap_tugas")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - RekapMapel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
        <div class="container px-4">
            <span class="navbar-brand fw-bold text-primary">📋 RekapMapel <span class="text-white">Admin</span></span>
            <div class="d-flex align-items-center gap-3">
                <span class="text-light fw-medium small">
                    👑 Login sebagai: <strong class="text-warning"><?= htmlspecialchars($nama_admin) ?></strong>
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Keluar</a>
            </div>
        </div>
    </nav>

    <!-- MAIN KONTEN -->
    <div class="container mt-4 mb-5">
        
        <!-- Notifikasi Alert -->
        <?php if ($pesan_alert): ?>
        <div class="alert alert-<?= $pesan_type ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= htmlspecialchars($pesan_alert) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- WIDGET STATISTIK -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 bg-white rounded-3 p-3 d-flex flex-row align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Siswa Binaan</h6>
                        <h3 class="fw-bold text-dark mb-0"><?= $total_siswa_binaan ?> <span class="fs-6 text-muted font-normal">Siswa</span></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3">
                        <i class="bi bi-people fs-3"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 bg-white rounded-3 p-3 d-flex flex-row align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Dokumen Tugas</h6>
                        <h3 class="fw-bold text-dark mb-0"><?= $total_seluruh_foto ?> <span class="fs-6 text-muted font-normal">Foto</span></h3>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                        <i class="bi bi-file-earmark-image fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL UTAMA MONITORING SISWA -->
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-secondary"><i class="bi bi-grid-3x3-gap-fill text-primary"></i> Monitoring Ketuntasan Tugas</h5>
                <span class="badge bg-light text-dark border">Data Realtime</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase">
                            <tr>
                                <th class="text-center" style="width: 5%;">No</th>
                                <th>Nama Siswa</th>
                                <th class="text-center" style="width: 12%;">Kelas</th>
                                <th class="text-center" style="width: 15%;">Jumlah Upload</th>
                                <th>Terakhir Upload</th>
                                <th class="text-center" style="width: 25%;">Aksi Pemantauan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daftar_siswa)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada data siswa di database.</td>
                                </tr>
                            <?php else: $no = 1; foreach ($daftar_siswa as $s): ?>
                                <tr>
                                    <td class="text-center fw-medium"><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($s['nama']) ?></div>
                                        <div class="text-muted small"><i class="bi bi-whatsapp text-success"></i> <?= htmlspecialchars($s['no_wa_ortu'] ?? '-') ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= htmlspecialchars($s['kelas']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $s['total_upload'] > 0 ? 'bg-success' : 'bg-danger' ?> rounded-pill px-3 fs-6">
                                            <?= $s['total_upload'] ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <?= $s['terakhir_upload'] ? date('d M Y, H:i', strtotime($s['terakhir_upload'])) : '<span class="text-danger italic">Belum Pernah</span>' ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <!-- Tombol Ndelok Foto (Mung aktif yen arek-e wis tau upload) -->
                                            <?php if ($s['foto_terakhir']): ?>
                                                <a href="<?= htmlspecialchars($s['foto_terakhir']) ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-semibold">
                                                    <i class="bi bi-eye"></i> Lihat Foto
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary fw-semibold" disabled>
                                                    <i class="bi bi-eye-slash"></i> No Image
                                                </button>
                                            <?php endif; ?>

                                            <!-- Tombol Sentil WA -->
                                            <a href="dashboard_admin.php?sentil_id=<?= $s['id'] ?>" class="btn btn-sm btn-warning text-dark fw-bold <?= $s['no_wa_ortu'] ? '' : 'disabled' ?>" onclick="return confirm('Kirim notifikasi pengingat WA ke orang tua <?= htmlspecialchars($s['nama']) ?>?')">
                                                <i class="bi bi-bell-fill"></i> Sentil WA
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>