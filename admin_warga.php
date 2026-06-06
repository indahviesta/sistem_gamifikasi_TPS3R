<?php
// admin_warga.php
// Halaman 2 - Manajemen Data Nasabah (CRUD + Ekspor)

require_once __DIR__ . '/includes/db.php';
require_login('admin');

$active_page = 'admin_warga';
$page_title = 'Manajemen Data Nasabah - TPS3R Gang Tani';

$error = '';
$success = '';

// 1. Handle Delete Nasabah
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'warga'");
        $stmt->execute([$delete_id]);
        header("Location: admin_warga.php?msg=Nasabah+berhasil+dihapus!&msg_type=success");
        exit;
    } catch (PDOException $e) {
        $error = 'Gagal menghapus data nasabah: ' . $e->getMessage();
    }
}

// 2. Handle Add / Edit Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_nasabah'])) {
        $name = sanitize($_POST['name'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $username_input = sanitize($_POST['username_input'] ?? '');
        $password_input = $_POST['password_input'] ?? '';

        if (empty($name) || empty($username_input) || empty($password_input)) {
            $error = 'Nama Lengkap, Username, dan Password wajib diisi!';
        } else {
            try {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR nik = ?");
                $stmt->execute([$username_input, $username_input]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Username sudah terdaftar! Gunakan username lain.';
                } else {
                    $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, address, phone, username, nik, password, role, saldo, poin, poin_akumulasi) 
                        VALUES (:name, :address, :phone, :username, :nik, :password, 'warga', 0, 0, 0)
                    ");
                    $stmt->execute([
                        'name' => $name,
                        'address' => $address,
                        'phone' => $phone,
                        'username' => $username_input,
                        'nik' => $username_input, // set NIK same as username for compatibility
                        'password' => $hashed_password
                    ]);
                    
                    header("Location: admin_warga.php?msg=Nasabah+baru+berhasil+ditambahkan!&msg_type=success");
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan nasabah: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['edit_nasabah'])) {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $username_input = sanitize($_POST['username_input'] ?? '');
        $password_input = $_POST['password_input'] ?? '';

        if (empty($name) || empty($username_input)) {
            $error = 'Nama Lengkap dan Username wajib diisi!';
        } else {
            try {
                // Check if username already exists excluding current user
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR nik = ?) AND id != ?");
                $stmt->execute([$username_input, $username_input, $id]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Username sudah digunakan nasabah lain!';
                } else {
                    if (!empty($password_input)) {
                        $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET name = :name, address = :address, phone = :phone, username = :username, nik = :nik, password = :password 
                            WHERE id = :id AND role = 'warga'
                        ");
                        $stmt->execute([
                            'name' => $name,
                            'address' => $address,
                            'phone' => $phone,
                            'username' => $username_input,
                            'nik' => $username_input,
                            'password' => $hashed_password,
                            'id' => $id
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET name = :name, address = :address, phone = :phone, username = :username, nik = :nik 
                            WHERE id = :id AND role = 'warga'
                        ");
                        $stmt->execute([
                            'name' => $name,
                            'address' => $address,
                            'phone' => $phone,
                            'username' => $username_input,
                            'nik' => $username_input,
                            'id' => $id
                        ]);
                    }
                    
                    header("Location: admin_warga.php?msg=Data+nasabah+berhasil+diperbarui!&msg_type=success");
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui nasabah: ' . $e->getMessage();
            }
        }
    }
}

// 3. Edit mode load
$edit_mode = false;
$edit_user = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'warga'");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();
    if ($edit_user) {
        $edit_mode = true;
    }
}

// 4. Detail mode load
$detail_mode = false;
$detail_user = null;
$detail_txs = [];
$detail_claims = [];
if (isset($_GET['action']) && $_GET['action'] === 'detail' && isset($_GET['id'])) {
    $detail_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'warga'");
    $stmt->execute([$detail_id]);
    $detail_user = $stmt->fetch();
    if ($detail_user) {
        $detail_mode = true;
        
        // Load setoran history
        $tx_stmt = $pdo->prepare("
            SELECT t.created_at, c.name AS waste_name, t.weight, t.points_earned, t.cash_earned, t.notes
            FROM transactions t
            JOIN waste_categories c ON t.category_id = c.id
            WHERE t.warga_id = ?
            ORDER BY t.created_at DESC
        ");
        $tx_stmt->execute([$detail_id]);
        $detail_txs = $tx_stmt->fetchAll();
        
        // Load claims history
        $cl_stmt = $pdo->prepare("
            SELECT rc.created_at, r.name AS reward_name, rc.points_spent
            FROM reward_claims rc
            JOIN rewards r ON rc.reward_id = r.id
            WHERE rc.warga_id = ?
            ORDER BY rc.created_at DESC
        ");
        $cl_stmt->execute([$detail_id]);
        $detail_claims = $cl_stmt->fetchAll();
    }
}

// 5. Fetch all Nasabah
try {
    $search_query = "";
    $params = [];
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . sanitize($_GET['search']) . '%';
        $search_query = " AND (name LIKE :search1 OR address LIKE :search2 OR phone LIKE :search3 OR username LIKE :search4)";
        $params['search1'] = $search;
        $params['search2'] = $search;
        $params['search3'] = $search;
        $params['search4'] = $search;
    }
    
    $nasabah_stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'warga' $search_query ORDER BY created_at DESC");
    $nasabah_stmt->execute($params);
    $nasabah_list = $nasabah_stmt->fetchAll();
} catch (PDOException $e) {
    $nasabah_list = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title animate-fade-in">
    <h1>Manajemen Data Nasabah</h1>
    <p>Registrasi nasabah baru, sunting biodata, dan pantau tabungan saldo serta perolehan poin mereka.</p>
</div>

<?php if ($error): ?>
    <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.2); color: var(--danger); padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 13px;">
        <i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i><?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="dashboard-sections">
    
    <!-- Table & List Nasabah (2 Columns span) -->
    <div class="glass-card">
        <div class="section-header" style="flex-wrap: wrap; gap: 15px; margin-bottom: 24px;">
            <div style="display: flex; gap: 10px;">
                <button onclick="window.location.href='admin_warga.php'" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-user-plus"></i> Tambah Nasabah
                </button>
                <a href="export_pdf.php" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                </a>
                <a href="export_excel.php" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-file-excel"></i> Export Excel
                </a>
            </div>
            
            <!-- Filter search -->
            <form action="admin_warga.php" method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 250px; margin-left: auto;">
                <input type="text" name="search" id="table-search" class="form-control" style="padding: 6px 12px; font-size: 12px;" placeholder="Cari Nasabah..." value="<?php echo isset($_GET['search']) ? sanitize($_GET['search']) : ''; ?>">
                <?php if (isset($_GET['search'])): ?>
                    <a href="admin_warga.php" class="btn btn-secondary btn-sm" style="padding: 6px 10px;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Nasabah</th>
                        <th>Alamat</th>
                        <th>No HP</th>
                        <th>Saldo (Uang)</th>
                        <th>Poin Aktif</th>
                        <th style="text-align: right; width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($nasabah_list)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                Data nasabah tidak ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        foreach ($nasabah_list as $n): 
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <div style="font-weight: 600; color: var(--primary);"><?php echo sanitize($n['name']); ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted); font-family: monospace;">username: <?php echo sanitize($n['username']); ?></div>
                                </td>
                                <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo sanitize($n['address'] ? $n['address'] : '-'); ?></td>
                                <td><?php echo sanitize($n['phone'] ? $n['phone'] : '-'); ?></td>
                                <td style="font-weight: 600; color: var(--success);">Rp<?php echo number_format($n['saldo'], 0, ',', '.'); ?></td>
                                <td style="font-weight: 700; color: #f59e0b;">⭐ <?php echo number_format($n['poin']); ?></td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="admin_warga.php?action=detail&id=<?php echo $n['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 5px 8px;" title="Detail Nasabah">
                                        <i class="fa-solid fa-eye" style="color: var(--primary);"></i>
                                    </a>
                                    <a href="admin_warga.php?action=edit&id=<?php echo $n['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 5px 8px;" title="Edit Nasabah">
                                        <i class="fa-solid fa-pen-to-square" style="color: var(--info);"></i>
                                    </a>
                                    <a href="admin_warga.php?action=delete&id=<?php echo $n['id']; ?>" class="btn btn-danger btn-sm" style="padding: 5px 8px;" title="Hapus Nasabah" onclick="return confirm('Apakah Anda yakin ingin menghapus nasabah ini? Seluruh riwayat setoran dan poin mereka akan dihapus permanen.');">
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

    <!-- Add / Edit Sidebar Form (Right side) -->
    <div class="glass-card">
        <?php if ($edit_mode): ?>
            <h2 class="section-title" style="margin-bottom: 20px; color: var(--info);"><i class="fa-solid fa-user-pen" style="margin-right: 8px;"></i>Edit Data Nasabah</h2>
            
            <form action="admin_warga.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $edit_user['id']; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="edit_name">Nama Lengkap</label>
                    <input type="text" name="name" id="edit_name" class="form-control" value="<?php echo sanitize($edit_user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_address">Alamat Lengkap</label>
                    <textarea name="address" id="edit_address" class="form-control" required><?php echo sanitize($edit_user['address']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_phone">Nomor HP</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control" value="<?php echo sanitize($edit_user['phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_username">Username Login</label>
                    <input type="text" name="username_input" id="edit_username" class="form-control" value="<?php echo sanitize($edit_user['username']); ?>" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="edit_password">Kata Sandi Baru (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password_input" id="edit_password" class="form-control" placeholder="Kata sandi baru">
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="edit_nasabah" class="btn btn-primary" style="flex: 1; justify-content: center;">
                        Simpan
                    </button>
                    <a href="admin_warga.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        <?php else: ?>
            <h2 class="section-title" style="margin-bottom: 20px;"><i class="fa-solid fa-user-plus" style="margin-right: 8px;"></i>Tambah Nasabah</h2>
            
            <form action="admin_warga.php" method="POST">
                <input type="hidden" name="add_nasabah" value="1">
                
                <div class="form-group">
                    <label class="form-label" for="add_name">Nama Lengkap</label>
                    <input type="text" name="name" id="add_name" class="form-control" placeholder="Masukkan nama lengkap nasabah" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="add_address">Alamat Lengkap</label>
                    <textarea name="address" id="add_address" class="form-control" placeholder="Masukkan alamat RT/RW Gang Tani" required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="add_phone">Nomor HP</label>
                    <input type="text" name="phone" id="add_phone" class="form-control" placeholder="08xxxxxxxxxx">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="add_username">Username Login</label>
                    <input type="text" name="username_input" id="add_username" class="form-control" placeholder="Gunakan NIK atau nama tanpa spasi" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="add_password">Kata Sandi</label>
                    <input type="password" name="password_input" id="add_password" class="form-control" placeholder="Masukkan kata sandi login nasabah" required>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;">
                        Simpan
                    </button>
                    <button type="reset" class="btn btn-secondary">Batal</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

</div>

<!-- Nasabah Detail Modal Overlay (Activated only when action=detail) -->
<?php if ($detail_mode && $detail_user): 
    $badge = get_badge($detail_user['poin_akumulasi']);
?>
<div class="modal active" id="detail-modal">
    <div class="modal-content" style="max-width: 750px; border-radius: var(--radius-lg); padding: 30px;">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fa-solid fa-id-card" style="color: var(--primary); margin-right: 8px;"></i>Profil Detail Nasabah</h2>
            <span class="modal-close" onclick="window.location.href='admin_warga.php'"><i class="fa-solid fa-circle-xmark" style="font-size: 20px;"></i></span>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 20px; margin-top: 20px; align-items: start;">
            <!-- Profile Column left -->
            <div class="glass-card" style="padding: 16px; background: var(--bg-main); text-align: center; border-radius: var(--radius-md);">
                <div class="user-avatar" style="width: 80px; height: 80px; margin: 0 auto 16px auto; font-size: 32px; background: var(--primary); color: #ffffff;">
                    <?php echo strtoupper(substr($detail_user['name'], 0, 1)); ?>
                </div>
                <h3 style="font-size: 16px; font-weight: 700; color: var(--primary);"><?php echo sanitize($detail_user['name']); ?></h3>
                <span class="badge <?php echo $badge['class']; ?>" style="font-size: 10px; margin-top: 8px;"><?php echo $badge['badge'] . ' ' . $badge['name']; ?></span>
                
                <div style="text-align: left; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 15px; font-size: 12px; display: flex; flex-direction: column; gap: 8px;">
                    <div><strong>Username:</strong> <code style="color: var(--primary);"><?php echo sanitize($detail_user['username']); ?></code></div>
                    <div><strong>No HP:</strong> <?php echo sanitize($detail_user['phone'] ? $detail_user['phone'] : '-'); ?></div>
                    <div><strong>Alamat:</strong> <?php echo sanitize($detail_user['address'] ? $detail_user['address'] : '-'); ?></div>
                    <div><strong>Tgl Daftar:</strong> <?php echo date('d M Y', strtotime($detail_user['created_at'])); ?></div>
                </div>
            </div>

            <!-- Financial and points info right -->
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <!-- Balance -->
                    <div class="glass-card" style="padding: 12px 16px; border-left: 4px solid var(--success);">
                        <div style="font-size: 11px; color: var(--text-secondary); font-weight: 600;">SALDO UANG</div>
                        <div style="font-size: 18px; font-weight: 700; color: var(--success); margin-top: 4px;">Rp<?php echo number_format($detail_user['saldo'], 0, ',', '.'); ?></div>
                    </div>
                    <!-- Points -->
                    <div class="glass-card" style="padding: 12px 16px; border-left: 4px solid #f59e0b;">
                        <div style="font-size: 11px; color: var(--text-secondary); font-weight: 600;">POIN AKTIF</div>
                        <div style="font-size: 18px; font-weight: 700; color: #f59e0b; margin-top: 4px;">⭐ <?php echo number_format($detail_user['poin']); ?></div>
                    </div>
                </div>
                
                <!-- History Tabs Accordion -->
                <div class="glass-card" style="padding: 16px; max-height: 250px; overflow-y: auto;">
                    <h4 style="font-size: 13px; font-weight: 700; color: var(--primary); border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 10px;">Riwayat Setoran Terakhir</h4>
                    <table class="custom-table" style="font-size: 11px;">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Sampah</th>
                                <th>Berat</th>
                                <th>Uang</th>
                                <th>Poin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($detail_txs)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 10px;">Belum ada setoran sampah.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($detail_txs, 0, 5) as $tx): ?>
                                    <tr>
                                        <td><?php echo date('d/m/y', strtotime($tx['created_at'])); ?></td>
                                        <td><?php echo $tx['waste_name']; ?></td>
                                        <td><?php echo $tx['weight']; ?> kg</td>
                                        <td style="color: var(--success);">+Rp<?php echo number_format($tx['cash_earned']); ?></td>
                                        <td style="color: #f59e0b;">+<?php echo $tx['points_earned']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Claim rewards history -->
                <div class="glass-card" style="padding: 16px; max-height: 150px; overflow-y: auto;">
                    <h4 style="font-size: 13px; font-weight: 700; color: #0dcaf0; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 10px;">Klaim Hadiah ditukarkan</h4>
                    <table class="custom-table" style="font-size: 11px;">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Hadiah</th>
                                <th>Poin Dipakai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($detail_claims)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 10px;">Belum pernah menukar reward.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($detail_claims as $cl): ?>
                                    <tr>
                                        <td><?php echo date('d/m/y', strtotime($cl['created_at'])); ?></td>
                                        <td><?php echo $cl['reward_name']; ?></td>
                                        <td style="color: var(--danger);">-<?php echo $cl['points_spent']; ?> Pts</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
