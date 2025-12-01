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
    
    // Validasi server-side
    if ($jumlah <= 0) {
        $error = 'Jumlah harus lebih dari 0!';
    } elseif ($id_kategori <= 0) {
        $error = 'Silakan pilih kategori yang valid!';
    } elseif (empty($nama_akun) || strlen($nama_akun) < 2) {
        $error = 'Nama akun minimal 2 karakter!';
    } elseif (!in_array($jenis_transaksi, ['pemasukan', 'pengeluaran'])) {
        $error = 'Jenis transaksi tidak valid!';
    } elseif (!in_array($jenis_akun, ['tunai', 'bank', 'ewallet'])) {
        $error = 'Jenis akun tidak valid!';
    } elseif (empty($tanggal) || !strtotime($tanggal)) {
        $error = 'Tanggal tidak valid!';
    } else {
        // Validasi kategori sesuai jenis transaksi
        $check_kategori = mysqli_query($conn, "SELECT jenis FROM kategori WHERE id_kategori = $id_kategori");
        if (mysqli_num_rows($check_kategori) == 0) {
            $error = 'Kategori tidak ditemukan!';
        } else {
            $kat = mysqli_fetch_assoc($check_kategori);
            if ($kat['jenis'] != $jenis_transaksi) {
                $error = 'Kategori tidak sesuai dengan jenis transaksi!';
            } else {
                // Validasi target hanya untuk pengeluaran (transfer dana)
                if ($id_target && $jenis_transaksi != 'pengeluaran') {
                    $error = 'Transfer ke target tabungan hanya berlaku untuk pengeluaran!';
                } else {
                    // Insert transaksi
                    $query = "INSERT INTO transaksi (id_pengguna, id_kategori, jumlah, jenis_transaksi, 
                              tanggal_transaksi, catatan, nama_akun, jenis_akun, id_target) 
                              VALUES ($user_id, $id_kategori, $jumlah, '$jenis_transaksi', 
                              '$tanggal', '$catatan', '$nama_akun', '$jenis_akun', " . 
                              ($id_target ? $id_target : 'NULL') . ")";
                    
                    if (mysqli_query($conn, $query)) {
                        // Jika ada target dan jenis pengeluaran, update jumlah terkumpul (transfer dana)
                        if ($id_target && $jenis_transaksi == 'pengeluaran') {
                            mysqli_query($conn, "UPDATE target_tabungan 
                                                 SET jumlah_terkumpul = jumlah_terkumpul + $jumlah 
                                                 WHERE id_target = $id_target");
                        }
                        
                        $success = 'Transaksi berhasil ditambahkan!';
                        header("refresh:1;url=dashboard.php");
                    } else {
                        $error = 'Gagal menambahkan transaksi: ' . mysqli_error($conn);
                    }
                }
            }
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
        
        .jenis-transaksi-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .jenis-option {
            flex: 1;
            position: relative;
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
            display: block;
            margin-bottom: 8px;
        }
        
        .jenis-label span {
            font-weight: 600;
            font-size: 0.95rem;
        }
        .jenis-option input[type="radio"]:checked + .jenis-label {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        }
        
        .jenis-option input[type="radio"]:checked + .jenis-label.pemasukan {
            border-color: #28a745;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(56, 239, 125, 0.1));
            color: #28a745;
        }
        
        .jenis-option input[type="radio"]:checked + .jenis-label.pengeluaran {
            border-color: #dc3545;
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(244, 92, 67, 0.1));
            color: #dc3545;
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
        
        .btn-submit {
            background: linear-gradient(135deg, #28a745, #38ef7d);
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            color: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .info-badge {
            background: #e7f3ff;
            border-left: 4px solid #0d6efd;
            padding: 12px 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 0.9rem;
            color: #004085;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-plus-circle-fill"></i> Tambah Transaksi
            </a>
            <a href="dashboard.php" class="btn btn-back">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container" style="max-width: 800px;">
        <div class="form-card">
            <h2><i class="bi bi-receipt"></i> Form Transaksi Baru</h2>
            
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
                            <input type="radio" name="jenis_transaksi" value="pemasukan" id="pemasukan" checked>
                            <label for="pemasukan" class="jenis-label pemasukan">
                                <i class="bi bi-arrow-down-circle-fill text-success"></i>
                                <span>Pemasukan</span>
                            </label>
                        </div>
                        <div class="jenis-option">
                            <input type="radio" name="jenis_transaksi" value="pengeluaran" id="pengeluaran">
                            <label for="pengeluaran" class="jenis-label pengeluaran">
                                <i class="bi bi-arrow-up-circle-fill text-danger"></i>
                                <span>Pengeluaran</span>
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
                            <option value="<?php echo $k['id_kategori']; ?>" data-jenis="<?php echo $k['jenis']; ?>">
                                <?php echo $k['ikon'] . ' ' . $k['nama']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <!-- Row: Jumlah & Tanggal -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="jumlah" class="form-label">
                            <i class="bi bi-cash"></i> Jumlah (Rp) *
                        </label>
                        <input type="number" class="form-control" id="jumlah" name="jumlah" 
                               required min="0" step="1000" placeholder="50000">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="tanggal_transaksi" class="form-label">
                            <i class="bi bi-calendar-event"></i> Tanggal *
                        </label>
                        <input type="date" class="form-control" id="tanggal_transaksi" 
                               name="tanggal_transaksi" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <!-- Catatan -->
                <div class="mb-3">
                    <label for="catatan" class="form-label">
                        <i class="bi bi-journal-text"></i> Catatan
                    </label>
                    <textarea class="form-control" id="catatan" name="catatan" rows="2" 
                              placeholder="Deskripsi transaksi (opsional)"></textarea>
                </div>
                
                <!-- Row: Nama Akun & Jenis Akun -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nama_akun" class="form-label">
                            <i class="bi bi-wallet2"></i> Nama Akun *
                        </label>
                        <input type="text" class="form-control" id="nama_akun" name="nama_akun" 
                               required placeholder="Contoh: Dompet, BNI, GoPay">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="jenis_akun" class="form-label">
                            <i class="bi bi-credit-card"></i> Jenis Akun *
                        </label>
                        <select class="form-select" id="jenis_akun" name="jenis_akun" required>
                            <option value="tunai">💵 Tunai</option>
                            <option value="bank">🏦 Bank</option>
                            <option value="ewallet">📱 E-Wallet</option>
                        </select>
                    </div>
                </div>
                
                <!-- Target Tabungan - Transfer Dana (PENGELUARAN) -->
                <?php if (mysqli_num_rows($target) > 0): ?>
                <div class="mb-3" id="target-section" style="display: none;">
                    <label for="id_target" class="form-label">
                        <i class="bi bi-bullseye"></i> Transfer ke Target Tabungan (Opsional)
                    </label>
                    <select class="form-select" id="id_target" name="id_target">
                        <option value="">Tidak ada target</option>
                        <?php 
                        mysqli_data_seek($target, 0);
                        while ($t = mysqli_fetch_assoc($target)): 
                        ?>
                            <option value="<?php echo $t['id_target']; ?>">
                                <?php echo $t['nama']; ?> 
                                (<?php echo rupiah($t['jumlah_terkumpul']); ?> / <?php echo rupiah($t['jumlah_target']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <div class="info-badge mt-2">
                        <i class="bi bi-info-circle-fill"></i>
                        <strong>Transfer ke tabungan:</strong> Uang akan dipindahkan dari saldo utama ke target tabungan. Ini adalah PENGELUARAN sehingga saldo utama akan berkurang.
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Submit Button -->
                <button type="submit" name="simpan" class="btn btn-submit mt-4">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Simpan Transaksi
                </button>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Filter kategori berdasarkan jenis transaksi
        document.addEventListener('DOMContentLoaded', function() {
            const radioButtons = document.querySelectorAll('input[name="jenis_transaksi"]');
            const selectKategori = document.getElementById('id_kategori');
            const allOptions = Array.from(selectKategori.querySelectorAll('option'));
            const targetSection = document.getElementById('target-section');
            
            function filterKategori() {
                const jenisTransaksi = document.querySelector('input[name="jenis_transaksi"]:checked').value;
                
                // Reset select
                selectKategori.innerHTML = '<option value="">Pilih Kategori</option>';
                
                // Filter dan tambahkan option yang sesuai
                allOptions.forEach(option => {
                    if (option.value === '') {
                        return;
                    }
                    
                    const jenis = option.getAttribute('data-jenis');
                    if (jenis === jenisTransaksi) {
                        selectKategori.appendChild(option.cloneNode(true));
                    }
                });
                
                // Tampilkan section target hanya untuk PENGELUARAN (transfer dana)
                if (targetSection) {
                    if (jenisTransaksi === 'pengeluaran') {
                        targetSection.style.display = 'block';
                    } else {
                        targetSection.style.display = 'none';
                        document.getElementById('id_target').value = '';
                    }
                }
            }
            
            // Event listener untuk radio buttons
            radioButtons.forEach(radio => {
                radio.addEventListener('change', filterKategori);
            });
            
            // Filter saat halaman pertama kali load
            filterKategori();
        });
        
        // Validasi form sebelum submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const jumlah = parseFloat(document.getElementById('jumlah').value);
            const namaAkun = document.getElementById('nama_akun').value.trim();
            const kategori = document.getElementById('id_kategori').value;
            
            // Validasi jumlah
            if (jumlah <= 0 || isNaN(jumlah)) {
                e.preventDefault();
                alert('Jumlah harus lebih dari 0!');
                document.getElementById('jumlah').focus();
                return false;
            }
            
            // Validasi kategori
            if (!kategori) {
                e.preventDefault();
                alert('Silakan pilih kategori!');
                document.getElementById('id_kategori').focus();
                return false;
            }
            
            // Validasi nama akun
            if (namaAkun.length < 2) {
                e.preventDefault();
                alert('Nama akun minimal 2 karakter!');
                document.getElementById('nama_akun').focus();
                return false;
            }
             // Konfirmasi sebelum submit
            const jenisTransaksi = document.querySelector('input[name="jenis_transaksi"]:checked').value;
            const jenisText = jenisTransaksi === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';
            const kategoriText = document.getElementById('id_kategori').selectedOptions[0].text;
            
            const confirmMsg = `Konfirmasi transaksi:\n\n` +
                             `Jenis: ${jenisText}\n` +
                             `Kategori: ${kategoriText}\n` +
                             `Jumlah: Rp ${jumlah.toLocaleString('id-ID')}\n` +
                             `Akun: ${namaAkun}\n\n` +
                             `Lanjutkan?`;
            
            if (!confirm(confirmMsg)) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });

        // Format input jumlah dengan pemisah ribuan
        document.getElementById('jumlah').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value) {
                e.target.value = value;
            }
        });
    </script>
</body>
</html>