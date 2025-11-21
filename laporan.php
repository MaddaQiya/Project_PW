<?php
require_once 'config.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

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
    <title>Laporan - CampussWallet</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .filter-form select,
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
        .btn-print {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        tr:hover {
            background: #f8f9fa;
        }
        .total-row {
            background: #e9ecef;
            font-weight: bold;
        }
        @media print {
            .navbar, .filter-form, .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📊 Laporan Keuangan</h1>
        <div>
            <a href="dashboard.php">← Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Filter -->
        <div class="section">
            <form method="GET" action="" class="filter-form">
                <label>Tahun:</label>
                <select name="tahun">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                
                <label>Bulan (untuk pengeluaran):</label>
                <select name="bulan">
                    <?php foreach ($nama_bulan as $b => $nama): ?>
                        <option value="<?php echo $b; ?>" <?php echo $bulan == $b ? 'selected' : ''; ?>>
                            <?php echo $nama; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit">Filter</button>
                <button type="button" onclick="window.print()" class="btn-print">🖨️ Print</button>
            </form>
        </div>
        
        <!-- Grafik Pemasukan per Bulan -->
        <div class="section">
            <h2>Pemasukan per Bulan (Tahun <?php echo $tahun; ?>)</h2>
            <canvas id="chartPemasukan" style="max-height: 300px;"></canvas>
        </div>
        
        <!-- Tabel Pengeluaran per Kategori -->
        <div class="section">
            <h2>Pengeluaran per Kategori (<?php echo $nama_bulan[$bulan]; ?> <?php echo $tahun; ?>)</h2>
            
            <?php if (count($pengeluaran_kategori) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th style="text-align: right;">Jumlah</th>
                        <th style="text-align: right;">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pengeluaran_kategori as $kat): ?>
                    <tr>
                        <td>
                            <span style="color: <?php echo $kat['warna']; ?>">
                                <?php echo $kat['ikon'] . ' ' . $kat['nama']; ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <strong><?php echo rupiah($kat['total']); ?></strong>
                        </td>
                        <td style="text-align: right;">
                            <?php echo number_format(($kat['total'] / $total_pengeluaran) * 100, 1); ?>%
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td>TOTAL</td>
                        <td style="text-align: right;"><?php echo rupiah($total_pengeluaran); ?></td>
                        <td style="text-align: right;">100%</td>
                    </tr>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #999;">
                Belum ada pengeluaran di bulan ini
            </p>
            <?php endif; ?>
        </div>
        
        <!-- Ringkasan Saldo -->
        <?php
        $sql_ringkasan = "SELECT 
            SUM(CASE WHEN jenis_transaksi = 'pemasukan' THEN jumlah ELSE 0 END) as total_pemasukan,
            SUM(CASE WHEN jenis_transaksi = 'pengeluaran' THEN jumlah ELSE 0 END) as total_pengeluaran
            FROM transaksi 
            WHERE id_pengguna = $user_id 
            AND YEAR(tanggal_transaksi) = $tahun 
            AND MONTH(tanggal_transaksi) = $bulan";
        $ringkasan = mysqli_fetch_assoc(mysqli_query($conn, $sql_ringkasan));
        $saldo_bulan = $ringkasan['total_pemasukan'] - $ringkasan['total_pengeluaran'];
        ?>
        <div class="section">
            <h2>Ringkasan Bulan <?php echo $nama_bulan[$bulan]; ?> <?php echo $tahun; ?></h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div style="padding: 20px; background: linear-gradient(135deg, #11998e, #38ef7d); color: white; border-radius: 10px;">
                    <div style="font-size: 14px; margin-bottom: 5px;">Total Pemasukan</div>
                    <div style="font-size: 24px; font-weight: bold;"><?php echo rupiah($ringkasan['total_pemasukan']); ?></div>
                </div>
                <div style="padding: 20px; background: linear-gradient(135deg, #eb3349, #f45c43); color: white; border-radius: 10px;">
                    <div style="font-size: 14px; margin-bottom: 5px;">Total Pengeluaran</div>
                    <div style="font-size: 24px; font-weight: bold;"><?php echo rupiah($ringkasan['total_pengeluaran']); ?></div>
                </div>
                <div style="padding: 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 10px;">
                    <div style="font-size: 14px; margin-bottom: 5px;">Saldo Bulan Ini</div>
                    <div style="font-size: 24px; font-weight: bold;"><?php echo rupiah($saldo_bulan); ?></div>
                </div>
            </div>
        </div>
    </div>

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
                    backgroundColor: 'rgba(102, 126, 234, 0.6)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>