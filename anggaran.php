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

/// Ambil daftar anggaran
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
    <title>Anggaran - CampussWallet</title>
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
        .filter-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-form input,
        .filter-form button {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .filter-form button {
            background: #667eea;
            color: white;
            border: none;
            cursor: pointer;
        }
        .anggaran-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }
        .anggaran-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
        }
        .anggaran-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .kategori-name {
            font-size: 18px;
            font-weight: bold;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-aman { background: #d4edda; color: #155724; }
        .status-hati-hati { background: #fff3cd; color: #856404; }
        .status-bahaya { background: #f8d7da; color: #721c24; }
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 25px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
            transition: width 0.3s;
        }
        .progress-fill.aman { background: #28a745; }
        .progress-fill.hati-hati { background: #ffc107; }
        .progress-fill.bahaya { background: #dc3545; }
        .anggaran-info {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin: 5px 0;
            color: #666;
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
        .btn-danger {
            background: #dc3545;
            color: white;
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
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-primary {
            background: #28a745;
            color: white;
            width: 100%;
            padding: 12px;
        }
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
        <h1>📊 Anggaran Bulanan</h1>
        <div>
            <a href="dashboard.php">← Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Form Tambah Anggaran -->
        <div class="section">
            <h2>Tambah Anggaran Baru</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px;">
                    <div class="form-group">
                        <label>Kategori Pengeluaran</label>
                        <select name="id_kategori" required>
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
                    
                    <div class="form-group">
                        <label>Jumlah (Rp)</label>
                        <input type="number" name="jumlah" required min="0" step="1000">
                    </div>
                    
                    <div class="form-group">
                        <label>Bulan</label>
                        <input type="month" name="bulan" required value="<?php echo $bulan; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Batas (%)</label>
                        <input type="number" name="batas_peringatan" required value="80" min="0" max="100">
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" name="tambah" class="btn btn-primary">Tambah</button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Filter Bulan -->
        <div class="section">
            <form method="GET" action="" class="filter-form">
                <input type="month" name="bulan" value="<?php echo $bulan; ?>">
                <button type="submit">Filter</button>
            </form>
            
            <h2>Daftar Anggaran</h2>
            
            <?php if (mysqli_num_rows($result_anggaran) > 0): ?>
            <div class="anggaran-grid">
                <?php while ($a = mysqli_fetch_assoc($result_anggaran)): 
                    $persen = ($a['terpakai'] / $a['jumlah']) * 100;
                    $status = 'aman';
                    $status_text = 'AMAN';
                    
                    if ($persen >= $a['batas_peringatan']) {
                        $status = 'bahaya';
                        $status_text = 'BAHAYA';
                    } elseif ($persen >= 50) {
                        $status = 'hati-hati';
                        $status_text = 'HATI-HATI';
                    }
                ?>
                <div class="anggaran-card">
                    <div class="anggaran-header">
                        <div class="kategori-name" style="color: <?php echo $a['warna']; ?>">
                            <?php echo $a['ikon'] . ' ' . $a['nama_kategori']; ?>
                        </div>
                        <span class="status-badge status-<?php echo $status; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </div>
                    
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo $status; ?>" 
                             style="width: <?php echo min($persen, 100); ?>%">
                            <?php echo number_format($persen, 0); ?>%
                        </div>
                    </div>
                    
                    <div class="anggaran-info">
                        <span>Anggaran:</span>
                        <strong><?php echo rupiah($a['jumlah']); ?></strong>
                    </div>
                    <div class="anggaran-info">
                        <span>Terpakai:</span>
                        <strong><?php echo rupiah($a['terpakai']); ?></strong>
                    </div>
                    <div class="anggaran-info">
                        <span>Sisa:</span>
                        <strong style="color: <?php echo $a['jumlah'] - $a['terpakai'] < 0 ? '#dc3545' : '#28a745'; ?>">
                            <?php echo rupiah($a['jumlah'] - $a['terpakai']); ?>
                        </strong>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <a href="?hapus=<?php echo $a['id_anggaran']; ?>&bulan=<?php echo $bulan; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Yakin ingin menghapus anggaran ini?')">
                            Hapus
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="no-data">
                Belum ada anggaran untuk bulan ini.<br>
                Tambahkan anggaran di form di atas.
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>