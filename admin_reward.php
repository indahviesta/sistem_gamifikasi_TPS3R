<?php
// admin_reward.php
// Halaman 4 - Manajemen Reward (CRUD Lengkap + Upload Gambar)

require_once __DIR__ . '/includes/db.php';
require_login('admin');

$active_page = 'admin_reward';
$page_title = 'Manajemen Reward - TPS3R Gang Tani';

$error = '';
$success = '';

// Helper to handle image upload
function upload_reward_image($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $upload_dir = 'uploads/rewards/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $sanitized_name = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
    $target_file = $upload_dir . $sanitized_name . '.' . $file_extension;
    
    // Validate image file
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return null;
    }
    
    // Limit file size (e.g., 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return null;
    }
    
    // Allowed formats
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array(strtolower($file_extension), $allowed)) {
        return null;
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    }
    
    return null;
}

// 1. Handle Form Submissions (Create & Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_reward'])) {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $points_required = (int)($_POST['points_required'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $status = $stock > 0 ? 'Tersedia' : 'Habis';
        
        $image_path = null;
        if (isset($_FILES['image'])) {
            $image_path = upload_reward_image($_FILES['image']);
        }

        if (empty($name) || $points_required <= 0) {
            $error = 'Nama Reward dan Poin Dibutuhkan wajib diisi!';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO rewards (name, description, points_required, stock, status, image_path) 
                    VALUES (:name, :description, :points, :stock, :status, :image)
                ");
                $stmt->execute([
                    'name' => $name,
                    'description' => $description,
                    'points' => $points_required,
                    'stock' => $stock,
                    'status' => $status,
                    'image' => $image_path
                ]);
                header("Location: admin_reward.php?msg=Reward+berhasil+ditambahkan!&msg_type=success");
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menambahkan reward: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['edit_reward'])) {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $points_required = (int)($_POST['points_required'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $status = $stock > 0 ? 'Tersedia' : 'Habis';
        $current_image = $_POST['current_image'] ?? null;
        
        $image_path = $current_image;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $new_image = upload_reward_image($_FILES['image']);
            if ($new_image) {
                $image_path = $new_image;
                // Delete old image if exists
                if ($current_image && file_exists($current_image)) {
                    unlink($current_image);
                }
            }
        }

        if (empty($name) || $points_required <= 0) {
            $error = 'Nama Reward dan Poin Dibutuhkan wajib diisi!';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE rewards 
                    SET name = :name, description = :description, points_required = :points, stock = :stock, status = :status, image_path = :image 
                    WHERE id = :id
                ");
                $stmt->execute([
                    'name' => $name,
                    'description' => $description,
                    'points' => $points_required,
                    'stock' => $stock,
                    'status' => $status,
                    'image' => $image_path,
                    'id' => $id
                ]);
                header("Location: admin_reward.php?msg=Reward+berhasil+diperbarui!&msg_type=success");
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui reward: ' . $e->getMessage();
            }
        }
    }
}

// 2. Handle Delete Reward
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        // Get image path first to delete file
        $stmt = $pdo->prepare("SELECT image_path FROM rewards WHERE id = ?");
        $stmt->execute([$delete_id]);
        $img = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("DELETE FROM rewards WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        if ($img && file_exists($img)) {
            unlink($img);
        }
        
        header("Location: admin_reward.php?msg=Reward+berhasil+dihapus!&msg_type=success");
        exit;
    } catch (PDOException $e) {
        $error = 'Gagal menghapus reward. Hadiah ini sudah pernah diklaim oleh nasabah.';
    }
}

// 3. Edit mode load
$edit_mode = false;
$edit_reward = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM rewards WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_reward = $stmt->fetch();
    if ($edit_reward) {
        $edit_mode = true;
    }
}

// 4. Fetch all Rewards
try {
    $search_query = "";
    $params = [];
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . sanitize($_GET['search']) . '%';
        $search_query = " WHERE name LIKE :search1 OR status LIKE :search2";
        $params['search1'] = $search;
        $params['search2'] = $search;
    }

    $stmt = $pdo->prepare("SELECT * FROM rewards $search_query ORDER BY points_required ASC, name ASC");
    $stmt->execute($params);
    $rewards_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $rewards_list = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title animate-fade-in">
    <h1>Manajemen Hadiah (Reward)</h1>
    <p>Kelola item hadiah beserta stok dan penentuan jumlah poin yang dibutuhkan nasabah untuk proses klaim.</p>
</div>

