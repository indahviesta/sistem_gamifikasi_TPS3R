<?php
// admin_transaksi.php
// Halaman Penerimaan Sampah - Catat Setoran & Log Transaksi Audit

require_once __DIR__ . '/includes/db.php';
require_login('admin');

$active_page = 'admin_transaksi';
$page_title = 'Penerimaan Sampah - TPS3R Gang Tani';

$error = '';
$success = '';

// 1. Handle Form Submission (Record Transaction)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    $warga_id = (int)($_POST['warga_id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $weight = (float)($_POST['weight'] ?? 0.0);
    $notes = sanitize($_POST['notes'] ?? '');
    $admin_id = $_SESSION['user_id'];

    if ($warga_id <= 0 || $category_id <= 0 || $weight <= 0) {
        $error = 'Nasabah, Kategori Sampah, dan Berat harus valid!';
    } else {
        try {
            // Fetch points and price from DB (Server-side calculation for security)
            $stmt = $pdo->prepare("SELECT price_per_kg, point_per_kg, name FROM waste_categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $category = $stmt->fetch();

            if (!$category) {
                $error = 'Jenis sampah tidak ditemukan!';
            } else {
                $points_earned = round($weight * $category['point_per_kg']);
                $cash_earned = round($weight * $category['price_per_kg']);
                
                // Fetch citizen/warga name
                $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? AND role = 'warga'");
                $stmt->execute([$warga_id]);
                $warga_name = $stmt->fetchColumn();

                if (!$warga_name) {
                    $error = 'Data nasabah tidak ditemukan!';
                } else {
                    // Insert transaction
                    $stmt = $pdo->prepare("
                        INSERT INTO transactions (warga_id, admin_id, category_id, weight, points_earned, cash_earned, notes)
                        VALUES (:warga_id, :admin_id, :category_id, :weight, :points_earned, :cash_earned, :notes)
                    ");
                    $stmt->execute([
                        'warga_id' => $warga_id,
                        'admin_id' => $admin_id,
                        'category_id' => $category_id,
                        'weight' => $weight,
                        'points_earned' => $points_earned,
                        'cash_earned' => $cash_earned,
                        'notes' => $notes
                    ]);

                    // Sync citizen's balances (saldo and points)
                    sync_nasabah_balance($pdo, $warga_id);

                    $success_msg = "Setoran sampah " . urlencode($warga_name) . " seberat " . $weight . " kg berhasil dicatat! (+Rp" . number_format($cash_earned) . " | +" . $points_earned . " Poin)";
                    header("Location: admin_transaksi.php?msg=$success_msg&msg_type=success");
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan transaksi setoran: ' . $e->getMessage();
        }
    }
}

// 2. Handle Delete Transaction (Cancellation)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        // Fetch warga_id of this transaction first to sync balance after deletion
        $stmt = $pdo->prepare("SELECT warga_id FROM transactions WHERE id = ?");
        $stmt->execute([$delete_id]);
        $warga_id = $stmt->fetchColumn();
        
        if ($warga_id) {
            // Delete transaction
            $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
            $stmt->execute([$delete_id]);
            
            // Re-sync balance
            sync_nasabah_balance($pdo, $warga_id);
            
            header("Location: admin_transaksi.php?msg=Transaksi+telah+dibatalkan+dan+saldo/poin+nasabah+telah+dikurangi!&msg_type=success");
            exit;
        } else {
            $error = 'Transaksi tidak ditemukan!';
        }
    } catch (PDOException $e) {
        $error = 'Gagal menghapus transaksi: ' . $e->getMessage();
    }
}

// 3. Fetch Warga/Nasabah List for Select options
try {
    $warga_options = $pdo->query("SELECT id, name, username FROM users WHERE role = 'warga' ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $warga_options = [];
}

// 4. Fetch Waste Categories for Select options
try {
    $category_options = $pdo->query("SELECT id, name, price_per_kg, point_per_kg, category FROM waste_categories ORDER BY category ASC, name ASC")->fetchAll();
} catch (PDOException $e) {
    $category_options = [];
}

// 5. Fetch Transaction Logs (with search)
try {
    $search_query = "";
    $params = [];
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . sanitize($_GET['search']) . '%';
        $search_query = " WHERE (w.name LIKE :search1 OR c.name LIKE :search2 OR t.notes LIKE :search3 OR w.username LIKE :search4)";
        $params['search1'] = $search;
        $params['search2'] = $search;
        $params['search3'] = $search;
        $params['search4'] = $search;
    }

    $tx_query = "
        SELECT 
            t.id,
            w.name AS warga_name,
            w.username AS warga_username,
            c.name AS category_name,
            c.category AS cat_group,
            t.weight,
            t.points_earned,
            t.cash_earned,
            t.notes,
            t.created_at,
            a.name AS admin_name
        FROM transactions t
        JOIN users w ON t.warga_id = w.id
        JOIN waste_categories c ON t.category_id = c.id
        JOIN users a ON t.admin_id = a.id
        $search_query
        ORDER BY t.created_at DESC
    ";
    
    $stmt = $pdo->prepare($tx_query);
    $stmt->execute($params);
    $transactions_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $transactions_list = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title animate-fade-in">
    <h1>Penerimaan & Pencatatan Sampah</h1>
    <p>Catat setoran sampah pilah dari nasabah, hitung otomatis perolehan saldo uang dan poin reward.</p>
</div>

<?php if ($error): ?>
    <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.2); color: var(--danger); padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 13px;">
        <i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i><?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="dashboard-sections">
    
    <!-- Table of Transaction Logs -->
    <div class="glass-card" style="grid-column: span 1;">
        <div class="section-header" style="flex-wrap: wrap; gap: 15px; margin-bottom: 24px;">
            <h2 class="section-title"><i class="fa-solid fa-receipt" style="margin-right: 8px;"></i>Log Riwayat Setoran</h2>
            
            <form action="admin_transaksi.php" method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 250px; margin-left: auto;">
                <input type="text" name="search" id="table-search" class="form-control" style="padding: 6px 12px; font-size: 12px;" placeholder="Cari log setoran..." value="<?php echo isset($_GET['search']) ? sanitize($_GET['search']) : ''; ?>">
                <?php if (isset($_GET['search'])): ?>
                    <a href="admin_transaksi.php" class="btn btn-secondary btn-sm" style="padding: 6px 10px;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Nasabah</th>
                        <th>Kategori Sampah</th>
                        <th>Berat (kg)</th>
                        <th>Uang Hasil</th>
                        <th>Poin</th>
                        <th>Petugas / Waktu</th>
                        <th style="text-align: right; width: 60px;">Batal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions_list)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                Belum ada riwayat transaksi setoran sampah.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions_list as $tx): 
                            $badgeClass = 'badge-success'; // Organik
                            if ($tx['cat_group'] === 'Anorganik') $badgeClass = 'badge-warning';
                            elseif ($tx['cat_group'] === 'B3') $badgeClass = 'badge-danger';
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--primary);"><?php echo sanitize($tx['warga_name']); ?></div>
                                    <div style="font-size: 10px; color: var(--text-secondary); font-family: monospace;">user: <?php echo sanitize($tx['warga_username']); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo sanitize($tx['category_name']); ?></div>
                                    <span class="badge <?php echo $badgeClass; ?>" style="font-size: 8px; padding: 2px 6px; margin-top: 2px;"><?php echo $tx['cat_group']; ?></span>
                                </td>
                                <td style="font-weight: 600;"><?php echo $tx['weight']; ?> kg</td>
                                <td style="font-weight: 600; color: var(--success);">+Rp<?php echo number_format($tx['cash_earned'], 0, ',', '.'); ?></td>
                                <td style="font-weight: 700; color: #f59e0b;">+<?php echo $tx['points_earned']; ?> Pts</td>
                                <td style="font-size: 11px; color: var(--text-secondary);">
                                    <div><?php echo format_date_id($tx['created_at']); ?></div>
                                    <div style="font-size: 10px; color: var(--text-muted);">Admin: <?php echo sanitize($tx['admin_name']); ?></div>
                                </td>
                                <td style="text-align: right;">
                                    <a href="admin_transaksi.php?action=delete&id=<?php echo $tx['id']; ?>" class="btn btn-danger btn-sm" style="padding: 5px 8px;" title="Batalkan Transaksi" onclick="return confirm('Apakah Anda yakin ingin membatalkan transaksi ini? Saldo rupiah dan poin nasabah bersangkutan akan dikurangi.');">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form Record Transaction (Sidebar style) -->
    <div class="glass-card">
        <h2 class="section-title" style="margin-bottom: 20px;"><i class="fa-solid fa-cart-flatbed-suitcases" style="margin-right: 8px;"></i>Penerimaan Setoran</h2>
        
        <form action="admin_transaksi.php" method="POST">
            <input type="hidden" name="add_transaction" value="1">
            
            <!-- Select Warga -->
            <div class="form-group">
                <label class="form-label" for="warga_id">Nama Nasabah</label>
                <select name="warga_id" id="warga_id" class="form-control" required>
                    <option value="" disabled selected>-- Pilih Nasabah --</option>
                    <?php foreach ($warga_options as $w): ?>
                        <option value="<?php echo $w['id']; ?>">
                            <?php echo sanitize($w['name']); ?> (Username: <?php echo sanitize($w['username']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Select Category -->
            <div class="form-group">
                <label class="form-label" for="category_id">Jenis Sampah</label>
                <select name="category_id" id="category_id" class="form-control" required>
                    <option value="" disabled selected>-- Pilih Jenis Sampah --</option>
                    <?php foreach ($category_options as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" data-points="<?php echo $cat['point_per_kg']; ?>" data-price="<?php echo $cat['price_per_kg']; ?>">
                            [<?php echo $cat['category']; ?>] <?php echo sanitize($cat['name']); ?> (Rp<?php echo number_format($cat['price_per_kg']); ?>/kg | ⭐ <?php echo $cat['point_per_kg']; ?> Pts/kg)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Weight Input -->
            <div class="form-group">
                <label class="form-label" for="weight">Berat Bersih (kg)</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="number" step="0.01" name="weight" id="weight" class="form-control" placeholder="0.00" min="0.01" style="padding-right: 45px;" required>
                    <span style="position: absolute; right: 16px; color: var(--text-muted); font-size: 13px; font-weight: 600; pointer-events: none;">kg</span>
                </div>
            </div>
            
            <!-- Estimates Calculator Output widget -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <!-- Saldo Rupiah -->
                <div style="background: rgba(25, 135, 84, 0.03); border: 1px dashed rgba(25, 135, 84, 0.2); padding: 12px 14px; border-radius: var(--radius-sm);">
                    <label class="form-label" style="margin-bottom: 4px; color: var(--success); font-size: 11px;">Estimasi Saldo (Uang)</label>
                    <input type="text" id="cash_earned" class="form-control" style="background: transparent; border: none; padding: 0; font-size: 18px; font-weight: 700; color: var(--success); height: auto;" value="Rp0" readonly>
                </div>
                <!-- Poin Reward -->
                <div style="background: rgba(245, 158, 11, 0.03); border: 1px dashed rgba(245, 158, 11, 0.2); padding: 12px 14px; border-radius: var(--radius-sm);">
                    <label class="form-label" style="margin-bottom: 4px; color: #d97706; font-size: 11px;">Estimasi Poin</label>
                    <input type="text" id="points_earned" class="form-control" style="background: transparent; border: none; padding: 0; font-size: 18px; font-weight: 700; color: #d97706; height: auto;" value="0" readonly>
                </div>
            </div>
            
            <!-- Notes -->
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label" for="notes">Catatan Transaksi (Opsional)</label>
                <textarea name="notes" id="notes" class="form-control" placeholder="Tulis catatan kondisi sampah..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px;">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Transaksi Setoran
            </button>
        </form>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
