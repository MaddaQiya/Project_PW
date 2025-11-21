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

// Ambil daftar kategori
$sql_kategori = "SELECT * FROM kategori ORDER BY jenis, nama";
$kategori = mysqli_query($conn, $sql_kategori);

// Ambil daftar target aktif
$sql_target = "SELECT * FROM target_tabungan WHERE id_pengguna = $user_id AND status = 'aktif'";
$target = mysqli_query($conn, $sql_target);

// Proses form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan'])) {
    $id_kategori = intval($_POST['id_kategori']);
    $jumlah = floatval($_POST['jumlah']);
    $jenis_transaksi = escape($_POST['jenis_transaksi']);
    $tanggal = escape($_POST['tanggal_transaksi']);
    $catatan = escape($_POST['catatan']);
    $nama_akun = escape($_POST['nama_akun']);
    $jenis_akun = escape($_POST['jenis_akun']);
    $id_target = isset($_POST['id_target']) && $_POST['id_target'] ? intval($_POST['id_target']) : NULL;
    
    if ($jumlah <= 0) {
        $error = 'Jumlah harus lebih dari 0!';
    } else {
        // Insert transaksi
        $query = "INSERT INTO transaksi (id_pengguna, id_kategori, jumlah, jenis_transaksi, 
                  tanggal_transaksi, catatan, nama_akun, jenis_akun, id_target) 
                  VALUES ($user_id, $id_kategori, $jumlah, '$jenis_transaksi', 
                  '$tanggal', '$catatan', '$nama_akun', '$jenis_akun', " . 
                  ($id_target ? $id_target : 'NULL') . ")";
        
        if (mysqli_query($conn, $query)) {
            // Jika ada target dan jenis pemasukan, update jumlah terkumpul
            if ($id_target && $jenis_transaksi == 'pemasukan') {
                mysqli_query($conn, "UPDATE target_tabungan 
                                     SET jumlah_terkumpul = jumlah_terkumpul + $jumlah 
                                     WHERE id_target = $id_target");
            }
            
            $success = 'Transaksi berhasil ditambahkan!';
            header("refresh:1;url=dashboard.php");
        } else {
            $error = 'Gagal menambahkan transaksi!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Transaksi - CampussWallet</title>
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
        .btn-back {
            background: white;
            color: #667eea;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        .container {
            max-width: 700px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .btn {
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #28a745;
            color: white;
            width: 100%;
        }
        .btn-primary:hover {
            background: #218838;
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success {
            background: #efe;
            color: #3c3;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .radio-group {
            display: flex;
            gap: 20px;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Tambah Transaksi Baru</h1>
        <a href="dashboard.php" class="btn-back">← Kembali</a>
    </div>
    
    <div class="container">
        <div class="form-container">
            <h2>Tambah Transaksi</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Jenis Transaksi *</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="jenis_transaksi" value="pemasukan" checked>
                            💰 Pemasukan
                        </label>
                        <label>
                            <input type="radio" name="jenis_transaksi" value="pengeluaran">
                            💸 Pengeluaran
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Kategori *</label>
                    <select name="id_kategori" required>
                        <option value="">Pilih Kategori</option>
                        <?php 
                        $current_jenis = '';
                        while ($k = mysqli_fetch_assoc($kategori)): 
                            if ($current_jenis !== $k['jenis']) {
                                if ($current_jenis !== '') echo '</optgroup>';
                                echo '<optgroup label="' . ucfirst($k['jenis']) . '">';
                                $current_jenis = $k['jenis'];
                            }
                        ?>
                            <option value="<?php echo $k['id_kategori']; ?>" data-jenis="<?php echo $k['jenis']; ?>">
                                <?php echo $k['ikon'] . ' ' . $k['nama']; ?>
                            </option>
                        <?php endwhile; ?>
                        </optgroup>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Jumlah (Rp) *</label>
                    <input type="number" name="jumlah" required min="0" step="1000" 
                           placeholder="Contoh: 50000">
                </div>
                
                <div class="form-group">
                    <label>Tanggal *</label>
                    <input type="date" name="tanggal_transaksi" required 
                           value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="catatan" rows="2" 
                              placeholder="Deskripsi transaksi (opsional)"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Akun *</label>
                        <input type="text" name="nama_akun" required 
                               placeholder="Contoh: Dompet, BNI, GoPay">
                    </div>
                    <div class="form-group">
                        <label>Jenis Akun *</label>
                        <select name="jenis_akun" required>
                            <option value="tunai">💵 Tunai</option>
                            <option value="bank">🏦 Bank</option>
                            <option value="ewallet">📱 E-Wallet</option>
                        </select>
                    </div>
                </div>
                
                <?php if (mysqli_num_rows($target) > 0): ?>
                <div class="form-group">
                    <label>🎯 Link ke Target Tabungan (opsional)</label>
                    <select name="id_target">
                        <option value="">Tidak ada target</option>
                        <?php while ($t = mysqli_fetch_assoc($target)): ?>
                            <option value="<?php echo $t['id_target']; ?>">
                                <?php echo $t['nama']; ?> 
                                (<?php echo rupiah($t['jumlah_terkumpul']); ?> / <?php echo rupiah($t['jumlah_target']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small style="color: #666;">Hanya berlaku untuk pemasukan</small>
                </div>
                <?php endif; ?>
                
                <button type="submit" name="simpan" class="btn btn-primary">
                    Simpan Transaksi
                </button>
            </form>
        </div>
    </div>
</body>
</html>