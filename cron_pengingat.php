<?php
include 'koneksi.php';
require_once 'fonnte.php';

// Otomatis njupuk format Taun-Wulan soko "Sasi Wingi"
$sasi_wingi = date('Y-m', strtotime('first day of last month')); 
$jeneng_sasi_wingi = date('F', strtotime('first day of last month')); // Nggo isi pesen WA

// Goleki siswa sing BLAS DURUNG upload ing sasi wingi
$query = "
    SELECT id, nama, no_wa_ortu, kelas 
    FROM users 
    WHERE role = 'siswa' 
    AND id NOT IN (
        SELECT DISTINCT siswa_id FROM rekap_tugas WHERE DATE_FORMAT(diupload_at, '%Y-%m') = ?
    )
";
$stmt = $pdo->prepare($query);
$stmt->execute([$sasi_wingi]);
$siswa_bandel = $stmt->fetchAll();

foreach ($siswa_bandel as $s) {
    if ($s['no_wa_ortu']) {
        $pesan = "⚠️ *PERINGATAN AKHIR: RekapMapel SIJA*\n\n" . 
                 "Diberitahukan kepada Orang Tua/Wali dari * " . htmlspecialchars($s['nama']) . " (" . $s['kelas'] . ")* bahwa yang bersangkutan *BELUM* mengumpulkan dokumen rekap tugas untuk periode bulan *" . $jeneng_sasi_wingi . "*.\n\n" .
                 "Mohon agar segera diselesaikan hari ini. Terima kasih.";
        
        kirimWA($s['no_wa_ortu'], $pesan);
    }
}
echo "Notifikasi pengingat bulanan otomatis sukses dikirim kanggo periode $sasi_wingi!";