<?php
// admin_leaderboard.php
// Halaman 6 - Leaderboard Nasabah (Gamifikasi + Filter)

require_once __DIR__ . '/includes/db.php';
require_login('admin');

$active_page = 'admin_leaderboard';
$page_title = 'Leaderboard Nasabah - TPS3R Gang Tani';

$filter = sanitize($_GET['filter'] ?? 'tahunan'); // Default filter is tahunan

// Define date filter clause based on selection
$date_clause = "";
if ($filter === 'mingguan') {
    // Current week (last 7 days)
    $date_clause = "AND t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($filter === 'bulanan') {
    // Current month (last 30 days)
    $date_clause = "AND t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} else {
    // Current year (last 365 days) / All-time equivalent
    $date_clause = "AND t.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
}

// 1. Fetch Leaderboard Data
try {
    $leaderboard_query = "
        SELECT 
            u.id, 
            u.name, 
            u.username,
            COALESCE(COUNT(t.id), 0) AS total_transaksi,
            COALESCE(SUM(t.points_earned), 0) AS period_points,
            u.poin_akumulasi -- Overall points for Badge calculation
        FROM users u
        LEFT JOIN transactions t ON u.id = t.warga_id $date_clause
        WHERE u.role = 'warga'
        GROUP BY u.id
        ORDER BY period_points DESC, u.name ASC
    ";
    
    $leaderboard = $pdo->query($leaderboard_query)->fetchAll();
} catch (PDOException $e) {
    $leaderboard = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title animate-fade-in">
    <h1>Leaderboard Nasabah</h1>
    <p>Papan skor keaktifan nasabah mengumpulkan sampah terpilah di TPS3R Gang Tani.</p>
</div>

<!-- Leaderboard Container -->
<div class="glass-card animate-fade-in" style="margin-bottom: 24px;">
    
    <!-- Filter Header -->
    <div class="section-header" style="flex-wrap: wrap; gap: 15px;">
        <h2 class="section-title"><i class="fa-solid fa-ranking-star" style="margin-right: 8px; color: #f59e0b;"></i>Peringkat Keaktifan Nasabah</h2>
        
        <!-- Weekly/Monthly/Yearly Filters -->
        <div class="filter-tabs">
            <a href="admin_leaderboard.php?filter=mingguan" class="filter-btn <?php echo $filter === 'mingguan' ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar-week" style="margin-right: 4px;"></i> Mingguan
            </a>
            <a href="admin_leaderboard.php?filter=bulanan" class="filter-btn <?php echo $filter === 'bulanan' ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar-days" style="margin-right: 4px;"></i> Bulanan
            </a>
            <a href="admin_leaderboard.php?filter=tahunan" class="filter-btn <?php echo $filter === 'tahunan' ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar" style="margin-right: 4px;"></i> Tahunan
            </a>
        </div>
    </div>

    <!-- Gamified Table of rankings -->
    <div class="table-responsive" style="margin-top: 20px;">
        <table class="custom-table">
            <thead>
                <tr style="background-color: var(--bg-main);">
                    <th style="width: 80px; text-align: center;">Ranking</th>
                    <th style="width: 80px;">Foto</th>
                    <th>Nama Nasabah</th>
                    <th style="text-align: center;">Total Transaksi</th>
                    <th style="text-align: right;">Poin Periode</th>
                    <th>Badge Kompetensi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaderboard)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            Tidak ada data transaksi penyetoran sampah untuk periode ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $rank = 1;
                    foreach ($leaderboard as $w): 
                        // Badge dynamically decided by lifetime points
                        $badge = get_badge($w['poin_akumulasi']);
                        
                        $rankClass = 'rank-other';
                        $medal = '';
                        if ($rank === 1) {
                            $rankClass = 'rank-1';
                            $medal = '🏆 ';
                        } elseif ($rank === 2) {
                            $rankClass = 'rank-2';
                            $medal = '🥈 ';
                        } elseif ($rank === 3) {
                            $rankClass = 'rank-3';
                            $medal = '🥉 ';
                        }
                    ?>
                        <tr style="<?php echo $rank <= 3 ? 'background-color: rgba(168, 230, 161, 0.03); font-weight: 500;' : ''; ?>">
                            <td style="text-align: center;">
                                <div class="rank-badge <?php echo $rankClass; ?>" style="margin: 0 auto;">
                                    <?php echo $rank; ?>
                                </div>
                            </td>
                            <td>
                                <div class="user-avatar" style="width: 42px; height: 42px; background: var(--primary); color: #ffffff; font-weight: 600; font-size: 16px; margin: 0;">
                                    <?php echo strtoupper(substr($w['name'], 0, 1)); ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 14px; color: var(--primary); font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                    <?php echo $medal . sanitize($w['name']); ?>
                                </div>
                                <div style="font-size: 11px; color: var(--text-secondary);">username: <?php echo sanitize($w['username']); ?></div>
                            </td>
                            <td style="text-align: center; font-weight: 600;">
                                <i class="fa-solid fa-arrows-spin" style="color: var(--primary-light); margin-right: 6px;"></i><?php echo $w['total_transaksi']; ?> Kali Setor
                            </td>
                            <td style="text-align: right;">
                                <span style="font-size: 16px; font-weight: 800; color: #f59e0b;">⭐ <?php echo number_format($w['period_points']); ?></span>
                                <div style="font-size: 9px; color: var(--text-muted);">periode ini</div>
                            </td>
                            <td>
                                <span class="badge <?php echo $badge['class']; ?>" style="padding: 6px 14px; font-size: 10px;">
                                    <?php echo $badge['badge'] . ' ' . $badge['name']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php 
                        $rank++;
                    endforeach; 
                    ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Badge Information Legends Card (Clean Gamification UX) -->
<div class="grid-3 animate-fade-in" style="animation-delay: 0.15s;">
    <!-- Legend 1: Eco Champion -->
    <div class="glass-card" style="border-top: 4px solid #d97706; display: flex; gap: 15px; align-items: start; padding: 16px;">
        <span style="font-size: 32px;">🥇</span>
        <div>
            <h4 style="font-weight: 700; color: #d97706;">Eco Champion</h4>
            <p style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Tingkat keaktifan tertinggi bagi nasabah super aktif yang telah menabung total di atas <strong>1.000 Poin</strong>.</p>
        </div>
    </div>

    <!-- Legend 2: Green Hero -->
    <div class="glass-card" style="border-top: 4px solid #15803d; display: flex; gap: 15px; align-items: start; padding: 16px;">
        <span style="font-size: 32px;">🥈</span>
        <div>
            <h4 style="font-weight: 700; color: #15803d;">Green Hero</h4>
            <p style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Pahlawan lingkungan bagi nasabah setia dengan total tabungan poin berkisar antara <strong>500 s.d. 999 Poin</strong>.</p>
        </div>
    </div>

    <!-- Legend 3: Waste Warrior -->
    <div class="glass-card" style="border-top: 4px solid #1d4ed8; display: flex; gap: 15px; align-items: start; padding: 16px;">
        <span style="font-size: 32px;">🥉</span>
        <div>
            <h4 style="font-weight: 700; color: #1d4ed8;">Waste Warrior</h4>
            <p style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Ksatria pengolah sampah bagi nasabah aktif dengan total perolehan poin berkisar antara <strong>200 s.d. 499 Poin</strong>.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
