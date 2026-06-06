<?php
// index.php
// Public Landing Page & Leaderboard for TPS3R Gang Tani Pringsewu

$active_page = 'landing';
$page_title = 'TPS3R Gang Tani Pringsewu - Sistem Gamifikasi Sampah';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// 1. Fetch Leaderboard Data (Top 5 Nasabah by Poin Akumulasi)
try {
    $leaderboard_query = "
        SELECT 
            u.id, 
            u.name, 
            u.poin_akumulasi AS total_points,
            COALESCE(SUM(t.weight), 0) AS total_weight
        FROM users u
        LEFT JOIN transactions t ON u.id = t.warga_id
        WHERE u.role = 'warga'
        GROUP BY u.id
        ORDER BY total_points DESC, u.name ASC
        LIMIT 5
    ";
    $leaderboard = $pdo->query($leaderboard_query)->fetchAll();
} catch (PDOException $e) {
    $leaderboard = [];
}

// 2. Fetch Waste Categories & Pricing
try {
    $categories = $pdo->query("SELECT * FROM waste_categories ORDER BY category ASC, name ASC")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// 3. Fetch General Statistics
try {
    $total_warga = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'warga'")->fetchColumn();
    $total_weight = $pdo->query("SELECT SUM(weight) FROM transactions")->fetchColumn();
    $total_points = $pdo->query("SELECT SUM(points_earned) FROM transactions")->fetchColumn();
    
    $total_weight = $total_weight ? round($total_weight, 2) : 0;
    $total_points = $total_points ? number_format($total_points) : 0;
} catch (PDOException $e) {
    $total_warga = 0;
    $total_weight = 0;
    $total_points = 0;
}
?>

<!-- Public Navigation Bar -->
<header class="landing-navbar" style="background: var(--primary); color: #ffffff; padding: 16px 40px; box-shadow: var(--shadow-soft); display: flex; justify-content: space-between; align-items: center;">
    <div class="brand" style="margin-bottom: 0; padding-left: 0;">
        <div class="brand-logo" style="background-color: var(--primary-light); color: var(--primary);"><i class="fa-solid fa-recycle"></i></div>
        <div class="brand-name" style="color: #ffffff;">
            TPS3R Gang Tani
            <span style="color: var(--primary-light);">Pringsewu Barat</span>
        </div>
    </div>
    
    <div>
        <?php if (is_logged_in()): ?>
            <?php if (is_admin()): ?>
                <a href="admin_dashboard.php" class="btn" style="background: var(--primary-light); color: var(--primary); font-weight: 700; border-radius: var(--radius-sm);">
                    <i class="fa-solid fa-chart-pie" style="margin-right: 6px;"></i> Panel Admin
                </a>
            <?php else: ?>
                <a href="warga_dashboard.php" class="btn" style="background: var(--primary-light); color: var(--primary); font-weight: 700; border-radius: var(--radius-sm);">
                    <i class="fa-solid fa-house-user" style="margin-right: 6px;"></i> Dashboard Saya
                </a>
            <?php endif; ?>
        <?php else: ?>
            <a href="login.php" class="btn" style="background: var(--primary-light); color: var(--primary); font-weight: 700; border-radius: var(--radius-sm);">
                <i class="fa-solid fa-right-to-bracket" style="margin-right: 6px;"></i> Masuk
            </a>
        <?php endif; ?>
    </div>
</header>

<div class="landing-container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
    
    <!-- Hero Section -->
    <section class="hero-section animate-fade-in" style="margin-bottom: 60px;">
        <div style="padding-right: 20px;">
            <h1 class="hero-title" style="color: var(--primary); font-size: 42px; font-weight: 800; line-height: 1.2; margin-bottom: 20px;">
                Ubah Sampah Jadi Berkah di <span style="background: linear-gradient(135deg, var(--primary) 0%, #2b8a3e 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">TPS3R Gang Tani!</span>
            </h1>
            <p class="hero-desc" style="color: var(--text-secondary); font-size: 15px; line-height: 1.6; margin-bottom: 30px;">
                Selamat datang di portal informasi dan gamifikasi sampah Kelurahan Pringsewu Barat. Pilah sampah anorganik dan organik Anda dari rumah, kumpulkan di TPS3R Gang Tani, dapatkan saldo rupiah serta poin reward untuk ditukarkan dengan hadiah menarik!
            </p>
            <div style="display: flex; gap: 15px;">
                <?php if (!is_logged_in()): ?>
                    <a href="login.php" class="btn btn-primary" style="padding: 12px 24px; border-radius: var(--radius-sm); font-size: 14px;">
                        Mulai Setor Sampah
                    </a>
                <?php endif; ?>
                <a href="#harga" class="btn btn-secondary" style="padding: 12px 24px; border-radius: var(--radius-sm); font-size: 14px; border: 1px solid var(--border-color);">
                    Lihat Daftar Konversi
                </a>
            </div>
        </div>
        
        <!-- Impact Indicators Card -->
        <div class="glass-card" style="padding: 30px; display: grid; grid-template-columns: 1fr; gap: 20px; border-left: 5px solid var(--primary);">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--primary); margin-bottom: 5px;"><i class="fa-solid fa-earth-asia" style="margin-right: 8px;"></i>Kontribusi Lingkungan Kita</h3>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(15, 81, 50, 0.08); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fa-solid fa-users-line"></i>
                    </div>
                    <div>
                        <div style="font-size: 18px; font-weight: 700; color: var(--primary);"><?php echo $total_warga; ?></div>
                        <div style="font-size: 12px; color: var(--text-secondary);">Nasabah Berpartisipasi</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(43, 138, 62, 0.08); color: #2b8a3e; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <div>
                        <div style="font-size: 18px; font-weight: 700; color: var(--primary);"><?php echo $total_weight; ?> kg</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">Total Sampah Terpilah Masuk</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(245, 158, 11, 0.08); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fa-solid fa-award"></i>
                    </div>
                    <div>
                        <div style="font-size: 18px; font-weight: 700; color: var(--primary);"><?php echo $total_points; ?> Poin</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">Total Poin Gamifikasi Didapat</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Leaderboard and Prices Grid -->
    <div class="landing-grid-3" style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; margin-bottom: 60px;">
        
        <!-- Papan Peringkat Warga (Leaderboard) -->
        <div class="glass-card">
            <div class="section-header" style="margin-bottom: 20px;">
                <h2 class="section-title" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-trophy" style="color: #f59e0b;"></i> Top 5 Nasabah Terbaik
                </h2>
            </div>
            
            <div class="leaderboard-list">
                <?php if (empty($leaderboard)): ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 20px 0;">Belum ada data warga terdaftar.</p>
                <?php else: ?>
                    <?php 
                    $rank = 1;
                    foreach ($leaderboard as $w): 
                        $rankClass = 'rank-other';
                        if ($rank === 1) $rankClass = 'rank-1';
                        elseif ($rank === 2) $rankClass = 'rank-2';
                        elseif ($rank === 3) $rankClass = 'rank-3';
                        
                        $badge = get_badge($w['total_points']);
                    ?>
                        <div class="leaderboard-item" style="padding: 12px 14px;">
                            <div class="rank-badge <?php echo $rankClass; ?>"><?php echo $rank; ?></div>
                            <div style="flex: 1;">
                                <div class="leaderboard-name" style="font-size: 13px; color: var(--primary);"><?php echo sanitize($w['name']); ?></div>
                                <span class="badge <?php echo $badge['class']; ?>" style="font-size: 7px; padding: 1px 4px; margin-top: 2px;"><?php echo $badge['badge'] . ' ' . $badge['name']; ?></span>
                            </div>
                            <div style="text-align: right;">
                                <div class="leaderboard-pts" style="font-size: 13px;"><?php echo number_format($w['total_points']); ?> <span>pts</span></div>
                                <div style="font-size: 10px; color: var(--text-muted);"><?php echo $w['total_weight']; ?> kg</div>
                            </div>
                        </div>
                    <?php 
                        $rank++;
                    endforeach; 
                    ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Daftar Harga Sampah & Poin -->
        <div id="harga" class="glass-card">
            <div class="section-header" style="margin-bottom: 20px;">
                <h2 class="section-title" style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-list-check" style="color: var(--primary);"></i> Nilai Konversi Sampah TPS3R
                </h2>
            </div>
            
            <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 13px;">
                Setiap kilogram sampah kering terpilah bernilai saldo tabungan rupiah dan poin reward gamifikasi langsung sebagai berikut:
            </p>
            
            <div class="table-responsive">
                <table class="custom-table" style="font-size: 13px;">
                    <thead>
                        <tr>
                            <th>Jenis Sampah</th>
                            <th>Kategori</th>
                            <th>Harga Rupiah / Kg</th>
                            <th>Poin / Kg</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 20px 0;">Kategori sampah belum tersedia.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): 
                                $badgeClass = 'badge-success';
                                if ($cat['category'] === 'Anorganik') $badgeClass = 'badge-warning';
                                elseif ($cat['category'] === 'B3') $badgeClass = 'badge-danger';
                            ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--primary);"><?php echo sanitize($cat['name']); ?></td>
                                    <td><span class="badge <?php echo $badgeClass; ?>" style="font-size: 9px;"><?php echo $cat['category']; ?></span></td>
                                    <td style="font-weight: 600; color: var(--success);">
                                        <?php echo $cat['price_per_kg'] > 0 ? 'Rp' . number_format($cat['price_per_kg'], 0, ',', '.') : '-'; ?>
                                    </td>
                                    <td style="font-weight: 700; color: #f59e0b;">⭐ <?php echo $cat['point_per_kg']; ?> pts</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    
    <!-- Footer Section -->
    <footer style="text-align: center; margin-top: 60px; padding: 24px 0; border-top: 1px solid var(--border-color); color: var(--text-secondary); font-size: 12px;">
        <p>© 2026 TPS3R Gang Tani Pringsewu - Sistem Gamifikasi Pengelolaan Sampah Warga. All Rights Reserved.</p>
    </footer>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
