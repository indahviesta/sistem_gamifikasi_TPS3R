<?php
// admin_dashboard.php
// Halaman 1 - Dashboard Admin TPS3R Gang Tani Pringsewu

require_once __DIR__ . '/includes/db.php';
require_login('admin');

$active_page = 'admin_dashboard';
$page_title = 'Dashboard Admin - TPS3R Gang Tani Pringsewu';

// 1. Fetch Stats Summary Data
try {
    $total_nasabah = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'warga'")->fetchColumn();
    $total_transaksi = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
    $total_poin = $pdo->query("SELECT SUM(points_earned) FROM transactions")->fetchColumn();
    $reward_ditukar = $pdo->query("SELECT COUNT(*) FROM reward_claims")->fetchColumn();

    $total_poin = $total_poin ? number_format($total_poin) : 0;
} catch (PDOException $e) {
    $total_nasabah = $total_transaksi = $total_poin = $reward_ditukar = 0;
}

// 2. Fetch Tren Volume Sampah Bulanan (Bar Chart)
try {
    $bar_stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%M %Y') AS month_name, 
            SUM(weight) AS total_weight 
        FROM transactions 
        GROUP BY YEAR(created_at), MONTH(created_at) 
        ORDER BY created_at ASC 
        LIMIT 6
    ");
    $bar_data = $bar_stmt->fetchAll();
    
    $bar_labels = [];
    $bar_values = [];
    foreach ($bar_data as $row) {
        $bar_labels[] = $row['month_name'];
        $bar_values[] = (float)$row['total_weight'];
    }
} catch (PDOException $e) {
    $bar_labels = [];
    $bar_values = [];
}

// 3. Fetch Pertumbuhan Nasabah (Line Chart)
try {
    $line_stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%M %Y') AS month_name, 
            COUNT(*) AS new_users 
        FROM users 
        WHERE role = 'warga' 
        GROUP BY YEAR(created_at), MONTH(created_at) 
        ORDER BY created_at ASC
    ");
    $line_data = $line_stmt->fetchAll();
    
    $line_labels = [];
    $line_values = [];
    $running_total = 0;
    foreach ($line_data as $row) {
        $running_total += (int)$row['new_users'];
        $line_labels[] = $row['month_name'];
        $line_values[] = $running_total;
    }
} catch (PDOException $e) {
    $line_labels = [];
    $line_values = [];
}

