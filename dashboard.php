<?php
require_once 'config.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

// Ambil ringkasan
$sql_saldo = "SELECT 
    SUM(CASE WHEN jenis_transaksi = 'pemasukan' THEN jumlah ELSE -jumlah END) as saldo
    FROM transaksi WHERE id_pengguna = $user_id";
$saldo = mysqli_fetch_assoc(mysqli_query($conn, $sql_saldo))['saldo'] ?: 0;

$sql_bulan_ini = "SELECT 
    SUM(CASE WHEN jenis_transaksi = 'pemasukan' THEN jumlah ELSE 0 END) as pemasukan,
    SUM(CASE WHEN jenis_transaksi = 'pengeluaran' THEN jumlah ELSE 0 END) as pengeluaran
    FROM transaksi 
    WHERE id_pengguna = $user_id 
    AND MONTH(tanggal_transaksi) = MONTH(CURDATE())
    AND YEAR(tanggal_transaksi) = YEAR(CURDATE())";
$bulan_ini = mysqli_fetch_assoc(mysqli_query($conn, $sql_bulan_ini));

// 5 transaksi terbaru
$sql_transaksi = "SELECT t.*, k.nama as nama_kategori, k.ikon, k.warna 
                  FROM transaksi t 
                  JOIN kategori k ON t.id_kategori = k.id_kategori
                  WHERE t.id_pengguna = $user_id 
                  ORDER BY t.tanggal_transaksi DESC, t.dibuat_pada DESC 
                  LIMIT 5";
$transaksi_terbaru = mysqli_query($conn, $sql_transaksi);

// Anggaran kritis
$sql_anggaran = "SELECT a.*, k.nama as nama_kategori, k.warna,
                 COALESCE(SUM(t.jumlah), 0) as terpakai
                 FROM anggaran a
                 JOIN kategori k ON a.id_kategori = k.id_kategori
                 LEFT JOIN transaksi t ON t.id_pengguna = a.id_pengguna 
                     AND t.id_kategori = a.id_kategori
                     AND t.jenis_transaksi = 'pengeluaran'
                     AND DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = DATE_FORMAT(a.bulan, '%Y-%m')
                 WHERE a.id_pengguna = $user_id
                 GROUP BY a.id_anggaran, a.jumlah, a.batas_peringatan, k.nama, k.warna
                 HAVING (COALESCE(SUM(t.jumlah), 0) / a.jumlah * 100) >= a.batas_peringatan
                 ORDER BY (COALESCE(SUM(t.jumlah), 0) / a.jumlah * 100) DESC";
$anggaran_kritis = mysqli_query($conn, $sql_anggaran);

// Target aktif
$sql_target = "SELECT * FROM target_tabungan 
               WHERE id_pengguna = $user_id AND status = 'aktif'
               ORDER BY (jumlah_terkumpul / jumlah_target * 100) DESC 
               LIMIT 3";
