<?php
require_once 'config.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];
$error = '';
$success = '';

// Filter bulan
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Proses tambah anggaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $id_kategori = intval($_POST['id_kategori']);
    $jumlah = floatval($_POST['jumlah']);
    $bulan_anggaran = $_POST['bulan'] . '-01';
    $batas_peringatan = floatval($_POST['batas_peringatan']);
    
    // Cek duplikat
    $check = mysqli_query($conn, "SELECT * FROM anggaran 
                                   WHERE id_pengguna = $user_id 
                                   AND id_kategori = $id_kategori 
                                   AND bulan = '$bulan_anggaran'");
    
    if (mysqli_num_rows($check) > 0) {
        $error = 'Anggaran untuk kategori ini di bulan tersebut sudah ada!';
    } else {
        $query = "INSERT INTO anggaran (id_pengguna, id_kategori, jumlah, bulan, batas_peringatan) 
                  VALUES ($user_id, $id_kategori, $jumlah, '$bulan_anggaran', $batas_peringatan)";
        
        if (mysqli_query($conn, $query)) {
            $success = 'Anggaran berhasil ditambahkan!';
        } else {
            $error = 'Gagal menambahkan anggaran!';
        }
    }
}

// Proses hapus anggaran
if (isset($_GET['hapus'])) {
    $id_anggaran = intval($_GET['hapus']);
    mysqli_query($conn, "DELETE FROM anggaran WHERE id_anggaran = $id_anggaran AND id_pengguna = $user_id");
    header("Location: anggaran.php?bulan=$bulan");
    exit();
}

// Ambil kategori pengeluaran
$sql_kategori = "SELECT * FROM kategori WHERE jenis = 'pengeluaran' ORDER BY nama";
$kategori = mysqli_query($conn, $sql_kategori);

// Ambil daftar anggaran
$sql_anggaran = "SELECT a.*, k.nama as nama_kategori, k.warna, k.ikon,
                 COALESCE(SUM(t.jumlah), 0) as terpakai
                 FROM anggaran a
                 JOIN kategori k ON a.id_kategori = k.id_kategori
                 LEFT JOIN transaksi t ON t.id_pengguna = a.id_pengguna 
                     AND t.id_kategori = a.id_kategori
                     AND t.jenis_transaksi = 'pengeluaran'
                     AND DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = DATE_FORMAT(a.bulan, '%Y-%m')
                 WHERE a.id_pengguna = $user_id
                 AND DATE_FORMAT(a.bulan, '%Y-%m') = '$bulan'
                 GROUP BY a.id_anggaran, a.jumlah, k.nama, k.warna, k.ikon
                 ORDER BY (COALESCE(SUM(t.jumlah), 0) / a.jumlah * 100) DESC";
