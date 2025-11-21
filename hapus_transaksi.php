<?php
require_once 'config.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$id_transaksi = intval($_GET['id']);

// Ambil data transaksi untuk cek target
$sql = "SELECT * FROM transaksi WHERE id_transaksi = $id_transaksi AND id_pengguna = $user_id";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: dashboard.php');
    exit();
}

$transaksi = mysqli_fetch_assoc($result);

// Jika transaksi terkait target, kurangi jumlah terkumpul
if ($transaksi['id_target'] && $transaksi['jenis_transaksi'] == 'pemasukan') {
    $jumlah = $transaksi['jumlah'];
    $id_target = $transaksi['id_target'];
    mysqli_query($conn, "UPDATE target_tabungan 
                         SET jumlah_terkumpul = jumlah_terkumpul - $jumlah 
                         WHERE id_target = $id_target");
}

// Hapus transaksi
mysqli_query($conn, "DELETE FROM transaksi WHERE id_transaksi = $id_transaksi AND id_pengguna = $user_id");

header('Location: dashboard.php');
exit();
?>