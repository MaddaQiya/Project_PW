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
$error = '';
$success = '';

// Ambil data transaksi LAMA (untuk perhitungan target)
$sql_transaksi = "SELECT * FROM transaksi WHERE id_transaksi = $id_transaksi AND id_pengguna = $user_id";
$result = mysqli_query($conn, $sql_transaksi);

if (mysqli_num_rows($result) == 0) {
    header('Location: dashboard.php');
    exit();
}

$transaksi_lama = mysqli_fetch_assoc($result); // ← SIMPAN DATA LAMA

// Ambil kategori
$sql_kategori = "SELECT * FROM kategori ORDER BY jenis, nama";
$kategori = mysqli_query($conn, $sql_kategori);

// Ambil target aktif
$sql_target = "SELECT * FROM target_tabungan WHERE id_pengguna = $user_id AND status = 'aktif'";
$target = mysqli_query($conn, $sql_target);

// ===== PROSES UPDATE =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id_kategori = intval($_POST['id_kategori']);
    $jumlah_baru = floatval($_POST['jumlah']);
    $jenis_transaksi = escape($_POST['jenis_transaksi']);
    $tanggal = escape($_POST['tanggal_transaksi']);
    $catatan = escape($_POST['catatan']);
    $nama_akun = escape($_POST['nama_akun']);
    $jenis_akun = escape($_POST['jenis_akun']);
    $id_target_baru = isset($_POST['id_target']) && $_POST['id_target'] ? intval($_POST['id_target']) : NULL;
    
    // Validasi
    if ($jumlah_baru <= 0) {
        $error = 'Jumlah harus lebih dari 0!';
    } else {
        // ===== BAGIAN BARU: UPDATE TARGET TABUNGAN =====
        
        // 1. Ambil data lama
        $jumlah_lama = floatval($transaksi_lama['jumlah']);
        $id_target_lama = $transaksi_lama['id_target'];
        $jenis_lama = $transaksi_lama['jenis_transaksi'];
        
        // 2. Jika transaksi LAMA terhubung dengan target, KURANGI dulu
        if ($id_target_lama && $jenis_lama == 'pengeluaran') {
            mysqli_query($conn, "UPDATE target_tabungan 
                                 SET jumlah_terkumpul = jumlah_terkumpul - $jumlah_lama 
                                 WHERE id_target = $id_target_lama");
        }
        
        // 3. Update transaksi
        $query = "UPDATE transaksi SET 
                  id_kategori = $id_kategori,
                  jumlah = $jumlah_baru,
                  jenis_transaksi = '$jenis_transaksi',
                  tanggal_transaksi = '$tanggal',
                  catatan = '$catatan',
                  nama_akun = '$nama_akun',
                  jenis_akun = '$jenis_akun',
                  id_target = " . ($id_target_baru ? $id_target_baru : 'NULL') . "
                  WHERE id_transaksi = $id_transaksi AND id_pengguna = $user_id";
        
        if (mysqli_query($conn, $query)) {
            // 4. Jika transaksi BARU terhubung dengan target, TAMBAH
            if ($id_target_baru && $jenis_transaksi == 'pengeluaran') {
                mysqli_query($conn, "UPDATE target_tabungan 
                                     SET jumlah_terkumpul = jumlah_terkumpul + $jumlah_baru 
                                     WHERE id_target = $id_target_baru");
            }
            
            $success = 'Transaksi berhasil diupdate!';
            header("refresh:1;url=dashboard.php");
        } else {
            $error = 'Gagal mengupdate transaksi!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi - CampussWallet</title>
    
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
        
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 35px;
            margin-top: 30px;
        }
        
        .form-card h2 {
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary-color);
            font-weight: 700;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
        }
        
        .btn-update {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            color: white;
        }
        
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 193, 7, 0.4);
            color: white;
        }
        
        .jenis-transaksi-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .jenis-option {
            flex: 1;
        }
        
        .jenis-option input[type="radio"] {
            display: none;
        }
        
        .jenis-label {
            display: block;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .jenis-label i {
            font-size: 2rem;
            margin-bottom: 8px;
        }
        
        .jenis-option input[type="radio"]:checked + .jenis-label.pemasukan {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .jenis-option input[type="radio"]:checked + .jenis-label.pengeluaran {
            border-color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-pencil-square"></i> Edit Transaksi
            </a>
            <a href="dashboard.php" class="btn btn-back">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container" style="max-width: 800px;">
        <div class="form-card">
            <h2><i class="bi bi-pencil-fill"></i> Edit Transaksi</h2>
            
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
                <!-- Jenis Transaksi -->
                <div class="mb-4">
                    <label class="form-label">Jenis Transaksi *</label>
                    <div class="jenis-transaksi-selector">
                        <div class="jenis-option">
                            <input type="radio" name="jenis_transaksi" value="pemasukan" id="pemasukan" 
                                   <?php echo $transaksi_lama['jenis_transaksi'] == 'pemasukan' ? 'checked' : ''; ?>>
                            <label for="pemasukan" class="jenis-label pemasukan">
                                <i class="bi bi-arrow-down-circle-fill"></i>
                                <div>Pemasukan</div>
                            </label>
                        </div>
                        <div class="jenis-option">
                            <input type="radio" name="jenis_transaksi" value="pengeluaran" id="pengeluaran"
                                   <?php echo $transaksi_lama['jenis_transaksi'] == 'pengeluaran' ? 'checked' : ''; ?>>
                            <label for="pengeluaran" class="jenis-label pengeluaran">
                                <i class="bi bi-arrow-up-circle-fill"></i>
                                <div>Pengeluaran</div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Kategori -->
                <div class="mb-3">
                    <label for="id_kategori" class="form-label">
                        <i class="bi bi-tag-fill"></i> Kategori *
                    </label>
                    <select class="form-select" id="id_kategori" name="id_kategori" required>
                        <option value="">Pilih Kategori</option>
                        <?php 
                        mysqli_data_seek($kategori, 0);
                        while ($k = mysqli_fetch_assoc($kategori)): 
                        ?>
                            <option value="<?php echo $k['id_kategori']; ?>" 
                                    data-jenis="<?php echo $k['jenis']; ?>"
                                    <?php echo $transaksi_lama['id_kategori'] == $k['id_kategori'] ? 'selected' : ''; ?>>
                                <?php echo $k['ikon'] . ' ' . $k['nama']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <!-- Jumlah & Tanggal -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="jumlah" class="form-label">
                            <i class="bi bi-cash"></i> Jumlah (Rp) *
                        </label>
                        <input type="number" class="form-control" id="jumlah" name="jumlah" 
                               required min="0" step="1000" 
                               value="<?php echo $transaksi_lama['jumlah']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="tanggal_transaksi" class="form-label">
                            <i class="bi bi-calendar-event"></i> Tanggal *
                        </label>
                        <input type="date" class="form-control" id="tanggal_transaksi" 
                               name="tanggal_transaksi" required 
                               value="<?php echo $transaksi_lama['tanggal_transaksi']; ?>">
                    </div>
                </div>
                
                <!-- Catatan -->
                <div class="mb-3">
                    <label for="catatan" class="form-label">
                        <i class="bi bi-journal-text"></i> Catatan
                    </label>
                    <textarea class="form-control" id="catatan" name="catatan" rows="2"><?php echo $transaksi_lama['catatan']; ?></textarea>
                </div>
                
                <!-- Nama Akun & Jenis Akun -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nama_akun" class="form-label">
                            <i class="bi bi-wallet2"></i> Nama Akun *
                        </label>
                        <input type="text" class="form-control" id="nama_akun" name="nama_akun" 
                               required value="<?php echo $transaksi_lama['nama_akun']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="jenis_akun" class="form-label">
                            <i class="bi bi-credit-card"></i> Jenis Akun *
                        </label>
                        <select class="form-select" id="jenis_akun" name="jenis_akun" required>
                            <option value="tunai" <?php echo $transaksi_lama['jenis_akun'] == 'tunai' ? 'selected' : ''; ?>>💵 Tunai</option>
                            <option value="bank" <?php echo $transaksi_lama['jenis_akun'] == 'bank' ? 'selected' : ''; ?>>🏦 Bank</option>
                            <option value="ewallet" <?php echo $transaksi_lama['jenis_akun'] == 'ewallet' ? 'selected' : ''; ?>>📱 E-Wallet</option>
                        </select>
                    </div>
                </div>
                
                <!-- Target Tabungan -->
                <?php if (mysqli_num_rows($target) > 0): ?>
                <div class="mb-3" id="target-section" style="display: <?php echo $transaksi_lama['jenis_transaksi'] == 'pengeluaran' ? 'block' : 'none'; ?>">
                    <label for="id_target" class="form-label">
                        <i class="bi bi-bullseye"></i> Transfer ke Target Tabungan (Opsional)
                    </label>
                    <select class="form-select" id="id_target" name="id_target">
                        <option value="">Tidak ada target</option>
                        <?php 
                        mysqli_data_seek($target, 0);
                        while ($t = mysqli_fetch_assoc($target)): 
                        ?>
                            <option value="<?php echo $t['id_target']; ?>"
                                    <?php echo $transaksi_lama['id_target'] == $t['id_target'] ? 'selected' : ''; ?>>
                                <?php echo $t['nama']; ?> 
                                (<?php echo rupiah($t['jumlah_terkumpul']); ?> / <?php echo rupiah($t['jumlah_target']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <!-- Submit Button -->
                <button type="submit" name="update" class="btn btn-update mt-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Update Transaksi
                </button>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Filter kategori & toggle target section
        document.addEventListener('DOMContentLoaded', function() {
            const radioButtons = document.querySelectorAll('input[name="jenis_transaksi"]');
            const selectKategori = document.getElementById('id_kategori');
            const allOptions = Array.from(selectKategori.querySelectorAll('option'));
            const targetSection = document.getElementById('target-section');
            
            function filterKategori() {
                const jenisTransaksi = document.querySelector('input[name="jenis_transaksi"]:checked').value;
                const currentValue = selectKategori.value;
                
                selectKategori.innerHTML = '<option value="">Pilih Kategori</option>';
                
                allOptions.forEach(option => {
                    if (option.value === '') return;
                    const jenis = option.getAttribute('data-jenis');
                    if (jenis === jenisTransaksi) {
                        selectKategori.appendChild(option.cloneNode(true));
                    }
                });
                
                // Restore selected value if still valid
                if (selectKategori.querySelector(`option[value="${currentValue}"]`)) {
                    selectKategori.value = currentValue;
                }
                
                // Toggle target section
                if (targetSection) {
                    targetSection.style.display = jenisTransaksi === 'pengeluaran' ? 'block' : 'none';
                    if (jenisTransaksi !== 'pengeluaran') {
                        document.getElementById('id_target').value = '';
                    }
                }
            }
            
            radioButtons.forEach(radio => {
                radio.addEventListener('change', filterKategori);
            });
            
            filterKategori();
        });
    </script>
</body>
</html>