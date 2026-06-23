<?php
include 'koneksi.php';

// Taktis: Goleki data sing tanggal_upload-e wis luwih soko 5 sasi kepungkur
// Iki otomatis narget sak semester kepungkur sacara dinamis
$bates_waktu = date('Y-m-d H:i:s', strtotime('-5 months'));

try {
    // 1. Goleki path file foto sing wis kadaluwarsa soko semester wingi
    $stmt = $pdo->prepare("SELECT foto_dokumen FROM rekap_tugas WHERE diupload_at < ?");
    $stmt->execute([$bates_waktu]);
    $foto_list = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $total_foto_dihapus = 0;
    foreach ($foto_list as $foto_path) {
        if (file_exists($foto_path)) {
            unlink($foto_path); // <--- Hapus file foto fisik ing server
            $total_foto_dihapus++;
        }
    }

    // 2. Busak rekamane soko database
    $stmt_del = $pdo->prepare("DELETE FROM rekap_tugas WHERE diupload_at < ?");
    $stmt_del->execute([$bates_waktu]);
    $total_db_dihapus = $stmt_del->rowCount();

    echo "🎉 Sukses Reresik Semesteran!\n";
    echo "Total file foto fisik dibusak: $total_foto_dihapus\n";
    echo "Total rekaman database dibusak: $total_db_dihapus\n";
    echo "Server adhem lan longgar maneh kanggo semester anyar!";

} catch (PDOException $e) {
    echo "Gagal reresik semesteran: " . $e->getMessage();
}