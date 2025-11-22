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

// Proses tambah target
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $nama_target = escape($_POST['nama']);
    $deskripsi = escape($_POST['deskripsi']);
    $jumlah_target = floatval($_POST['jumlah_target']);
    $tanggal_target = escape($_POST['tanggal_target']);
    
    if (empty($nama_target) || $jumlah_target <= 0) {
        $error = 'Nama dan jumlah target wajib diisi!';
    } else {
        $query = "INSERT INTO target_tabungan (id_pengguna, nama, deskripsi, jumlah_target, tanggal_target) 
                  VALUES ($user_id, '$nama_target', '$deskripsi', $jumlah_target, '$tanggal_target')";
        
        if (mysqli_query($conn, $query)) {
            $success = 'Target berhasil ditambahkan!';
        } else {
            $error = 'Gagal menambahkan target!';
        }
    }
}

// Proses hapus target
if (isset($_GET['hapus'])) {
    $id_target = intval($_GET['hapus']);
    mysqli_query($conn, "DELETE FROM target_tabungan WHERE id_target = $id_target AND id_pengguna = $user_id");
    header("Location: target.php");
    exit();
}

// Proses update status
if (isset($_GET['selesai'])) {
    $id_target = intval($_GET['selesai']);
    mysqli_query($conn, "UPDATE target_tabungan SET status = 'selesai' WHERE id_target = $id_target AND id_pengguna = $user_id");
    header("Location: target.php");
    exit();
}

// Ambil daftar target
$sql_target = "SELECT * FROM target_tabungan 
               WHERE id_pengguna = $user_id 
               ORDER BY status ASC, (jumlah_terkumpul / jumlah_target * 100) DESC";
$result_target = mysqli_query($conn, $sql_target);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Target Tabungan - CampussWallet</title>
    
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
        
        .target-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .target-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #11998e, #38ef7d);
        }
        
        .target-card.selesai {
            background: #f8f9fa;
            opacity: 0.8;
        }
        
        .target-card.selesai::before {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
        }
        
        .target-card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            transform: translateY(-5px);
        }
        
        .target-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .target-name {
            font-size: 1.4rem;
            font-weight: bold;
            color: #333;
        }
        
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .status-aktif { background: #d4edda; color: #155724; }
        .status-selesai { background: #cce5ff; color: #004085; }
        
        .target-desc {
            color: #6c757d;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .progress-custom {
            height: 35px;
            border-radius: 20px;
            background: #e9ecef;
            margin: 20px 0;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .progress-bar-custom {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 20px 0;
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .info-label {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: 700;
            color: #333;
            font-size: 1.1rem;
        }
        
        .deadline-warning {
            color: #dc3545;
        }
        
        .deadline-safe {
            color: #28a745;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="target.php">
                <i class="bi bi-bullseye"></i> Target Tabungan
            </a>
            <a href="dashboard.php" class="btn btn-back">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container-fluid py-4">
        <!-- Form Tambah Target -->
        <div class="section-card">
            <h2><i class="bi bi-plus-circle"></i> Tambah Target Baru</h2>
            
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
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label"><i class="bi bi-tag"></i> Nama Target</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Contoh: Laptop Baru">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label"><i class="bi bi-cash-stack"></i> Jumlah Target (Rp)</label>
                        <input type="number" name="jumlah_target" class="form-control" required min="0" step="1000" placeholder="5000000">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label"><i class="bi bi-calendar-check"></i> Tanggal Target</label>
                        <input type="date" name="tanggal_target" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" name="tambah" class="btn btn-success w-100">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <label class="form-label"><i class="bi bi-text-left"></i> Deskripsi (Opsional)</label>
                        <textarea name="deskripsi" class="form-control" rows="2" placeholder="Deskripsi target tabungan"></textarea>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Daftar Target -->
        <div class="section-card">
            <h2><i class="bi bi-list-stars"></i> Daftar Target Tabungan</h2>
            
            <?php if (mysqli_num_rows($result_target) > 0): ?>
            <div class="row g-4">
                <?php while ($t = mysqli_fetch_assoc($result_target)): 
                    $persen = ($t['jumlah_terkumpul'] / $t['jumlah_target']) * 100;
                    $hari_tersisa = floor((strtotime($t['tanggal_target']) - time()) / 86400);
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="target-card <?php echo $t['status'] == 'selesai' ? 'selesai' : ''; ?>">
                        <div class="target-header">
                            <div class="target-name"><?php echo $t['nama']; ?></div>
                            <span class="status-badge status-<?php echo $t['status']; ?>">
                                <?php echo $t['status'] == 'aktif' ? 'AKTIF' : 'SELESAI'; ?>
                            </span>
                        </div>
                        
                        <?php if ($t['deskripsi']): ?>
                        <div class="target-desc">
                            <i class="bi bi-info-circle"></i> <?php echo $t['deskripsi']; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="progress progress-custom">
                            <div class="progress-bar progress-bar-custom" 
                                 role="progressbar" 
                                 style="width: <?php echo min($persen, 100); ?>%">
                                <?php echo number_format($persen, 1); ?>%
                            </div>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-box">
                                <div class="info-label">
                                    <i class="bi bi-wallet2"></i> Terkumpul
                                </div>
                                <div class="info-value text-success">
                                    <?php echo rupiah($t['jumlah_terkumpul']); ?>
                                </div>
                            </div>
                            
                            <div class="info-box">
                                <div class="info-label">
                                    <i class="bi bi-bullseye"></i> Target
                                </div>
                                <div class="info-value text-primary">
                                    <?php echo rupiah($t['jumlah_target']); ?>
                                </div>
                            </div>
                            
                            <div class="info-box">
                                <div class="info-label">
                                    <i class="bi bi-currency-dollar"></i> Sisa
                                </div>
                                <div class="info-value text-warning">
                                    <?php echo rupiah($t['jumlah_target'] - $t['jumlah_terkumpul']); ?>
                                </div>
                            </div>
                            
                            <div class="info-box">
                                <div class="info-label">
                                    <i class="bi bi-calendar-event"></i> Deadline
                                </div>
                                <div class="info-value <?php echo $hari_tersisa < 30 ? 'deadline-warning' : 'deadline-safe'; ?>">
                                    <?php echo $hari_tersisa; ?> hari
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($t['status'] == 'aktif'): ?>
                        <div class="d-flex gap-2 mt-3">
                            <?php if ($persen >= 100): ?>
                            <a href="?selesai=<?php echo $t['id_target']; ?>" 
                               class="btn btn-success flex-fill"
                               onclick="return confirm('Tandai target ini sebagai selesai?')">
                                <i class="bi bi-check-circle"></i> Selesai
                            </a>
                            <?php endif; ?>
                            <a href="?hapus=<?php echo $t['id_target']; ?>" 
                               class="btn btn-danger flex-fill"
                               onclick="return confirm('Yakin ingin menghapus target ini?')">
                                <i class="bi bi-trash"></i> Hapus
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #dee2e6;"></i>
                <p class="text-muted mt-3">Belum ada target tabungan.</p>
                <p class="text-muted">Mulai menabung dengan menambahkan target di atas!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>