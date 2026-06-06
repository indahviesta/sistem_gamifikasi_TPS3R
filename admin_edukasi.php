<?php
// admin_edukasi.php
// Halaman 5 - Manajemen Edukasi Sampah (CRUD Lengkap + Upload Gambar)

require_once __DIR__ . '/includes/db.php';
require_login('admin');

$active_page = 'admin_edukasi';
$page_title = 'Manajemen Edukasi Sampah - TPS3R';

$error = '';
$success = '';

// Helper to handle image upload
function upload_education_image($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $upload_dir = 'uploads/education/';
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
    if (isset($_POST['add_education'])) {
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $waste_type = sanitize($_POST['waste_type'] ?? '');
        $point_category = sanitize($_POST['point_category'] ?? '');
        $bin_color = sanitize($_POST['bin_color'] ?? '');
        
        $image_path = null;
        if (isset($_FILES['image'])) {
            $image_path = upload_education_image($_FILES['image']);
        }

        if (empty($title) || empty($waste_type) || empty($bin_color)) {
            $error = 'Judul Materi, Jenis Sampah, dan Warna Tempat Sampah wajib diisi!';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO education (title, description, waste_type, point_category, bin_color, image_path) 
                    VALUES (:title, :description, :waste_type, :point_category, :bin_color, :image)
                ");
                $stmt->execute([
                    'title' => $title,
                    'description' => $description,
                    'waste_type' => $waste_type,
                    'point_category' => $point_category,
                    'bin_color' => $bin_color,
                    'image' => $image_path
                ]);
                header("Location: admin_edukasi.php?msg=Materi+edukasi+berhasil+ditambahkan!&msg_type=success");
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menambahkan materi edukasi: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['edit_education'])) {
        $id = (int)$_POST['id'];
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $waste_type = sanitize($_POST['waste_type'] ?? '');
        $point_category = sanitize($_POST['point_category'] ?? '');
        $bin_color = sanitize($_POST['bin_color'] ?? '');
        $current_image = $_POST['current_image'] ?? null;
        
        $image_path = $current_image;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $new_image = upload_education_image($_FILES['image']);
            if ($new_image) {
                $image_path = $new_image;
                // Delete old image if exists
                if ($current_image && file_exists($current_image)) {
                    unlink($current_image);
                }
            }
        }

        if (empty($title) || empty($waste_type) || empty($bin_color)) {
            $error = 'Judul Materi, Jenis Sampah, dan Warna Tempat Sampah wajib diisi!';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE education 
                    SET title = :title, description = :description, waste_type = :waste_type, point_category = :point_category, bin_color = :bin_color, image_path = :image 
                    WHERE id = :id
                ");
                $stmt->execute([
                    'title' => $title,
                    'description' => $description,
                    'waste_type' => $waste_type,
                    'point_category' => $point_category,
                    'bin_color' => $bin_color,
                    'image' => $image_path,
                    'id' => $id
                ]);
                header("Location: admin_edukasi.php?msg=Materi+edukasi+berhasil+diperbarui!&msg_type=success");
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui materi edukasi: ' . $e->getMessage();
            }
        }
    }
}

// 2. Handle Delete Education
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    try {
        // Get image path first to delete file
        $stmt = $pdo->prepare("SELECT image_path FROM education WHERE id = ?");
        $stmt->execute([$delete_id]);
        $img = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("DELETE FROM education WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        if ($img && file_exists($img)) {
            unlink($img);
        }
        
        header("Location: admin_edukasi.php?msg=Materi+edukasi+berhasil+dihapus!&msg_type=success");
        exit;
    } catch (PDOException $e) {
        $error = 'Gagal menghapus materi edukasi: ' . $e->getMessage();
    }
}

// 3. Edit mode load
$edit_mode = false;
$edit_ed = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM education WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_ed = $stmt->fetch();
    if ($edit_ed) {
        $edit_mode = true;
    }
}

