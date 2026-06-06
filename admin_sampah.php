<?php
// admin_sampah.php
// Halaman 3 - Manajemen Data Sampah (CRUD Lengkap)

require_once __DIR__ . '/includes/db.php';
require_login('admin');

$active_page = 'admin_sampah';
$page_title = 'Manajemen Data Sampah - TPS3R Gang Tani';

$error = '';
$success = '';

// 1. Handle Form Submissions (Create & Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_sampah'])) {
        $name = sanitize($_POST['name'] ?? '');
        $price = (int)($_POST['price_per_kg'] ?? 0);
        $points = (int)($_POST['point_per_kg'] ?? 0);
        $category = sanitize($_POST['category'] ?? 'Anorganik');

        if (empty($name)) {
            $error = 'Nama sampah wajib diisi!';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO waste_categories (name, price_per_kg, point_per_kg, category) 
                    VALUES (:name, :price, :points, :category)
                ");
                $stmt->execute([
                    'name' => $name,
                    'price' => $price,
                    'points' => $points,
                    'category' => $category
                ]);
                header("Location: admin_sampah.php?msg=Jenis+sampah+berhasil+ditambahkan!&msg_type=success");
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menambahkan data sampah: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['edit_sampah'])) {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name'] ?? '');
        $price = (int)($_POST['price_per_kg'] ?? 0);
        $points = (int)($_POST['point_per_kg'] ?? 0);
        $category = sanitize($_POST['category'] ?? 'Anorganik');

        if (empty($name)) {
            $error = 'Nama sampah wajib diisi!';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE waste_categories 
                    SET name = :name, price_per_kg = :price, point_per_kg = :points, category = :category 
                    WHERE id = :id
                ");
                $stmt->execute([
                    'name' => $name,
                    'price' => $price,
                    'points' => $points,
                    'category' => $category,
                    'id' => $id
                ]);
                header("Location: admin_sampah.php?msg=Data+sampah+berhasil+diperbarui!&msg_type=success");
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui data sampah: ' . $e->getMessage();
            }
        }
    }
}

// 2. Handle Delete Sampah
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM waste_categories WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: admin_sampah.php?msg=Data+sampah+berhasil+dihapus!&msg_type=success");
        exit;
    } catch (PDOException $e) {
        $error = 'Gagal menghapus data sampah. Jenis sampah ini sudah terpakai di riwayat transaksi setoran nasabah.';
    }
}

// 3. Edit mode load
$edit_mode = false;
$edit_waste = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM waste_categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_waste = $stmt->fetch();
    if ($edit_waste) {
        $edit_mode = true;
    }
}

// 4. Fetch all Waste
try {
    $search_query = "";
    $params = [];
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . sanitize($_GET['search']) . '%';
        $search_query = " WHERE name LIKE :search1 OR category LIKE :search2";
        $params['search1'] = $search;
        $params['search2'] = $search;
    }

    $stmt = $pdo->prepare("SELECT * FROM waste_categories $search_query ORDER BY category ASC, name ASC");
    $stmt->execute($params);
    $waste_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $waste_list = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title animate-fade-in">
    <h1>Manajemen Data Sampah</h1>
    <p>Kelola klasifikasi harga beli per kilogram dan bobot poin reward berdasarkan kategori sampah.</p>
</div>