$result_anggaran = mysqli_query($conn, $sql_anggaran);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anggaran - CampussWallet</title>
    
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
            color: white;
        }
        
        .btn-back {
            background: white;
            color: var(--primary-color);
            border: none;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 8px;
        }
        
        .btn-back:hover {
            background: #f0f0f0;
            color: var(--primary-color);
        }
        
        .section-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 30px;
            margin-bottom: 25px;
        }
        
        .section-card h2 {
            color: #333;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary-color);
            font-weight: 700;
        }
        
        .anggaran-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s;
            height: 100%;
        }
        
        .anggaran-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }
        
        .anggaran-card.bahaya {
            border-color: #dc3545;
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.05), rgba(244, 92, 67, 0.05));
        }
        
        .anggaran-card.hati-hati {
            border-color: #ffc107;
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.05), rgba(255, 152, 0, 0.05));
        }
        
        .anggaran-card.aman {
            border-color: #28a745;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.05), rgba(56, 239, 125, 0.05));
        }
        
        .kategori-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .kategori-name {
            font-size: 1.3rem;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .status-aman { background: #d4edda; color: #155724; }
        .status-hati-hati { background: #fff3cd; color: #856404; }
        .status-bahaya { background: #f8d7da; color: #721c24; }
        
        .progress-custom {
            height: 30px;
            border-radius: 15px;
            background: #e9ecef;
            margin: 15px 0;
        }
        
        .progress-bar-custom {
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            border-radius: 15px;
        }
        
        .anggaran-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 0.95rem;
        }
        
        .info-label {
            color: #6c757d;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
        }
        
        .filter-section {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="anggaran.php">
                <i class="bi bi-pie-chart-fill"></i> Anggaran Bulanan
            </a>
            <a href="dashboard.php" class="btn btn-back">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container-fluid py-4">
        <!-- Form Tambah Anggaran -->
        <div class="section-card">
            <h2><i class="bi bi-plus-circle"></i> Tambah Anggaran Baru</h2>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label"><i class="bi bi-tag"></i> Kategori Pengeluaran</label>
                        <select name="id_kategori" class="form-select" required>
                            <option value="">Pilih Kategori</option>
                            <?php 
                            mysqli_data_seek($kategori, 0);
                            while ($k = mysqli_fetch_assoc($kategori)): 
                            ?>
                                <option value="<?php echo $k['id_kategori']; ?>">
                                    <?php echo $k['ikon'] . ' ' . $k['nama']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label"><i class="bi bi-cash"></i> Jumlah (Rp)</label>
                        <input type="number" name="jumlah" class="form-control" required min="0" step="1000" placeholder="100000">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label"><i class="bi bi-calendar3"></i> Bulan</label>
                        <input type="month" name="bulan" class="form-control" required value="<?php echo $bulan; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label"><i class="bi bi-exclamation-triangle"></i> Batas (%)</label>
                        <input type="number" name="batas_peringatan" class="form-control" required value="80" min="0" max="100">
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" name="tambah" class="btn btn-success w-100">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Filter Bulan -->
        <div class="filter-section">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-auto">
                    <label class="form-label fw-bold">
                        <i class="bi bi-filter"></i> Filter Periode:
                    </label>
                </div>
                <div class="col-auto">
                    <input type="month" name="bulan" class="form-control" value="<?php echo $bulan; ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Daftar Anggaran -->
        <div class="section-card">
            <h2><i class="bi bi-list-check"></i> Daftar Anggaran - <?php echo date('F Y', strtotime($bulan . '-01')); ?></h2>
            
            <?php if (mysqli_num_rows($result_anggaran) > 0): ?>
            <div class="row g-4">
                <?php while ($a = mysqli_fetch_assoc($result_anggaran)): 
                    $persen = ($a['terpakai'] / $a['jumlah']) * 100;
                    $status = 'aman';
                    $status_text = 'AMAN';
                    $progress_class = 'bg-success';
                    
                    if ($persen >= $a['batas_peringatan']) {
                        $status = 'bahaya';
                        $status_text = 'BAHAYA';
                        $progress_class = 'bg-danger';
                    } elseif ($persen >= 50) {
                        $status = 'hati-hati';
                        $status_text = 'HATI-HATI';
                        $progress_class = 'bg-warning';
                    }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="anggaran-card <?php echo $status; ?>">
                        <div class="kategori-header">
                            <div class="kategori-name" style="color: <?php echo $a['warna']; ?>">
                                <?php echo $a['ikon'] . ' ' . $a['nama_kategori']; ?>
                            </div>
                            <span class="status-badge status-<?php echo $status; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                        
                        <div class="progress progress-custom">
                            <div class="progress-bar progress-bar-custom <?php echo $progress_class; ?>" 
                                 role="progressbar" 
                                 style="width: <?php echo min($persen, 100); ?>%">
                                <?php echo number_format($persen, 0); ?>%
                            </div>
                        </div>
                        
                        <div class="anggaran-info">
                            <div class="info-row">
                                <span class="info-label">Anggaran:</span>
                                <strong class="info-value"><?php echo rupiah($a['jumlah']); ?></strong>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Terpakai:</span>
                                <strong class="info-value text-primary"><?php echo rupiah($a['terpakai']); ?></strong>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Sisa:</span>
                                <strong class="info-value" style="color: <?php echo $a['jumlah'] - $a['terpakai'] < 0 ? '#dc3545' : '#28a745'; ?>">
                                    <?php echo rupiah($a['jumlah'] - $a['terpakai']); ?>
                                </strong>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="?hapus=<?php echo $a['id_anggaran']; ?>&bulan=<?php echo $bulan; ?>" 
                               class="btn btn-danger w-100" 
                               onclick="return confirm('Yakin ingin menghapus anggaran ini?')">
                                <i class="bi bi-trash"></i> Hapus Anggaran
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #dee2e6;"></i>
                <p class="text-muted mt-3">Belum ada anggaran untuk bulan ini.</p>
                <p class="text-muted">Tambahkan anggaran menggunakan form di atas.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>