<?php if ($error): ?>
    <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.2); color: var(--danger); padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 13px;">
        <i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i><?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="dashboard-sections">
    
    <!-- Table List of Rewards -->
    <div class="glass-card">
        <div class="section-header" style="flex-wrap: wrap; gap: 15px; margin-bottom: 24px;">
            <h2 class="section-title"><i class="fa-solid fa-gift" style="margin-right: 8px;"></i>Daftar Hadiah Tersedia</h2>
            
            <form action="admin_reward.php" method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 250px; margin-left: auto;">
                <input type="text" name="search" id="table-search" class="form-control" style="padding: 6px 12px; font-size: 12px;" placeholder="Cari nama/status..." value="<?php echo isset($_GET['search']) ? sanitize($_GET['search']) : ''; ?>">
                <?php if (isset($_GET['search'])): ?>
                    <a href="admin_reward.php" class="btn btn-secondary btn-sm" style="padding: 6px 10px;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Foto</th>
                        <th>Nama Reward</th>
                        <th>Poin Dibutuhkan</th>
                        <th>Stok Hadiah</th>
                        <th>Status</th>
                        <th style="text-align: right; width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rewards_list)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                Data reward kosong.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        foreach ($rewards_list as $r): 
                            $statusClass = $r['status'] === 'Tersedia' ? 'badge-success' : 'badge-danger';
                            
                            // Image display
                            $img_src = 'https://images.unsplash.com/photo-1549465220-1a8b9238cd48?w=100&auto=format&fit=crop&q=60'; // default placeholder
                            if ($r['image_path'] && file_exists($r['image_path'])) {
                                $img_src = $r['image_path'];
                            }
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <div style="width: 48px; height: 48px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color); background: var(--bg-main);">
                                        <img src="<?php echo $img_src; ?>" alt="<?php echo sanitize($r['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--primary);"><?php echo sanitize($r['name']); ?></div>
                                    <div style="font-size: 11px; color: var(--text-secondary); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo sanitize($r['description']); ?></div>
                                </td>
                                <td style="font-weight: 700; color: #f59e0b;">⭐ <?php echo number_format($r['points_required']); ?> Pts</td>
                                <td style="font-weight: 600;"><?php echo $r['stock']; ?> Pcs</td>
                                <td>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $r['status']; ?></span>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="admin_reward.php?action=edit&id=<?php echo $r['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 5px 8px;" title="Edit">
                                        <i class="fa-solid fa-pen-to-square" style="color: var(--info);"></i>
                                    </a>
                                    <a href="admin_reward.php?action=delete&id=<?php echo $r['id']; ?>" class="btn btn-danger btn-sm" style="padding: 5px 8px;" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus reward ini?');">
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

    <!-- Form Section -->
    <div class="glass-card">
        <?php if ($edit_mode): ?>
            <h2 class="section-title" style="margin-bottom: 20px; color: var(--info);"><i class="fa-solid fa-gift-open" style="margin-right: 8px;"></i>Edit Reward</h2>
            
            <form action="admin_reward.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $edit_reward['id']; ?>">
                <input type="hidden" name="current_image" value="<?php echo $edit_reward['image_path']; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="edit_name">Nama Reward</label>
                    <input type="text" name="name" id="edit_name" class="form-control" value="<?php echo sanitize($edit_reward['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_points">Poin Dibutuhkan</label>
                    <input type="number" name="points_required" id="edit_points" class="form-control" value="<?php echo $edit_reward['points_required']; ?>" min="1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_stock">Stok Hadiah</label>
                    <input type="number" name="stock" id="edit_stock" class="form-control" value="<?php echo $edit_reward['stock']; ?>" min="0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_image">Ganti Gambar Reward (Ukuran maks: 2MB)</label>
                    <input type="file" name="image" id="edit_image" class="form-control" accept="image/*" style="padding: 6px 12px;">
                    <?php if ($edit_reward['image_path']): ?>
                        <div style="margin-top: 8px; font-size: 11px; color: var(--text-secondary);">Gambar saat ini: <code><?php echo basename($edit_reward['image_path']); ?></code></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="edit_desc">Deskripsi Reward</label>
                    <textarea name="description" id="edit_desc" class="form-control" placeholder="Tulis detail reward..."><?php echo sanitize($edit_reward['description']); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="edit_reward" class="btn btn-primary" style="flex: 1; justify-content: center;">
                        Simpan
                    </button>
                    <a href="admin_reward.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        <?php else: ?>
            <h2 class="section-title" style="margin-bottom: 20px;"><i class="fa-solid fa-circle-plus" style="margin-right: 8px;"></i>Tambah Reward</h2>
            
            <form action="admin_reward.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_reward" value="1">
                
                <div class="form-group">
                    <label class="form-label" for="add_name">Nama Reward</label>
                    <input type="text" name="name" id="add_name" class="form-control" placeholder="Contoh: Sembako Minyak & Beras" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="add_points">Poin Dibutuhkan</label>
                    <input type="number" name="points_required" id="add_points" class="form-control" placeholder="0" min="1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="add_stock">Stok Awal</label>
                    <input type="number" name="stock" id="add_stock" class="form-control" placeholder="0" min="0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="add_image">Upload Gambar Reward (Ukuran maks: 2MB)</label>
                    <input type="file" name="image" id="add_image" class="form-control" accept="image/*" style="padding: 6px 12px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="add_desc">Deskripsi Reward</label>
                    <textarea name="description" id="add_desc" class="form-control" placeholder="Tulis deskripsi detail hadiah..."></textarea>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