<?php if ($error): ?>
    <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.2); color: var(--danger); padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 13px;">
        <i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i><?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="dashboard-sections">
    
    <!-- Table of Waste list -->
    <div class="glass-card">
        <div class="section-header" style="flex-wrap: wrap; gap: 15px; margin-bottom: 24px;">
            <h2 class="section-title"><i class="fa-solid fa-layer-group" style="margin-right: 8px;"></i>Daftar Jenis Sampah</h2>
            
            <form action="admin_sampah.php" method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 250px; margin-left: auto;">
                <input type="text" name="search" id="table-search" class="form-control" style="padding: 6px 12px; font-size: 12px;" placeholder="Cari nama/kategori..." value="<?php echo isset($_GET['search']) ? sanitize($_GET['search']) : ''; ?>">
                <?php if (isset($_GET['search'])): ?>
                    <a href="admin_sampah.php" class="btn btn-secondary btn-sm" style="padding: 6px 10px;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Jenis Sampah</th>
                        <th>Kategori</th>
                        <th>Harga / Kg</th>
                        <th>Poin / Kg</th>
                        <th style="text-align: right; width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($waste_list)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                Data sampah kosong.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        foreach ($waste_list as $w): 
                            $badgeClass = 'badge-success'; // Organik
                            if ($w['category'] === 'Anorganik') $badgeClass = 'badge-warning';
                            elseif ($w['category'] === 'B3') $badgeClass = 'badge-danger';
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td style="font-weight: 600; color: var(--primary);"><?php echo sanitize($w['name']); ?></td>
                                <td>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $w['category']; ?></span>
                                </td>
                                <td style="font-weight: 600; color: var(--success);">
                                    <?php echo $w['price_per_kg'] > 0 ? 'Rp' . number_format($w['price_per_kg'], 0, ',', '.') : '-'; ?>
                                </td>
                                <td style="font-weight: 700; color: #f59e0b;">⭐ <?php echo number_format($w['point_per_kg']); ?> pts</td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="admin_sampah.php?action=edit&id=<?php echo $w['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 5px 8px;" title="Edit">
                                        <i class="fa-solid fa-pen-to-square" style="color: var(--info);"></i>
                                    </a>
                                    <a href="admin_sampah.php?action=delete&id=<?php echo $w['id']; ?>" class="btn btn-danger btn-sm" style="padding: 5px 8px;" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus data sampah ini?');">
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

    <!-- Form Section (Sidebar style) -->
    <div class="glass-card">
        <?php if ($edit_mode): ?>
            <h2 class="section-title" style="margin-bottom: 20px; color: var(--info);"><i class="fa-solid fa-pen-to-square" style="margin-right: 8px;"></i>Edit Sampah</h2>
            
            <form action="admin_sampah.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $edit_waste['id']; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="edit_name">Nama Sampah</label>
                    <input type="text" name="name" id="edit_name" class="form-control" value="<?php echo sanitize($edit_waste['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_price">Harga Per Kg</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <span style="position: absolute; left: 14px; color: var(--text-secondary); font-size: 13px; font-weight: 600;">Rp</span>
                        <input type="number" name="price_per_kg" id="edit_price" class="form-control" style="padding-left: 35px;" value="<?php echo $edit_waste['price_per_kg']; ?>" min="0" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_point">Jumlah Poin Per Kg</label>
                    <input type="number" name="point_per_kg" id="edit_point" class="form-control" value="<?php echo $edit_waste['point_per_kg']; ?>" min="0" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="edit_category">Kategori Sampah</label>
                    <select name="category" id="edit_category" class="form-control" required>
                        <option value="Organik" <?php echo $edit_waste['category'] === 'Organik' ? 'selected' : ''; ?>>Organik</option>
                        <option value="Anorganik" <?php echo $edit_waste['category'] === 'Anorganik' ? 'selected' : ''; ?>>Anorganik</option>
                        <option value="B3" <?php echo $edit_waste['category'] === 'B3' ? 'selected' : ''; ?>>B3 (Bahan Berbahaya & Beracun)</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="edit_sampah" class="btn btn-primary" style="flex: 1; justify-content: center;">
                        Simpan
                    </button>
                    <a href="admin_sampah.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        <?php else: ?>
            <h2 class="section-title" style="margin-bottom: 20px;"><i class="fa-solid fa-circle-plus" style="margin-right: 8px;"></i>Tambah Jenis Sampah</h2>
            
            <form action="admin_sampah.php" method="POST">
                <input type="hidden" name="add_sampah" value="1">
                
                <div class="form-group">
                    <label class="form-label" for="add_name">Nama Sampah</label>
                    <input type="text" name="name" id="add_name" class="form-control" placeholder="Contoh: Botol Plastik PET" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="add_price">Harga Per Kg</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <span style="position: absolute; left: 14px; color: var(--text-secondary); font-size: 13px; font-weight: 600;">Rp</span>
                        <input type="number" name="price_per_kg" id="add_price" class="form-control" style="padding-left: 35px;" placeholder="0" min="0" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="add_point">Jumlah Poin Per Kg</label>
                    <input type="number" name="point_per_kg" id="add_point" class="form-control" placeholder="0" min="0" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="add_category">Kategori Sampah</label>
                    <select name="category" id="add_category" class="form-control" required>
                        <option value="Organik">Organik</option>
                        <option value="Anorganik" selected>Anorganik</option>
                        <option value="B3">B3 (Bahan Berbahaya & Beracun)</option>
                    </select>
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
