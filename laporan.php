<?php
require_once 'config.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

// Filter
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : date('n');

// Rekap pemasukan per bulan (1 tahun)
$sql_pemasukan = "SELECT 
                    MONTH(tanggal_transaksi) as bulan,
                    SUM(jumlah) as total
                  FROM transaksi
                  WHERE id_pengguna = $user_id 
                  AND YEAR(tanggal_transaksi) = $tahun 
                  AND jenis_transaksi = 'pemasukan'
                  GROUP BY MONTH(tanggal_transaksi)
                  ORDER BY bulan";
$result_pemasukan = mysqli_query($conn, $sql_pemasukan);

$pemasukan_bulanan = array_fill(1, 12, 0);
while ($row = mysqli_fetch_assoc($result_pemasukan)) {
    $pemasukan_bulanan[$row['bulan']] = $row['total'];
}

// Rekap pengeluaran per kategori (bulan terpilih)
$sql_pengeluaran = "SELECT 
                        k.nama, k.warna, k.ikon, SUM(t.jumlah) as total
                    FROM transaksi t
                    JOIN kategori k ON t.id_kategori = k.id_kategori
                    WHERE t.id_pengguna = $user_id 
                          AND YEAR(t.tanggal_transaksi) = $tahun 
                          AND MONTH(t.tanggal_transaksi) = $bulan
                          AND t.jenis_transaksi = 'pengeluaran'
                    GROUP BY k.id_kategori
                    ORDER BY total DESC";
$result_pengeluaran = mysqli_query($conn, $sql_pengeluaran);

$total_pengeluaran = 0;
$pengeluaran_kategori = [];
while ($row = mysqli_fetch_assoc($result_pengeluaran)) {
    $total_pengeluaran += $row['total'];
    $pengeluaran_kategori[] = $row;
}

// Ringkasan
$sql_ringkasan = "SELECT 
    SUM(CASE WHEN jenis_transaksi = 'pemasukan' THEN jumlah ELSE 0 END) as total_pemasukan,
    SUM(CASE WHEN jenis_transaksi = 'pengeluaran' THEN jumlah ELSE 0 END) as total_pengeluaran
    FROM transaksi 
    WHERE id_pengguna = $user_id 
    AND YEAR(tanggal_transaksi) = $tahun 
    AND MONTH(tanggal_transaksi) = $bulan";
$ringkasan = mysqli_fetch_assoc(mysqli_query($conn, $sql_ringkasan));
$saldo_bulan = $ringkasan['total_pemasukan'] - $ringkasan['total_pengeluaran'];

// Nama bulan
$nama_bulan = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - CampussWallet</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        
        .filter-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 12px;
            padding: 25px;
        }
        
        .summary-card {
            border: none;
            border-radius: 15px;
            padding: 25px;
            color: white;
            text-align: center;
        }
        
        .summary-card h6 {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .summary-card h2 {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }
        
        .card-gradient-success {
            background: linear-gradient(135deg, #11998e, #38ef7d);
        }
        
        .card-gradient-danger {
            background: linear-gradient(135deg, #eb3349, #f45c43);
        }
        
        .card-gradient-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .table-custom thead th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table-custom tbody tr:hover {
            background: #f8f9fa;
        }
        
        .total-row {
            background: #e9ecef;
            font-weight: bold;
            font-size: 1.05rem;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
        }
        
        @media print {
            .navbar-custom, .filter-card, .no-print {
                display: none !important;
            }
            
            .section-card {
                box-shadow: none;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom no-print">
        <div class="container-fluid">
            <a class="navbar-brand" href="laporan.php">
                <i class="bi bi-file-bar-graph-fill"></i> Laporan Keuangan
            </a>
            <div>
                <button onclick="window.print()" class="btn btn-success me-2">
                    <i class="bi bi-printer"></i> Print
                </button>
                <a href="dashboard.php" class="btn btn-back">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container-fluid py-4">
        <!-- Filter -->
        <div class="filter-card no-print mb-4">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-auto">
                    <label class="form-label fw-bold">
                        <i class="bi bi-calendar3"></i> Tahun:
                    </label>
                    <select name="tahun" class="form-select">
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-auto">
                    <label class="form-label fw-bold">
                        <i class="bi bi-calendar-month"></i> Bulan (Pengeluaran):
                    </label>
                    <select name="bulan" class="form-select">
                        <?php foreach ($nama_bulan as $b => $nama_bln): ?>
                            <option value="<?php echo $b; ?>" <?php echo $bulan == $b ? 'selected' : ''; ?>>
                                <?php echo $nama_bln; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Tampilkan Laporan
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Ringkasan Bulan Ini -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="summary-card card-gradient-success">
                    <h6>Total Pemasukan</h6>
                    <h2><?php echo rupiah($ringkasan['total_pemasukan']); ?></h2>
                    <small><?php echo $nama_bulan[$bulan]; ?> <?php echo $tahun; ?></small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card card-gradient-danger">
                    <h6>Total Pengeluaran</h6>
                    <h2><?php echo rupiah($ringkasan['total_pengeluaran']); ?></h2>
                    <small><?php echo $nama_bulan[$bulan]; ?> <?php echo $tahun; ?></small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card card-gradient-primary">
                    <h6>Saldo Bulan Ini</h6>
                    <h2><?php echo rupiah($saldo_bulan); ?></h2>
                    <small><?php echo $nama_bulan[$bulan]; ?> <?php echo $tahun; ?></small>
                </div>
            </div>
        </div>
        
        <!-- Grafik Pemasukan per Bulan -->
        <div class="section-card">
            <h2><i class="bi bi-graph-up"></i> Grafik Pemasukan Bulanan (Tahun <?php echo $tahun; ?>)</h2>
            <div class="chart-container">
                <canvas id="chartPemasukan"></canvas>
            </div>
        </div>
        
        <!-- Tabel Pengeluaran per Kategori -->
        <div class="section-card">
            <h2><i class="bi bi-pie-chart"></i> Pengeluaran per Kategori (<?php echo $nama_bulan[$bulan]; ?> <?php echo $tahun; ?>)</h2>
            
            <?php if (count($pengeluaran_kategori) > 0): ?>
            <div class="table-responsive">
                <table class="table table-custom table-hover">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="50%">Kategori</th>
                            <th width="30%" class="text-end">Jumlah</th>
                            <th width="15%" class="text-end">Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($pengeluaran_kategori as $kat): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                <span style="color: <?php echo $kat['warna']; ?>; font-weight: 600;">
                                    <?php echo $kat['ikon'] . ' ' . $kat['nama']; ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <strong><?php echo rupiah($kat['total']); ?></strong>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-primary">
                                    <?php echo number_format(($kat['total'] / $total_pengeluaran) * 100, 1); ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="2">TOTAL PENGELUARAN</td>
                            <td class="text-end"><?php echo rupiah($total_pengeluaran); ?></td>
                            <td class="text-end">100%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #dee2e6;"></i>
                <p class="text-muted mt-3">Belum ada pengeluaran di bulan ini</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Data untuk grafik
        const ctx = document.getElementById('chartPemasukan').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Pemasukan (Rp)',
                    data: <?php echo json_encode(array_values($pemasukan_bulanan)); ?>,
                    backgroundColor: 'rgba(102, 126, 234, 0.7)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>