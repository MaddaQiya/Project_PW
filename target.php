<?php
require_once 'config.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Proses tambah target
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $nama = escape($_POST['nama']);
    $deskripsi = escape($_POST['deskripsi']);
    $jumlah_target = floatval($_POST['jumlah_target']);
    $tanggal_target = escape($_POST['tanggal_target']);
    
    if (empty($nama) || $jumlah_target <= 0) {
        $error = 'Nama dan jumlah target wajib diisi!';
    } else {
        $query = "INSERT INTO target_tabungan (id_pengguna, nama, deskripsi, jumlah_target, tanggal_target) 
                  VALUES ($user_id, '$nama', '$deskripsi', $jumlah_target, '$tanggal_target')";
        
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
    <title>Target Tabungan - CampussWallet</title>
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
        }
        .navbar h1 { font-size: 20px; }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
        }
        .navbar a:hover {
            background: rgba(255,255,255,0.2);
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: #28a745;
            color: white;
            padding: 12px;
            width: 100%;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .target-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }
        .target-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
        }
        .target-card.selesai {
            background: #f8f9fa;
            opacity: 0.7;
        }
        .target-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .target-desc {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 30px;
            overflow: hidden;
            margin: 15px 0;
        }
        .progress-fill {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: bold;
        }
        .target-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 10px 0;
            font-size: 14px;
        }
        .info-box {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .info-label {
            color: #666;
            font-size: 12px;
        }
        .info-value {
            font-weight: bold;
            color: #333;
        }
        .target-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .status-aktif { background: #d4edda; color: #155724; }
        .status-selesai { background: #cce5ff; color: #004085; }
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background: #efe;
            color: #3c3;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🎯 Target Tabungan</h1>
        <div>
            <a href="dashboard.php">← Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Form Tambah Target -->
        <div class="section">
            <h2>Tambah Target Baru</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px;">
                    <div class="form-group">
                        <label>Nama Target</label>
                        <input type="text" name="nama" required placeholder="Contoh: Laptop Baru">
                    </div>
                    
                    <div class="form-group">
                        <label>Jumlah Target (Rp)</label>
                        <input type="number" name="jumlah_target" required min="0" step="1000">
                    </div>
                    
                    <div class="form-group">
                        <label>Tanggal Target</label>
                        <input type="date" name="tanggal_target" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" name="tambah" class="btn btn-primary">Tambah</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi (opsional)</label>
                    <textarea name="deskripsi" rows="2" placeholder="Deskripsi target tabungan"></textarea>
                </div>
            </form>
        </div>
        
        <!-- Daftar Target -->
        <div class="section">
            <h2>Daftar Target</h2>
            
            <?php if (mysqli_num_rows($result_target) > 0): ?>
            <div class="target-grid">
                <?php while ($t = mysqli_fetch_assoc($result_target)): 
                    $persen = ($t['jumlah_terkumpul'] / $t['jumlah_target']) * 100;
                    $hari_tersisa = floor((strtotime($t['tanggal_target']) - time()) / 86400);
                ?>
                <div class="target-card <?php echo $t['status'] == 'selesai' ? 'selesai' : ''; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                        <div class="target-name"><?php echo $t['nama']; ?></div>
                        <span class="status-badge status-<?php echo $t['status']; ?>">
                            <?php echo strtoupper($t['status']); ?>
                        </span>
                    </div>
                    
                    <?php if ($t['deskripsi']): ?>
                    <div class="target-desc"><?php echo $t['deskripsi']; ?></div>
                    <?php endif; ?>
                    
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min($persen, 100); ?>%">
                            <?php echo number_format($persen, 1); ?>%
                        </div>
                    </div>
                    
                    <div class="target-info">
                        <div class="info-box">
                            <div class="info-label">Terkumpul</div>
                            <div class="info-value"><?php echo rupiah($t['jumlah_terkumpul']); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">Target</div>
                            <div class="info-value"><?php echo rupiah($t['jumlah_target']); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">Sisa</div>
                            <div class="info-value"><?php echo rupiah($t['jumlah_target'] - $t['jumlah_terkumpul']); ?></div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">Deadline</div>
                            <div class="info-value" style="color: <?php echo $hari_tersisa < 30 ? '#dc3545' : '#28a745'; ?>">
                                <?php echo $hari_tersisa; ?> hari lagi
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($t['status'] == 'aktif'): ?>
                    <div class="target-actions">
                        <?php if ($persen >= 100): ?>
                        <a href="?selesai=<?php echo $t['id_target']; ?>" class="btn btn-success"
                           onclick="return confirm('Tandai target ini sebagai selesai?')">
                            ✓ Tandai Selesai
                        </a>
                        <?php endif; ?>
                        <a href="?hapus=<?php echo $t['id_target']; ?>" class="btn btn-danger"
                           onclick="return confirm('Yakin ingin menghapus target ini?')">
                            Hapus
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="no-data">
                Belum ada target tabungan.<br>
                Tambahkan target di form di atas.
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>