// 4. Fetch Mini Leaderboard (Top 5 Nasabah Terbaik)
try {
    $leaderboard_stmt = $pdo->query("
        SELECT id, name, poin_akumulasi 
        FROM users 
        WHERE role = 'warga' 
        ORDER BY poin_akumulasi DESC, name ASC 
        LIMIT 5
    ");
    $top_nasabah = $leaderboard_stmt->fetchAll();
} catch (PDOException $e) {
    $top_nasabah = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title animate-fade-in">
    <h1>Dashboard Ringkasan</h1>
    <p>Selamat datang di Sistem Informasi Gamifikasi TPS3R Gang Tani Pringsewu.</p>
</div>

<!-- Stats Card Row -->
<div class="stats-grid animate-fade-in">
    <!-- Card 1: Total Nasabah -->
    <div class="glass-card stat-card" style="border-left-color: var(--primary);">
        <div class="stat-header">
            <span class="stat-title">Total Nasabah</span>
            <div class="stat-icon" style="background: rgba(15, 81, 50, 0.1); color: var(--primary);">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo $total_nasabah; ?></div>
        <div class="stat-desc">Nasabah terdaftar aktif</div>
    </div>

    <!-- Card 2: Total Transaksi -->
    <div class="glass-card stat-card" style="border-left-color: #2b8a3e;">
        <div class="stat-header">
            <span class="stat-title">Total Transaksi</span>
            <div class="stat-icon" style="background: rgba(43, 138, 62, 0.1); color: #2b8a3e;">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo $total_transaksi; ?></div>
        <div class="stat-desc">Setoran sampah diproses</div>
    </div>

    <!-- Card 3: Total Poin -->
    <div class="glass-card stat-card" style="border-left-color: #f59e0b;">
        <div class="stat-header">
            <span class="stat-title">Total Poin</span>
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                <i class="fa-solid fa-star"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo $total_poin; ?></div>
        <div class="stat-desc">Poin beredar dalam ekosistem</div>
    </div>

    <!-- Card 4: Reward Ditukar -->
    <div class="glass-card stat-card" style="border-left-color: #0dcaf0;">
        <div class="stat-header">
            <span class="stat-title">Reward Ditukar</span>
            <div class="stat-icon" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                <i class="fa-solid fa-gift"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo $reward_ditukar; ?></div>
        <div class="stat-desc">Klaim hadiah ditukarkan</div>
    </div>
</div>

<!-- Dashboard Charts & Leaderboard Section -->
<div class="dashboard-sections animate-fade-in" style="animation-delay: 0.1s;">
    
    <!-- Charts Panel (Left Columns) -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Bar Chart: Tren Volume Sampah Bulanan -->
        <div class="glass-card">
            <div class="section-header">
                <h2 class="section-title"><i class="fa-solid fa-dumpster-fire" style="margin-right: 8px;"></i>Tren Volume Sampah Bulanan (kg)</h2>
            </div>
            <div style="height: 250px; position: relative;">
                <?php if (empty($bar_labels)): ?>
                    <div style="text-align: center; color: var(--text-muted); padding-top: 100px;">Data transaksi sampah kosong.</div>
                <?php else: ?>
                    <canvas id="barVolumeChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <!-- Line Chart: Pertumbuhan Nasabah -->
        <div class="glass-card">
            <div class="section-header">
                <h2 class="section-title"><i class="fa-solid fa-chart-line" style="margin-right: 8px;"></i>Pertumbuhan Nasabah Kumulatif</h2>
            </div>
            <div style="height: 250px; position: relative;">
                <?php if (empty($line_labels)): ?>
                    <div style="text-align: center; color: var(--text-muted); padding-top: 100px;">Data nasabah kosong.</div>
                <?php else: ?>
                    <canvas id="lineUserChart"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mini Leaderboard Panel (Right Column) -->
    <div class="glass-card" style="height: 100%;">
        <div class="section-header" style="margin-bottom: 24px;">
            <h2 class="section-title"><i class="fa-solid fa-trophy" style="color: #f59e0b; margin-right: 8px;"></i>Top 5 Nasabah Terbaik</h2>
            <span class="badge badge-success" style="font-size: 9px; padding: 2px 8px;">Teratas</span>
        </div>
        
        <div class="leaderboard-list">
            <?php if (empty($top_nasabah)): ?>
                <div style="text-align: center; color: var(--text-muted); padding: 40px 0;">Belum ada nasabah terdaftar.</div>
            <?php else: ?>
                <?php 
                $rank = 1;
                foreach ($top_nasabah as $n): 
                    $badge = get_badge($n['poin_akumulasi']);
                    $rankClass = 'rank-other';
                    if ($rank === 1) $rankClass = 'rank-1';
                    elseif ($rank === 2) $rankClass = 'rank-2';
                    elseif ($rank === 3) $rankClass = 'rank-3';
                ?>
                    <div class="leaderboard-item">
                        <div class="rank-badge <?php echo $rankClass; ?>"><?php echo $rank; ?></div>
                        <div class="leaderboard-name"><?php echo sanitize($n['name']); ?></div>
                        <div style="text-align: right;">
                            <div class="leaderboard-pts"><?php echo number_format($n['poin_akumulasi']); ?> <span style="font-size: 9px; color: var(--text-muted); font-weight: 500;">pts</span></div>
                            <span class="badge <?php echo $badge['class']; ?>" style="font-size: 8px; padding: 2px 6px; margin-top: 4px;"><?php echo $badge['badge'] . ' ' . $badge['name']; ?></span>
                        </div>
                    </div>
                <?php 
                    $rank++;
                endforeach; 
                ?>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 24px; text-align: center;">
            <a href="admin_leaderboard.php" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center; font-size: 11px;">
                Lihat Selengkapnya <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
            </a>
        </div>
    </div>
</div>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Volume Sampah Bulanan (Bar Chart)
    const barCtx = document.getElementById('barVolumeChart');
    if (barCtx) {
        const barLabels = <?php echo json_encode($bar_labels); ?>;
        const barValues = <?php echo json_encode($bar_values); ?>;
        
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: barLabels,
                datasets: [{
                    label: 'Volume Sampah (kg)',
                    data: barValues,
                    backgroundColor: '#0F5132',
                    borderRadius: 6,
                    borderWidth: 0,
                    barThickness: 24
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#718096',
                            font: { family: "'Poppins', sans-serif", size: 10 }
                        }
                    },
                    y: {
                        grid: { color: 'rgba(0, 0, 0, 0.03)' },
                        ticks: {
                            color: '#718096',
                            font: { family: "'Poppins', sans-serif", size: 10 }
                        }
                    }
                }
            }
        });
    }

    // 2. Pertumbuhan Nasabah (Line Chart)
    const lineCtx = document.getElementById('lineUserChart');
    if (lineCtx) {
        const lineLabels = <?php echo json_encode($line_labels); ?>;
        const lineValues = <?php echo json_encode($line_values); ?>;
        
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: lineLabels,
                datasets: [{
                    label: 'Total Nasabah',
                    data: lineValues,
                    borderColor: '#2b8a3e',
                    backgroundColor: 'rgba(168, 230, 161, 0.1)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 3,
                    pointBackgroundColor: '#0F5132',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#718096',
                            font: { family: "'Poppins', sans-serif", size: 10 }
                        }
                    },
                    y: {
                        grid: { color: 'rgba(0, 0, 0, 0.03)' },
                        ticks: {
                            color: '#718096',
                            font: { family: "'Poppins', sans-serif", size: 10 },
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
