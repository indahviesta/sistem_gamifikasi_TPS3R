<?php
// export_excel.php
// Excel Exporter for Nasabah Data - TPS3R Gang Tani Pringsewu

require_once __DIR__ . '/includes/db.php';
require_login('admin'); // Restrict to admin only

// 1. Fetch All Nasabah Data
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'warga' ORDER BY name ASC");
    $nasabah = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data untuk diekspor: " . $e->getMessage());
}

// 2. Set Excel Headers
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Data_Nasabah_TPS3R_Gang_Tani_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// 3. Print HTML Table
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
</head>
<body>
    <h2>DATA NASABAH - TPS3R GANG TANI PRINGSEWU</h2>
    <p>Tanggal Unduh: <?php echo date('d-m-Y H:i'); ?></p>
    
    <table border="1">
        <thead>
            <tr style="background-color: #0F5132; color: #ffffff; font-weight: bold;">
                <th>No</th>
                <th>Nama Nasabah</th>
                <th>Username</th>
                <th>Alamat</th>
                <th>Nomor HP</th>
                <th>Saldo (Rupiah)</th>
                <th>Poin Aktif</th>
                <th>Poin Akumulasi</th>
                <th>Tanggal Registrasi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($nasabah as $n): 
            ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo sanitize($n['name']); ?></td>
                    <td><?php echo sanitize($n['username']); ?></td>
                    <td><?php echo sanitize($n['address'] ? $n['address'] : '-'); ?></td>
                    <td style="vnd.ms-excel.numberformat:@"><?php echo sanitize($n['phone'] ? $n['phone'] : '-'); ?></td>
                    <td><?php echo $n['saldo']; ?></td>
                    <td><?php echo $n['poin']; ?></td>
                    <td><?php echo $n['poin_akumulasi']; ?></td>
                    <td><?php echo date('d-m-Y', strtotime($n['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