$target_aktif = mysqli_query($conn, $sql_target);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - CampussWallet</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        .navbar {
            background: #667eea;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .navbar h1 { font-size: 20px; }
        .navbar-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
        }
        .navbar a:hover {
            background: rgba(255,255,255,0.2);
        }
        .btn-logout {
            background: white;
            color: #667eea;
        }
        .btn-logout:hover {
            background: #f0f0f0;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .card .amount {
            font-size: 24px;
            font-weight: bold;
        }
        .card-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .card-success { background: linear-gradient(135deg, #11998e, #38ef7d); color: white; }
        .card-danger { background: linear-gradient(135deg, #eb3349, #f45c43); color: white; }
        
        .section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .section h2 {
            margin-bottom: 15px;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 5px 0;
        }
        .progress-fill {
            background: #28a745;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        .progress-fill.danger {
            background: #dc3545;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📱 CampussWallet</h1>
        <div class="navbar-right">
            <span>Halo, <?php echo $nama; ?></span>
            <a href="tambah_transaksi.php">+ Transaksi</a>
            <a href="anggaran.php">Anggaran</a>
            <a href="target.php">Target</a>
            <a href="laporan.php">Laporan</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Cards Ringkasan -->
        <div class="cards">
            <div class="card card-primary">
                <h3>Total Saldo</h3>
                <div class="amount"><?php echo rupiah($saldo); ?></div>
            </div>
            <div class="card card-success">
                <h3>Pemasukan Bulan Ini</h3>
                <div class="amount"><?php echo rupiah($bulan_ini['pemasukan'] ?: 0); ?></div>
            </div>
            <div class="card card-danger">
                <h3>Pengeluaran Bulan Ini</h3>
                <div class="amount"><?php echo rupiah($bulan_ini['pengeluaran'] ?: 0); ?></div>
            </div>
        </div>
        
        <!-- Transaksi Terbaru -->
        <div class="section">
            <h2>
                Transaksi Terbaru
                <a href="tambah_transaksi.php" class="btn btn-primary">+ Tambah</a>
            </h2>
            <?php if (mysqli_num_rows($transaksi_terbaru) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Catatan</th>
                        <th>Akun</th>
                        <th style="text-align: right;">Jumlah</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($t = mysqli_fetch_assoc($transaksi_terbaru)): ?>
                    <tr>
                        <td><?php echo tanggal($t['tanggal_transaksi']); ?></td>
                        <td>
                            <span style="color: <?php echo $t['warna']; ?>">
                                <?php echo $t['ikon'] . ' ' . $t['nama_kategori']; ?>
                            </span>
                        </td>
                        <td><?php echo $t['catatan']; ?></td>
                        <td><?php echo $t['nama_akun']; ?></td>
                        <td style="text-align: right;">
                            <strong style="color: <?php echo $t['jenis_transaksi'] == 'pemasukan' ? '#28a745' : '#dc3545'; ?>">
                                <?php echo $t['jenis_transaksi'] == 'pemasukan' ? '+' : '-'; ?>
                                <?php echo rupiah($t['jumlah']); ?>
                            </strong>
                        </td>
                        <td>
                            <a href="edit_transaksi.php?id=<?php echo $t['id_transaksi']; ?>" style="color: #ffc107;">Edit</a>
                            <a href="hapus_transaksi.php?id=<?php echo $t['id_transaksi']; ?>" 
                               style="color: #dc3545; margin-left: 10px;"
                               onclick="return confirm('Yakin hapus?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">Belum ada transaksi</div>
            <?php endif; ?>
        </div>
        
        <!-- Anggaran Kritis -->
        <?php if (mysqli_num_rows($anggaran_kritis) > 0): ?>
        <div class="section">
            <h2>⚠️ Anggaran Kritis</h2>
            <?php while ($a = mysqli_fetch_assoc($anggaran_kritis)): 
                $persen = ($a['terpakai'] / $a['jumlah']) * 100;
            ?>
            <div style="margin-bottom: 15px;">
                <strong><?php echo $a['nama_kategori']; ?></strong>
                <div class="progress-bar">
                    <div class="progress-fill <?php echo $persen >= $a['batas_peringatan'] ? 'danger' : ''; ?>" 
                         style="width: <?php echo min($persen, 100); ?>%">
                        <?php echo number_format($persen, 0); ?>%
                    </div>
                </div>
                <small>Sisa: <?php echo rupiah($a['jumlah'] - $a['terpakai']); ?> / <?php echo rupiah($a['jumlah']); ?></small>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
        
        <!-- Target Aktif -->
        <?php if (mysqli_num_rows($target_aktif) > 0): ?>
        <div class="section">
            <h2>🎯 Target Tabungan</h2>
            <?php while ($tg = mysqli_fetch_assoc($target_aktif)): 
                $persen_target = ($tg['jumlah_terkumpul'] / $tg['jumlah_target']) * 100;
            ?>
            <div style="margin-bottom: 15px;">
                <strong><?php echo $tg['nama']; ?></strong>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo min($persen_target, 100); ?>%">
                        <?php echo number_format($persen_target, 0); ?>%
                    </div>
                </div>
                <small><?php echo rupiah($tg['jumlah_terkumpul']); ?> / <?php echo rupiah($tg['jumlah_target']); ?></small>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>