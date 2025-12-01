<?php
/**
 * FILE: config.php
 * FUNGSI: Koneksi database + helper functions
 */

// Konfigurasi Database
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "campusswallet");

// Koneksi ke database
$conn = mysqli_connect("localhost", "root", "", "campusswallet");

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Fungsi untuk mencegah SQL Injection
function escape($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Fungsi format rupiah
function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Fungsi format tanggal
function tanggal($date) {
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    $split = explode('-', $date);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

// Start session jika belum
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