// 4. Fetch all Education materials
try {
    $search_query = "";
    $params = [];
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . sanitize($_GET['search']) . '%';
        $search_query = " WHERE title LIKE :search1 OR waste_type LIKE :search2 OR bin_color LIKE :search3";
        $params['search1'] = $search;
        $params['search2'] = $search;
        $params['search3'] = $search;
    }

    $stmt = $pdo->prepare("SELECT * FROM education $search_query ORDER BY id ASC");
    $stmt->execute($params);
    $ed_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $ed_list = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title animate-fade-in">
    <h1>Manajemen Edukasi Sampah</h1>
    <p>Kelola artikel edukasi jenis sampah, klasifikasi poin, serta instruksi pemilahan wadah tempat sampah bagi nasabah.</p>
</div>

<?php if ($error): ?>
    <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.2); color: var(--danger); padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 13px;">
        <i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i><?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="dashboard-sections">
    
    <!-- Table List of Education -->
    <div class="glass-card">
        <div class="section-header" style="flex-wrap: wrap; gap: 15px; margin-bottom: 24px;">
            <h2 class="section-title"><i class="fa-solid fa-graduation-cap" style="margin-right: 8px;"></i>Daftar Materi Edukasi</h2>
            
            <form action="admin_edukasi.php" method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 250px; margin-left: auto;">
                <input type="text" name="search" id="table-search" class="form-control" style="padding: 6px 12px; font-size: 12px;" placeholder="Cari judul/warna..." value="<?php echo isset($_GET['search']) ? sanitize($_GET['search']) : ''; ?>">
                <?php if (isset($_GET['search'])): ?>
                    <a href="admin_edukasi.php" class="btn btn-secondary btn-sm" style="padding: 6px 10px;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Foto</th>
                        <th>Judul Materi</th>
                        <th>Jenis Sampah</th>
                        <th>Kategori Poin</th>
                        <th>Warna Tempat Sampah</th>
                        <th style="text-align: right; width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ed_list)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                Data materi edukasi kosong.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        foreach ($ed_list as $e): 
                            // Bin color rendering
                            $color_code = '#6c757d';
                            $text_color = '#ffffff';
                            $color_name = strtolower($e['bin_color']);
                            if ($color_name === 'hijau') $color_code = '#198754';
                            elseif ($color_name === 'kuning') { $color_code = '#ffc107'; $text_color = '#000000'; }
                            elseif ($color_name === 'biru') $color_code = '#0d6efd';
                            elseif ($color_name === 'merah') $color_code = '#dc3545';
                            elseif ($color_name === 'abu-abu') $color_code = '#6c757d';
                            
                            // Image display
                            $img_src = 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?w=100&auto=format&fit=crop&q=60'; // default placeholder
                            if ($e['image_path'] && file_exists($e['image_path'])) {
                                $img_src = $e['image_path'];
                            }
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <div style="width: 48px; height: 48px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color); background: var(--bg-main);">
                                        <img src="<?php echo $img_src; ?>" alt="<?php echo sanitize($e['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--primary);"><?php echo sanitize($e['title']); ?></div>
                                    <div style="font-size: 11px; color: var(--text-secondary); max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo sanitize($e['description']); ?></div>
                                </td>
                                <td style="font-weight: 600;"><?php echo sanitize($e['waste_type']); ?></td>
                                <td style="font-weight: 600; color: #f59e0b;"><?php echo sanitize($e['point_category']); ?></td>
                                <td>
                                    <span class="badge" style="background: <?php echo $color_code; ?>; color: <?php echo $text_color; ?>; padding: 4px 12px; font-weight: 600;">
                                        <i class="fa-solid fa-trash" style="margin-right: 4px;"></i><?php echo sanitize($e['bin_color']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="admin_edukasi.php?action=edit&id=<?php echo $e['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 5px 8px;" title="Edit">
                                        <i class="fa-solid fa-pen-to-square" style="color: var(--info);"></i>
                                    </a>
                                    <a href="admin_edukasi.php?action=delete&id=<?php echo $e['id']; ?>" class="btn btn-danger btn-sm" style="padding: 5px 8px;" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus materi edukasi ini?');">
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
            <h2 class="section-title" style="margin-bottom: 20px; color: var(--info);"><i class="fa-solid fa-pen-to-square" style="margin-right: 8px;"></i>Edit Materi</h2>
            
            <form action="admin_edukasi.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $edit_ed['id']; ?>">
                <input type="hidden" name="current_image" value="<?php echo $edit_ed['image_path']; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="edit_title">Judul Materi</label>
                    <input type="text" name="title" id="edit_title" class="form-control" value="<?php echo sanitize($edit_ed['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_type">Jenis Sampah</label>
                    <input type="text" name="waste_type" id="edit_type" class="form-control" value="<?php echo sanitize($edit_ed['waste_type']); ?>" placeholder="Misal: Organik, Plastik, Kertas..." required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_pts">Kategori Poin</label>
                    <input type="text" name="point_category" id="edit_pts" class="form-control" value="<?php echo sanitize($edit_ed['point_category']); ?>" placeholder="Misal: 50 Poin/Kg" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_bin">Warna Tempat Sampah</label>
                    <select name="bin_color" id="edit_bin" class="form-control" required>
                        <option value="Hijau" <?php echo $edit_ed['bin_color'] === 'Hijau' ? 'selected' : ''; ?>>Hijau (Sampah Organik)</option>
                        <option value="Kuning" <?php echo $edit_ed['bin_color'] === 'Kuning' ? 'selected' : ''; ?>>Kuning (Sampah Guna Ulang / Plastik)</option>
                        <option value="Biru" <?php echo $edit_ed['bin_color'] === 'Biru' ? 'selected' : ''; ?>>Biru (Kertas)</option>
                        <option value="Merah" <?php echo $edit_ed['bin_color'] === 'Merah' ? 'selected' : ''; ?>>Merah (Sampah B3 / Pecah Belah)</option>
                        <option value="Abu-abu" <?php echo $edit_ed['bin_color'] === 'Abu-abu' ? 'selected' : ''; ?>>Abu-abu (Residu)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_image">Ganti Gambar Ilustrasi (Ukuran maks: 2MB)</label>
                    <input type="file" name="image" id="edit_image" class="form-control" accept="image/*" style="padding: 6px 12px;">
                    <?php if ($edit_ed['image_path']): ?>
                        <div style="margin-top: 8px; font-size: 11px; color: var(--text-secondary);">Gambar saat ini: <code><?php echo basename($edit_ed['image_path']); ?></code></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="edit_desc">Deskripsi Pemilahan / Instruksi</label>
                    <textarea name="description" id="edit_desc" class="form-control" placeholder="Tulis instruksi lengkap..."><?php echo sanitize($edit_ed['description']); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="edit_education" class="btn btn-primary" style="flex: 1; justify-content: center;">
                        Simpan
                    </button>
                    <a href="admin_edukasi.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        <?php else: ?>
            <h2 class="section-title" style="margin-bottom: 20px;"><i class="fa-solid fa-circle-plus" style="margin-right: 8px;"></i>Tambah Materi Edukasi</h2>
            
            <form action="admin_edukasi.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_education" value="1">
                
                <div class="form-group">
                    <label class="form-label" for="add_title">Judul Materi</label>
                    <input type="text" name="title" id="add_title" class="form-control" placeholder="Contoh: Pengolahan Kompos dari Sampah Dapur" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="add_type">Jenis Sampah</label>
                    <input type="text" name="waste_type" id="add_type" class="form-control" placeholder="Contoh: Organik, Plastik, Kaca, dll" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="add_pts">Kategori Poin</label>
                    <input type="text" name="point_category" id="add_pts" class="form-control" placeholder="Contoh: 10 Poin/Kg atau 50 Poin/Kg" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="add_bin">Warna Tempat Sampah</label>
                    <select name="bin_color" id="add_bin" class="form-control" required>
                        <option value="Hijau" selected>Hijau (Sampah Organik)</option>
                        <option value="Kuning">Kuning (Sampah Guna Ulang / Plastik)</option>
                        <option value="Biru">Biru (Kertas)</option>
                        <option value="Merah">Merah (Sampah B3 / Pecah Belah)</option>
                        <option value="Abu-abu">Abu-abu (Residu)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="add_image">Upload Gambar Ilustrasi (Ukuran maks: 2MB)</label>
                    <input type="file" name="image" id="add_image" class="form-control" accept="image/*" style="padding: 6px 12px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="add_desc">Deskripsi Pemilahan / Instruksi</label>
                    <textarea name="description" id="add_desc" class="form-control" placeholder="Tulis rincian deskripsi materi edukasi sampah..."></textarea>
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
