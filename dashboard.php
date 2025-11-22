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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CampussWallet</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Navbar */
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-custom .navbar-brand {
            font-weight: bold;
            font-size: 1.3rem;
        }
        
        .navbar-custom .nav-link {
            color: white !important;
            margin: 0 5px;
            padding: 8px 15px !important;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .navbar-custom .nav-link:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .btn-logout {
            background: white;
            color: var(--primary-color);
            border: none;
            font-weight: 600;
        }
        
        .btn-logout:hover {
            background: #f0f0f0;
            color: var(--primary-color);
        }
        
        /* Summary Cards */
        .summary-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .summary-card .card-body {
            padding: 25px;
        }
        
        .card-gradient-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .card-gradient-success {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
        }
        
        .card-gradient-danger {
            background: linear-gradient(135deg, #eb3349, #f45c43);
            color: white;
        }
        
        .summary-card h6 {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .summary-card h2 {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
        }
        
        .summary-card i {
            font-size: 2.5rem;
            opacity: 0.3;
            position: absolute;
            right: 20px;
            top: 20px;
        }
        
        /* Section Cards */
        .section-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .section-card .card-header {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            padding: 20px 25px;
            border-radius: 15px 15px 0 0;
        }
        
        .section-card .card-body {
            padding: 25px;
        }
        
        /* Table */
        .table-custom {
            margin-bottom: 0;
        }
        
        .table-custom thead th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding: 15px;
            font-size: 0.9rem;
        }
        
        .table-custom tbody td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .table-custom tbody tr:hover {
            background: #f8f9fa;
        }
        
        /* Badges */
        .category-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        /* Progress Bars */
        .progress-custom {
            height: 25px;
            border-radius: 10px;
            background: #e9ecef;
            overflow: hidden;
        }
        
        .progress-custom .progress-bar {
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Buttons */
        .btn-custom {
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .summary-card h2 {
                font-size: 1.5rem;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-wallet2"></i> CampussWallet
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="bi bi-person-circle"></i> Halo, <strong><?php echo $nama; ?></strong>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tambah_transaksi.php">
                            <i class="bi bi-plus-circle"></i> Transaksi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="anggaran.php">
                            <i class="bi bi-pie-chart"></i> Anggaran
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="target.php">
                            <i class="bi bi-bullseye"></i> Target
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="laporan.php">
                            <i class="bi bi-file-bar-graph"></i> Laporan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-logout btn-sm ms-2" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container-fluid py-4">
        <!-- Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card summary-card card-gradient-primary">
                    <div class="card-body position-relative">
                        <i class="bi bi-wallet"></i>
                        <h6>Total Saldo</h6>
                        <h2><?php echo rupiah($saldo); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card card-gradient-success">
                    <div class="card-body position-relative">
                        <i class="bi bi-arrow-down-circle"></i>
                        <h6>Pemasukan Bulan Ini</h6>
                        <h2><?php echo rupiah($bulan_ini['pemasukan'] ?: 0); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card summary-card card-gradient-danger">
                    <div class="card-body position-relative">
                        <i class="bi bi-arrow-up-circle"></i>
                        <h6>Pengeluaran Bulan Ini</h6>
                        <h2><?php echo rupiah($bulan_ini['pengeluaran'] ?: 0); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Transaksi Terbaru -->
        <div class="card section-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Transaksi Terbaru</h5>
                <a href="tambah_transaksi.php" class="btn btn-primary btn-custom btn-sm">
                    <i class="bi bi-plus-lg"></i> Tambah Transaksi
                </a>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($transaksi_terbaru) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Catatan</th>
                                <th>Akun</th>
                                <th class="text-end">Jumlah</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($t = mysqli_fetch_assoc($transaksi_terbaru)): ?>
                            <tr>
                                <td>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-event"></i>
                                        <?php echo tanggal($t['tanggal_transaksi']); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="category-badge" style="background: <?php echo $t['warna']; ?>20; color: <?php echo $t['warna']; ?>">
                                        <?php echo $t['ikon'] . ' ' . $t['nama_kategori']; ?>
                                    </span>
                                </td>
                                <td><?php echo $t['catatan'] ?: '-'; ?></td>
                                <td>
                                    <small class="text-muted">
                                        <i class="bi bi-credit-card"></i> <?php echo $t['nama_akun']; ?>
                                    </small>
                                </td>
                                <td class="text-end">
                                    <strong style="color: <?php echo $t['jenis_transaksi'] == 'pemasukan' ? '#28a745' : '#dc3545'; ?>">
                                        <?php echo $t['jenis_transaksi'] == 'pemasukan' ? '+' : '-'; ?>
                                        <?php echo rupiah($t['jumlah']); ?>
                                    </strong>
                                </td>
                                <td class="text-center">
                                    <a href="edit_transaksi.php?id=<?php echo $t['id_transaksi']; ?>" 
                                       class="btn btn-warning btn-sm me-1" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="hapus_transaksi.php?id=<?php echo $t['id_transaksi']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Yakin ingin menghapus transaksi ini?')"
                                       title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>Belum ada transaksi</p>
                    <a href="tambah_transaksi.php" class="btn btn-primary btn-custom">
                        <i class="bi bi-plus-lg"></i> Tambah Transaksi Pertama
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Row untuk Anggaran & Target -->
        <div class="row g-4">
            <!-- Anggaran Kritis -->
            <?php if (mysqli_num_rows($anggaran_kritis) > 0): ?>
            <div class="col-md-6">
                <div class="card section-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle-fill text-warning"></i> Anggaran Kritis
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php while ($a = mysqli_fetch_assoc($anggaran_kritis)): 
                            $persen = ($a['terpakai'] / $a['jumlah']) * 100;
                            $is_critical = $persen >= $a['batas_peringatan'];
                        ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <strong><?php echo $a['nama_kategori']; ?></strong>
                                <span class="badge <?php echo $is_critical ? 'bg-danger' : 'bg-warning'; ?>">
                                    <?php echo number_format($persen, 0); ?>%
                                </span>
                            </div>
                            <div class="progress progress-custom">
                                <div class="progress-bar <?php echo $is_critical ? 'bg-danger' : 'bg-warning'; ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo min($persen, 100); ?>%">
                                    <?php echo rupiah($a['terpakai']); ?>
                                </div>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                Sisa: <strong><?php echo rupiah($a['jumlah'] - $a['terpakai']); ?></strong> 
                                dari <?php echo rupiah($a['jumlah']); ?>
                            </small>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Target Aktif -->
            <?php if (mysqli_num_rows($target_aktif) > 0): ?>
            <div class="col-md-6">
                <div class="card section-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-bullseye text-primary"></i> Target Tabungan
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php while ($tg = mysqli_fetch_assoc($target_aktif)): 
                            $persen_target = ($tg['jumlah_terkumpul'] / $tg['jumlah_target']) * 100;
                        ?>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <strong><?php echo $tg['nama']; ?></strong>
                                <span class="badge bg-success">
                                    <?php echo number_format($persen_target, 0); ?>%
                                </span>
                            </div>
                            <div class="progress progress-custom">
                                <div class="progress-bar bg-success" 
                                     role="progressbar" 
                                     style="width: <?php echo min($persen_target, 100); ?>%">
                                    <?php echo rupiah($tg['jumlah_terkumpul']); ?>
                                </div>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                Target: <strong><?php echo rupiah($tg['jumlah_target']); ?></strong>
                                <?php if (isset($tg['tenggat_waktu'])): ?>
                                    | Deadline: <?php echo tanggal($tg['tenggat_waktu']); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>