<?php
// export_pdf.php
// PDF Printable View for Nasabah Data - TPS3R Gang Tani Pringsewu

require_once __DIR__ . '/includes/db.php';
require_login('admin'); // Restrict to admin only

try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'warga' ORDER BY name ASC");
    $nasabah = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Data Nasabah - TPS3R Gang Tani Pringsewu</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            background-color: #fff;
            padding: 30px;
            font-size: 12px;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px double #0F5132;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0;
            color: #0F5132;
            font-weight: 700;
        }

        .header p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 12px;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 11px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        table th {
            background-color: #0F5132;
            color: #fff;
            padding: 10px;
            font-weight: 600;
            text-align: left;
            border: 1px solid #ddd;
        }

        table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .footer {
            margin-top: 50px;
            text-align: right;
            font-size: 11px;
            color: #777;
        }

        .signature {
            margin-top: 60px;
            display: inline-block;
            text-align: center;
            width: 200px;
        }

        .signature-title {
            margin-bottom: 60px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- Printable Header -->
    <div class="header">
        <h1>SISTEM INFORMASI GAMIFIKASI TPS3R GANG TANI PRINGSEWU</h1>
        <p>Alamat: Gang Tani, Kelurahan Pringsewu Barat, Kecamatan Pringsewu, Kabupaten Pringsewu, Lampung</p>
    </div>

    <div class="meta-info">
        <div><strong>Laporan:</strong> Data Nasabah Aktif</div>
        <div><strong>Tanggal Cetak:</strong> <?php echo date('d F Y, H:i'); ?> WIB</div>
    </div>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>Nama Nasabah</th>
                <th>Username</th>
                <th>Alamat</th>
                <th>No HP</th>
                <th>Saldo (Rupiah)</th>
                <th>Poin Aktif</th>
                <th>Poin Akumulasi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($nasabah as $n): 
            ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td style="font-weight: 600;"><?php echo sanitize($n['name']); ?></td>
                    <td><?php echo sanitize($n['username']); ?></td>
                    <td><?php echo sanitize($n['address'] ? $n['address'] : '-'); ?></td>
                    <td><?php echo sanitize($n['phone'] ? $n['phone'] : '-'); ?></td>
                    <td style="font-weight: 600; color: #198754;">Rp<?php echo number_format($n['saldo'], 0, ',', '.'); ?></td>
                    <td style="font-weight: 600;">⭐ <?php echo number_format($n['poin']); ?></td>
                    <td><?php echo number_format($n['poin_akumulasi']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Signatures -->
    <div class="footer">
        <div class="signature">
            <div class="signature-title">Mengetahui,<br>Pengelola TPS3R Gang Tani</div>
            <div class="signature-name"><?php echo sanitize($_SESSION['name']); ?></div>
            <div>Koordinator Lapangan</div>
        </div>
    </div>

</body>
</html>
