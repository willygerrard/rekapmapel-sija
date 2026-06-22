<?php
require 'koneksi.php';

$pesan = "";
$token_sah = "RekapSija2026";

if (isset($_POST['register'])) {

    $username    = trim($_POST['username']);
    $password    = $_POST['password'];
    $nama        = trim($_POST['nama']);
    $kelas       = trim($_POST['kelas']);
    $no_wa_ortu  = trim($_POST['no_wa_ortu']);
    $token_input = trim($_POST['token']);
    $role        = 'siswa';

    // Normalisasi nomor WA (hapus spasi, strip, dst)
    $no_wa_ortu_bersih = preg_replace('/[^0-9]/', '', $no_wa_ortu);

    if ($token_input !== $token_sah) {
        $pesan = "<div class='alert alert-danger small mb-3'>Token salah! Tanyakan ke guru pembimbing.</div>";
    } elseif (!preg_match('/^(08|62)[0-9]{8,12}$/', $no_wa_ortu_bersih)) {
        $pesan = "<div class='alert alert-danger small mb-3'>Format nomor WA tidak valid. Contoh: 081234567890</div>";
    } else {
        try {
            $stmt_cek = $pdo->prepare("SELECT username FROM users WHERE username = ?");
            $stmt_cek->execute([$username]);

            if ($stmt_cek->rowCount() > 0) {
                $pesan = "<div class='alert alert-danger small mb-3'>Username sudah terdaftar!</div>";
            } else {
                $password_aman = password_hash($password, PASSWORD_DEFAULT);

                $stmt_insert = $pdo->prepare("INSERT INTO users (username, password, nama, kelas, no_wa_ortu, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $eksekusi = $stmt_insert->execute([$username, $password_aman, $nama, $kelas, $no_wa_ortu_bersih, $role]);

                if ($eksekusi) {
                    $pesan = "<div class='alert alert-success small mb-3'>Akun berhasil dibuat! Silakan login.</div>";
                }
            }
        } catch (PDOException $e) {
            $pesan = "<div class='alert alert-danger small mb-3'>Gagal: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - RekapMapel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="card shadow-sm border-0 rounded-3" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h4 class="fw-bold mb-1">📋 RekapMapel</h4>
                <p class="text-muted small mb-0">Daftar Akun Siswa Binaan</p>
            </div>

            <?= $pesan; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Lengkap</label>
                    <input type="text" class="form-control" name="nama" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Kelas</label>
                    <input type="text" class="form-control" name="kelas" placeholder="Contoh: XI SIJA 1" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Username</label>
                    <input type="text" class="form-control" name="username" required autocomplete="off">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">No. WhatsApp Orang Tua/Wali</label>
                    <input type="text" class="form-control" name="no_wa_ortu" placeholder="Contoh: 081234567890" required>
                    <div class="form-text">Untuk notifikasi rekap tugas ke orang tua.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-warning">Token Akses Pendaftaran</label>
                    <input type="text" class="form-control" name="token" placeholder="Tanyakan ke guru pembimbing" required autocomplete="off">
                </div>

                <button type="submit" name="register" class="btn btn-primary w-100 fw-bold py-2">
                    <i class="bi bi-person-plus"></i> Daftar Akun
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="login.php" class="text-muted small text-decoration-none">← Kembali ke halaman login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>