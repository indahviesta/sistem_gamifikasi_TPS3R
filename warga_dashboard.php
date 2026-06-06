<?php
// warga_dashboard.php
// Halaman Portal Nasabah (Warga) - Stats, Riwayat Setoran, & Toko Penukaran Poin (Redeem Rewards)

require_once __DIR__ . '/includes/db.php';
require_login('warga'); // Restrict to warga/nasabah only

$active_page = 'warga_dashboard';
$page_title = 'Portal Nasabah - TPS3R Gang Tani';

$warga_id = $_SESSION['user_id'];
$warga_name = $_SESSION['name'];

$error = '';
$success = '';

// 1. Handle Claim Reward Request
if (isset($_GET['action']) && $_GET['action'] === 'claim' && isset($_GET['reward_id'])) {
    $reward_id = (int)$_GET['reward_id'];
    
    try {
        // Fetch reward info
        $stmt = $pdo->prepare("SELECT * FROM rewards WHERE id = ?");
        $stmt->execute([$reward_id]);
        $reward = $stmt->fetch();
        
        if (!$reward) {
            $error = 'Hadiah tidak ditemukan!';
        } elseif ($reward['stock'] <= 0 || $reward['status'] === 'Habis') {
            $error = 'Maaf, stok hadiah ini sedang habis!';
        } else {
            // Get citizen current points balance
            $current_points = get_warga_points($pdo, $warga_id);
            
            if ($current_points < $reward['points_required']) {
                $error = 'Poin Anda tidak mencukupi untuk menukarkan hadiah ini!';
            } else {
                // Process claim transaction
                $pdo->beginTransaction();
                
                // 1. Insert claim record
                $stmt = $pdo->prepare("INSERT INTO reward_claims (warga_id, reward_id, points_spent) VALUES (?, ?, ?)");
                $stmt->execute([$warga_id, $reward_id, $reward['points_required']]);
                
                // 2. Deduct active points
                $stmt = $pdo->prepare("UPDATE users SET poin = poin - ? WHERE id = ?");
                $stmt->execute([$reward['points_required'], $warga_id]);
                
                // 3. Deduct reward stock
                $new_stock = $reward['stock'] - 1;
                $new_status = $new_stock > 0 ? 'Tersedia' : 'Habis';
                $stmt = $pdo->prepare("UPDATE rewards SET stock = ?, status = ? WHERE id = ?");
                $stmt->execute([$new_stock, $new_status, $reward_id]);
                
                $pdo->commit();
                
                // Sync nasabah balance just to be safe
                sync_nasabah_balance($pdo, $warga_id);
                
                $msg = "Penukaran Poin Berhasil! Silakan ambil '" . urlencode($reward['name']) . "' Anda di kantor petugas TPS3R Gang Tani.";
                header("Location: warga_dashboard.php?msg=$msg&msg_type=success");
                exit;
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Terjadi kesalahan transaksi: ' . $e->getMessage();
    }
}

// 2. Fetch Personal Stats
try {
    // Refresh balances from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$warga_id]);
    $nasabah = $stmt->fetch();
    
    $my_points = $nasabah['poin'];
    $my_points_accumulated = $nasabah['poin_akumulasi'];
    $my_saldo = $nasabah['saldo'];
    
    // Get total weight deposited
    $my_weight = get_warga_weight($pdo, $warga_id);
    
    // Get rank (comparing accumulated points)
    $rank_stmt = $pdo->prepare("
        SELECT COUNT(*) + 1 FROM (
            SELECT u.id, u.poin_akumulasi 
            FROM users u
            WHERE u.role = 'warga'
            GROUP BY u.id
        ) as subquery 
        WHERE poin_akumulasi > ?
    ");
    $rank_stmt->execute([$my_points_accumulated]);
    $my_rank = $rank_stmt->fetchColumn();
    
    $badge = get_badge($my_points_accumulated);
} catch (PDOException $e) {
    $my_points = $my_points_accumulated = $my_saldo = $my_weight = 0;
    $my_rank = '-';
    $badge = ['name' => 'Eco Starter', 'badge' => '🌱', 'class' => 'badge-eco-starter'];
}

// 3. Fetch Personal Deposits History
try {
    $tx_stmt = $pdo->prepare("
        SELECT 
            t.created_at,
            c.name AS waste_name,
            t.weight,
            t.points_earned,
            t.cash_earned,
            t.notes,
            a.name AS admin_name
        FROM transactions t
        JOIN waste_categories c ON t.category_id = c.id
        JOIN users a ON t.admin_id = a.id
        WHERE t.warga_id = ?
        ORDER BY t.created_at DESC
    ");
    $tx_stmt->execute([$warga_id]);
    $my_txs = $tx_stmt->fetchAll();
} catch (PDOException $e) {
    $my_txs = [];
}

// 4. Fetch Available Rewards for Exchange
try {
    $rewards = $pdo->query("SELECT * FROM rewards ORDER BY points_required ASC")->fetchAll();
} catch (PDOException $e) {
    $rewards = [];
}

// 5. Fetch Education materials for public view
try {
    $edu_list = $pdo->query("SELECT * FROM education ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    $edu_list = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title animate-fade-in" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <h1>Halo, <?php echo sanitize($warga_name); ?>!</h1>
        <p>Gunakan portal nasabah untuk memantau tabungan dan menukarkan poin reward Anda.</p>
    </div>
    
    <span class="badge <?php echo $badge['class']; ?>" style="padding: 8px 16px; font-size: 11px; box-shadow: var(--shadow-sm);">
        <?php echo $badge['badge'] . ' ' . $badge['name']; ?> (Lifetime)
    </span>
</div>

<?php if ($error): ?>
    <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.2); color: var(--danger); padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 13px;">
        <i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i><?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="stats-grid animate-fade-in">
    <!-- Poin Aktif -->
    <div class="glass-card stat-card" style="border-left-color: #f59e0b;">
        <div class="stat-header">
            <span class="stat-title">Poin Anda (Aktif)</span>
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                <i class="fa-solid fa-star"></i>
            </div>
        </div>
        <div class="stat-value" style="color: #d97706;"><?php echo number_format($my_points); ?> <span style="font-size: 14px; font-weight: 500; color: var(--text-secondary);">pts</span></div>
        <div class="stat-desc">Dapat digunakan untuk tukar reward</div>
    </div>

    <!-- Saldo Tabungan -->
    <div class="glass-card stat-card" style="border-left-color: var(--success);">
        <div class="stat-header">
            <span class="stat-title">Saldo Uang Anda</span>
            <div class="stat-icon" style="background: rgba(25, 135, 84, 0.1); color: var(--success);">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </div>
        <div class="stat-value" style="color: var(--success);">Rp<?php echo number_format($my_saldo, 0, ',', '.'); ?></div>
        <div class="stat-desc">Hasil konversi sampah menjadi rupiah</div>
    </div>

    <!-- Peringkat Scoreboard -->
    <div class="glass-card stat-card" style="border-left-color: var(--primary);">
        <div class="stat-header">
            <span class="stat-title">Peringkat Papan Skor</span>
            <div class="stat-icon" style="background: var(--primary-glow); color: var(--primary);">
                <i class="fa-solid fa-ranking-star"></i>
            </div>
        </div>
        <div class="stat-value">#<?php echo $my_rank; ?></div>
        <div class="stat-desc">Dari seluruh nasabah terdaftar</div>
    </div>

    <!-- Total Sampah Disetor -->
    <div class="glass-card stat-card" style="border-left-color: #0dcaf0;">
        <div class="stat-header">
            <span class="stat-title">Total Sampah Disetor</span>
            <div class="stat-icon" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo $my_weight; ?> <span style="font-size: 14px; font-weight: 500; color: var(--text-secondary);">kg</span></div>
        <div class="stat-desc">Total kontribusi berat sampah terpilah</div>
    </div>
</div>

<!-- Main Sections: Left is Deposits, Right is Claims Shop -->
<div class="dashboard-sections animate-fade-in" style="animation-delay: 0.1s;">
    
    <!-- Left: Deposits History Logs -->
    <div class="glass-card">
        <h2 class="section-title" style="margin-bottom: 20px;"><i class="fa-solid fa-clock-rotate-left" style="margin-right: 8px;"></i>Riwayat Setoran Sampah Anda</h2>
        
        <div class="table-responsive">
            <table class="custom-table" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th>Tanggal Setor</th>
                        <th>Jenis Sampah</th>
                        <th>Berat</th>
                        <th>Uang Hasil</th>
                        <th>Poin Diperoleh</th>
                        <th>Petugas Penerima</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($my_txs)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                Anda belum memiliki riwayat transaksi setoran sampah.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($my_txs as $tx): ?>
                            <tr>
                                <td><?php echo date('d M Y, H:i', strtotime($tx['created_at'])); ?></td>
                                <td style="font-weight: 600; color: var(--primary);"><?php echo sanitize($tx['waste_name']); ?></td>
                                <td style="font-weight: 600;"><?php echo $tx['weight']; ?> kg</td>
                                <td style="font-weight: 600; color: var(--success);">+Rp<?php echo number_format($tx['cash_earned'], 0, ',', '.'); ?></td>
                                <td style="font-weight: 700; color: #f59e0b;">+<?php echo $tx['points_earned']; ?> Pts</td>
                                <td style="color: var(--text-secondary);"><?php echo sanitize($tx['admin_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right: Redeem Reward Shop -->
    <div class="glass-card" style="height: 100%;">
        <h2 class="section-title" style="margin-bottom: 20px;"><i class="fa-solid fa-gifts" style="color: #dc3545; margin-right: 8px;"></i>Tukarkan Poin Hadiah</h2>
        
        <div style="display: flex; flex-direction: column; gap: 16px; max-height: 500px; overflow-y: auto; padding-right: 5px;">
            <?php if (empty($rewards)): ?>
                <div style="text-align: center; color: var(--text-muted); padding: 30px;">Toko reward kosong.</div>
            <?php else: ?>
                <?php foreach ($rewards as $r): 
                    $btnClass = 'btn-primary';
                    $btnText = 'Tukarkan Poin';
                    $disabled = false;
                    
                    if ($r['stock'] <= 0 || $r['status'] === 'Habis') {
                        $btnClass = 'btn-secondary';
                        $btnText = 'Stok Habis';
                        $disabled = true;
                    } elseif ($my_points < $r['points_required']) {
                        $btnClass = 'btn-secondary';
                        $btnText = 'Poin Tidak Cukup';
                        $disabled = true;
                    }
                    
                    $img_src = 'https://images.unsplash.com/photo-1549465220-1a8b9238cd48?w=150&auto=format&fit=crop&q=60';
                    if ($r['image_path'] && file_exists($r['image_path'])) {
                        $img_src = $r['image_path'];
                    }
                ?>
                    <div style="display: flex; gap: 12px; background: var(--bg-main); border-radius: var(--radius-md); padding: 12px; border: 1px solid var(--border-color); align-items: center;">
                        <div style="width: 64px; height: 64px; border-radius: 8px; overflow: hidden; background: var(--surface); border: 1px solid var(--border-color); flex-shrink: 0;">
                            <img src="<?php echo $img_src; ?>" alt="<?php echo sanitize($r['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <h4 style="font-size: 13px; font-weight: 700; color: var(--primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo sanitize($r['name']); ?></h4>
                            <div style="font-size: 10px; color: var(--text-secondary); margin-top: 2px;">Butuh: <strong style="color: #d97706;">⭐ <?php echo $r['points_required']; ?> Pts</strong></div>
                            <div style="font-size: 9px; color: var(--text-muted); margin-top: 2px;">Stok: <?php echo $r['stock']; ?> pcs</div>
                        </div>
                        <div style="flex-shrink: 0;">
                            <?php if ($disabled): ?>
                                <button class="btn btn-secondary btn-sm" style="font-size: 10px; padding: 6px 10px;" disabled><?php echo $btnText; ?></button>
                            <?php else: ?>
                                <a href="warga_dashboard.php?action=claim&reward_id=<?php echo $r['id']; ?>" class="btn btn-primary btn-sm" style="font-size: 10px; padding: 6px 10px; background-color: #0F5132; box-shadow: none;" onclick="return confirm('Apakah Anda yakin ingin menukarkan <?php echo $r['points_required']; ?> poin dengan <?php echo sanitize($r['name']); ?>?');">
                                    <i class="fa-solid fa-gift"></i> Tukar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Trash Education Section (Bottom wide card) -->
<div class="glass-card animate-fade-in" style="animation-delay: 0.2s; margin-top: 24px;">
    <h2 class="section-title" style="margin-bottom: 20px;"><i class="fa-solid fa-graduation-cap" style="margin-right: 8px; color: var(--primary-light);"></i>Panduan Edukasi Pemilahan Sampah</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
        <?php foreach ($edu_list as $e): 
            $color_code = '#6c757d';
            $text_color = '#ffffff';
            $color_name = strtolower($e['bin_color']);
            if ($color_name === 'hijau') $color_code = '#198754';
            elseif ($color_name === 'kuning') { $color_code = '#ffc107'; $text_color = '#000000'; }
            elseif ($color_name === 'biru') $color_code = '#0d6efd';
            elseif ($color_name === 'merah') $color_code = '#dc3545';
            elseif ($color_name === 'abu-abu') $color_code = '#6c757d';
            
            $img_src = 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?w=200&auto=format&fit=crop&q=60';
            if ($e['image_path'] && file_exists($e['image_path'])) {
                $img_src = $e['image_path'];
            }
        ?>
            <div style="background: var(--bg-main); border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
                <div style="height: 120px; overflow: hidden; position: relative;">
                    <img src="<?php echo $img_src; ?>" alt="<?php echo sanitize($e['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <span class="badge" style="position: absolute; top: 12px; right: 12px; background: <?php echo $color_code; ?>; color: <?php echo $text_color; ?>; box-shadow: var(--shadow-sm);">
                        Wadah: <?php echo sanitize($e['bin_color']); ?>
                    </span>
                </div>
                <div style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                    <h4 style="font-size: 13px; font-weight: 700; color: var(--primary);"><?php echo sanitize($e['title']); ?></h4>
                    <p style="font-size: 11px; color: var(--text-secondary); margin-top: 6px; line-height: 1.4; flex: 1;"><?php echo sanitize($e['description']); ?></p>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 10px; margin-top: 12px; font-size: 10px;">
                        <span>Jenis: <strong><?php echo sanitize($e['waste_type']); ?></strong></span>
                        <span style="color: #d97706; font-weight: 700;">⭐ <?php echo sanitize($e['point_category']); ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
