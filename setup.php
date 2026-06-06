<?php
// setup.php
// Script to set up database and insert seed data for TPS3R Gang Tani Pringsewu

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'gamifikasi_sampah';

$message = '';
$status = 'info';

try {
    // 1. Connect to MySQL Server (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // 3. Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 4. Create Tables
    
    // Users table (Admin & Warga/Nasabah)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) UNIQUE NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('admin', 'warga') NOT NULL DEFAULT 'warga',
        `nik` VARCHAR(20) UNIQUE NULL,
        `name` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(15) NULL,
        `address` TEXT NULL,
        `saldo` INT NOT NULL DEFAULT 0,
        `poin` INT NOT NULL DEFAULT 0,
        `poin_akumulasi` INT NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // Waste categories & pricing
    $pdo->exec("CREATE TABLE IF NOT EXISTS `waste_categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `price_per_kg` INT NOT NULL DEFAULT 0,
        `point_per_kg` INT NOT NULL DEFAULT 0,
        `category` ENUM('Organik', 'Anorganik', 'B3') NOT NULL DEFAULT 'Anorganik',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // Transactions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `transactions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `warga_id` INT NOT NULL,
        `admin_id` INT NOT NULL,
        `category_id` INT NOT NULL,
        `weight` DECIMAL(10,2) NOT NULL,
        `points_earned` INT NOT NULL DEFAULT 0,
        `cash_earned` INT NOT NULL DEFAULT 0,
        `notes` TEXT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`warga_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`category_id`) REFERENCES `waste_categories`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // Rewards table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `rewards` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT NULL,
        `points_required` INT NOT NULL DEFAULT 0,
        `stock` INT NOT NULL DEFAULT 0,
        `status` ENUM('Tersedia', 'Habis') NOT NULL DEFAULT 'Tersedia',
        `image_path` VARCHAR(255) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // Reward Claims table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `reward_claims` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `warga_id` INT NOT NULL,
        `reward_id` INT NOT NULL,
        `points_spent` INT NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`warga_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`reward_id`) REFERENCES `rewards`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // Waste Education table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `education` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(150) NOT NULL,
        `description` TEXT NULL,
        `waste_type` VARCHAR(50) NOT NULL,
        `point_category` VARCHAR(50) NOT NULL,
        `bin_color` VARCHAR(30) NOT NULL,
        `image_path` VARCHAR(255) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 5. Seed Initial Data
    
    // Seed Admin (if not exists)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `users` (`username`, `password`, `role`, `name`) VALUES (:username, :password, :role, :name)");
        $stmt->execute([
            'username' => 'admin',
            'password' => $adminPassword,
            'role' => 'admin',
            'name' => 'Admin TPS3R Gang Tani'
        ]);
    }

    // Seed Waste Categories
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `waste_categories`");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $categories = [
            ['Botol Plastik PET', 3000, 50, 'Anorganik'],
            ['Kardus & Kertas Bekas', 1500, 20, 'Anorganik'],
            ['Kaleng & Besi Tua', 6000, 100, 'Anorganik'],
            ['Botol Kaca Bekas', 1000, 30, 'Anorganik'],
            ['Sisa Makanan Organik', 500, 10, 'Organik'],
            ['Baterai & Limbah Elektronik B3', 0, 150, 'B3']
        ];
        $stmt = $pdo->prepare("INSERT INTO `waste_categories` (`name`, `price_per_kg`, `point_per_kg`, `category`) VALUES (?, ?, ?, ?)");
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }
    }

    // Seed Rewards
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `rewards`");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $rewards = [
            ['Pulsa Rp10.000', 'Pengisian pulsa seluler sebesar Rp10.000 untuk all operator.', 200, 50, 'Tersedia'],
            ['Voucher Belanja Rp50.000', 'Voucher belanja minimarket lokal Gang Tani Pringsewu.', 900, 10, 'Tersedia'],
            ['Tumbler Eco-Friendly', 'Botol minum ramah lingkungan untuk mengurangi sampah botol plastik sekali pakai.', 500, 15, 'Tersedia'],
            ['Sembako Minyak & Beras', 'Paket sembako berisi 1 Liter minyak goreng dan 2 kg beras.', 800, 8, 'Tersedia'],
            ['Kaos TPS3R Gang Tani', 'Kaos merchandise eksklusif relawan TPS3R Gang Tani Pringsewu.', 600, 0, 'Habis']
        ];
        $stmt = $pdo->prepare("INSERT INTO `rewards` (`name`, `description`, `points_required`, `stock`, `status`) VALUES (?, ?, ?, ?, ?)");
        foreach ($rewards as $r) {
            $stmt->execute($r);
        }
    }

    // Seed Education Materials
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `education`");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $education = [
            ['Pemilahan Sampah Organik Rumah Tangga', 'Sampah organik seperti sisa sayuran, dedaunan, dan buah-buahan dapat diolah menjadi kompos organik yang menyuburkan tanah.', 'Organik', '10 Poin/Kg', 'Hijau'],
            ['Mengelola Sampah Plastik & Botol PET', 'Botol plastik sekali pakai (PET) bernilai ekonomi tinggi. Cuci bersih dan pilah agar mudah didaur ulang kembali.', 'Plastik', '50 Poin/Kg', 'Kuning'],
            ['Kertas & Karton yang Dapat Didaur Ulang', 'Kardus bekas, koran, dan kertas HVS dapat diproses kembali menjadi bubur kertas. Pastikan kertas kering sebelum disetor.', 'Kertas', '20 Poin/Kg', 'Biru'],
            ['Bahaya Sampah Kaca & Penanganannya', 'Botol kaca utuh atau pecah memiliki wadah daur ulang tersendiri. Memilah kaca dapat menghindarkan bahaya bagi petugas kebersihan.', 'Kaca', '30 Poin/Kg', 'Merah'],
            ['Pengelolaan Logam, Besi & Kaleng Bekas', 'Besi tua, kaleng susu, dan aluminium bekas memiliki harga jual yang tinggi dan dapat dilebur berulang kali tanpa mengurangi kualitas.', 'Logam', '100 Poin/Kg', 'Abu-abu'],
            ['Pembuangan Sampah B3 Rumah Tangga', 'Limbah bahan berbahaya seperti baterai bekas, lampu neon, dan kemasan racun serangga tidak boleh dicampur dan harus ditangani khusus.', 'B3', '150 Poin/Kg', 'Merah']
        ];
        $stmt = $pdo->prepare("INSERT INTO `education` (`title`, `description`, `waste_type`, `point_category`, `bin_color`) VALUES (?, ?, ?, ?, ?)");
        foreach ($education as $ed) {
            $stmt->execute($ed);
        }
    }

    // Seed Warga / Nasabah with different registration dates
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `role` = 'warga'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $warga_users = [
            ['3201010101010001', 'Ahmad Fauzi', '081234567890', 'Gang Tani RT 01, Pringsewu Barat', '2026-03-01 09:00:00'],
            ['3201010101010002', 'Budi Santoso', '082198765432', 'Gang Tani RT 02, Pringsewu Barat', '2026-03-15 10:30:00'],
            ['3201010101010003', 'Siti Aminah', '085711223344', 'Jl. Ahmad Yani No. 12, Pringsewu', '2026-04-05 14:15:00'],
            ['3201010101010004', 'Dewi Lestari', '089988776655', 'Pringsewu Timur, Pringsewu', '2026-04-20 08:45:00'],
            ['3201010101010005', 'Eko Prasetyo', '081399887766', 'Gang Tani RT 01, Pringsewu Barat', '2026-05-10 11:00:00']
        ];
        
        $w_stmt = $pdo->prepare("INSERT INTO `users` (`nik`, `password`, `role`, `name`, `phone`, `address`, `created_at`) VALUES (?, ?, 'warga', ?, ?, ?, ?)");
        foreach ($warga_users as $w) {
            $hashedPass = password_hash($w[0], PASSWORD_DEFAULT); // default password is NIK
            $w_stmt->execute([$w[0], $hashedPass, $w[1], $w[2], $w[3], $w[4]]);
        }

        // Get admin ID
        $admin_id = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn();
        
        // Get category mapping
        $cats = $pdo->query("SELECT id, name, price_per_kg, point_per_kg FROM waste_categories")->fetchAll(PDO::FETCH_ASSOC);
        $cat_map = [];
        foreach ($cats as $c) {
            $cat_map[$c['name']] = $c;
        }

        // Get warga ID mapping
        $wargas = $pdo->query("SELECT id, name FROM users WHERE role = 'warga'")->fetchAll(PDO::FETCH_ASSOC);
        $warga_map = [];
        foreach ($wargas as $w) {
            $warga_map[$w['name']] = $w['id'];
        }

        // Transactions Seed (Spread over March, April, May, June 2026 for trend rendering)
        // [WargaName, CategoryName, Weight, CreatedAt]
        $transactions_seed = [
            // Maret 2026
            ['Ahmad Fauzi', 'Botol Plastik PET', 15.5, '2026-03-05 10:00:00'],
            ['Ahmad Fauzi', 'Kardus & Kertas Bekas', 25.0, '2026-03-12 11:30:00'],
            ['Budi Santoso', 'Kaleng & Besi Tua', 8.2, '2026-03-20 09:15:00'],
            ['Budi Santoso', 'Botol Kaca Bekas', 12.0, '2026-03-28 14:00:00'],
            
            // April 2026
            ['Ahmad Fauzi', 'Kaleng & Besi Tua', 12.0, '2026-04-02 09:45:00'],
            ['Budi Santoso', 'Botol Plastik PET', 18.0, '2026-04-10 10:00:00'],
            ['Siti Aminah', 'Kardus & Kertas Bekas', 35.0, '2026-04-15 15:30:00'],
            ['Siti Aminah', 'Sisa Makanan Organik', 20.0, '2026-04-22 08:30:00'],
            ['Dewi Lestari', 'Botol Plastik PET', 10.5, '2026-04-28 11:00:00'],
            
            // Mei 2026
            ['Ahmad Fauzi', 'Botol Plastik PET', 22.0, '2026-05-04 10:20:00'],
            ['Budi Santoso', 'Sisa Makanan Organik', 15.0, '2026-05-11 13:45:00'],
            ['Siti Aminah', 'Kaleng & Besi Tua', 15.0, '2026-05-15 09:00:00'],
            ['Dewi Lestari', 'Kardus & Kertas Bekas', 18.2, '2026-05-18 10:15:00'],
            ['Eko Prasetyo', 'Botol Plastik PET', 25.0, '2026-05-24 16:00:00'],
            ['Eko Prasetyo', 'Baterai & Limbah Elektronik B3', 3.0, '2026-05-29 14:30:00'],

            // Juni 2026 (Bulan berjalan)
            ['Ahmad Fauzi', 'Botol Plastik PET', 12.5, '2026-06-01 10:00:00'],
            ['Budi Santoso', 'Kardus & Kertas Bekas', 30.0, '2026-06-02 11:00:00'],
            ['Siti Aminah', 'Botol Kaca Bekas', 22.5, '2026-06-03 09:30:00'],
            ['Eko Prasetyo', 'Kaleng & Besi Tua', 10.0, '2026-06-05 15:00:00']
        ];

        $tx_stmt = $pdo->prepare("
            INSERT INTO `transactions` (`warga_id`, `admin_id`, `category_id`, `weight`, `points_earned`, `cash_earned`, `notes`, `created_at`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($transactions_seed as $tx) {
            $w_id = $warga_map[$tx[0]];
            $c_info = $cat_map[$tx[1]];
            $weight = $tx[2];
            $pts = round($weight * $c_info['point_per_kg']);
            $cash = round($weight * $c_info['price_per_kg']);
            $note = 'Setoran disetujui petugas TPS3R.';
            $date = $tx[3];
            
            $tx_stmt->execute([$w_id, $admin_id, $c_info['id'], $weight, $pts, $cash, $note, $date]);
        }

        // Sync points and balance of seeded users
        $sync_stmt = $pdo->prepare("
            UPDATE users u
            SET 
                u.poin_akumulasi = (SELECT COALESCE(SUM(points_earned), 0) FROM transactions WHERE warga_id = u.id),
                u.poin = (SELECT COALESCE(SUM(points_earned), 0) FROM transactions WHERE warga_id = u.id),
                u.saldo = (SELECT COALESCE(SUM(cash_earned), 0) FROM transactions WHERE warga_id = u.id)
            WHERE u.role = 'warga'
        ");
        $sync_stmt->execute();
        
        // Let's make some claim record to demo "Reward Ditukar"
        $claim_warga_id = $warga_map['Ahmad Fauzi'];
        $reward_id = $pdo->query("SELECT id FROM rewards WHERE name = 'Pulsa Rp10.000' LIMIT 1")->fetchColumn();
        
        $claim_stmt = $pdo->prepare("INSERT INTO `reward_claims` (`warga_id`, `reward_id`, `points_spent`, `created_at`) VALUES (?, ?, ?, '2026-05-12 11:00:00')");
        claim_action($pdo, $claim_warga_id, $reward_id, 200, $claim_stmt);
    }

    $message = "Database TPS3R Gang Tani Pringsewu berhasil diinisialisasi! Data contoh nasabah, kategori sampah, reward, materi edukasi, dan transaksi bulanan telah dimasukkan.";
    $status = "success";
} catch (PDOException $e) {
    $message = "Error: " . $e->getMessage();
    $status = "danger";
}

// Function helper to run claim and deduct points
function claim_action($pdo, $w_id, $r_id, $points, $stmt) {
    $stmt->execute([$w_id, $r_id, $points]);
    // Deduct points from current point balance (poin), but keep poin_akumulasi the same!
    $pdo->prepare("UPDATE users SET poin = poin - ? WHERE id = ?")->execute([$points, $w_id]);
    // Deduct stock from rewards
    $pdo->prepare("UPDATE rewards SET stock = stock - 1 WHERE id = ?")->execute([$r_id]);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inisialisasi Database - TPS3R Gang Tani Pringsewu</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f5f5f5;
            --primary: #0F5132;
            --accent: #A8E6A1;
            --surface: #ffffff;
            --text-main: #212529;
            --text-muted: #6c757d;
            --success: #198754;
            --danger: #dc3545;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(15, 81, 50, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(168, 230, 161, 0.2) 0%, transparent 40%);
        }

        .container {
            background: var(--surface);
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 24px;
            padding: 40px;
            max-width: 550px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .icon {
            font-size: 54px;
            margin-bottom: 20px;
            color: var(--primary);
        }

        h1 {
            font-size: 22px;
            margin-bottom: 12px;
            font-weight: 700;
            color: var(--primary);
        }

        p {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            text-align: left;
            line-height: 1.5;
        }

        .alert-success {
            background: rgba(25, 135, 84, 0.1);
            border: 1px solid rgba(25, 135, 84, 0.2);
            color: var(--success);
        }

        .alert-danger {
            background: rgba(220, 53, 85, 0.1);
            border: 1px solid rgba(220, 53, 85, 0.2);
            color: var(--danger);
        }

        .btn {
            display: inline-block;
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(15, 81, 50, 0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15, 81, 50, 0.35);
            background: #0a3a24;
        }

        .credentials {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            text-align: left;
            font-size: 13px;
        }

        .credentials h3 {
            font-size: 14px;
            margin-bottom: 8px;
            color: var(--primary);
        }

        .credentials ul {
            list-style: none;
            color: var(--text-muted);
        }

        .credentials li {
            margin-bottom: 4px;
        }

        .credentials code {
            background: rgba(0, 0, 0, 0.05);
            padding: 2px 6px;
            border-radius: 4px;
            color: var(--primary);
            font-family: monospace;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">♻️</div>
        <h1>TPS3R Gang Tani Pringsewu</h1>
        <p>Inisialisasi database gamifikasi pengelolaan sampah dan reward nasabah.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $status; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($status === 'success'): ?>
            <a href="index.php" class="btn">Mulai Aplikasi</a>
            
            <div class="credentials">
                <h3>Akun Default untuk Uji Coba:</h3>
                <ul>
                    <li><strong>Admin Username:</strong> <code>admin</code></li>
                    <li><strong>Admin Password:</strong> <code>admin123</code></li>
                    <li><strong>Warga Login:</strong> Gunakan NIK (contoh: <code>3201010101010001</code>)</li>
                    <li><strong>Warga Password:</strong> Sama dengan NIK warga</li>
                </ul>
            </div>
        <?php else: ?>
            <a href="setup.php" class="btn" style="background-color: var(--danger); box-shadow: 0 4px 12px rgba(220, 53, 85, 0.2);">Coba Lagi</a>
        <?php endif; ?>
    </div>
</body>
</html>
