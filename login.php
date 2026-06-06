<?php
// login.php
// Login Page for TPS3R Gang Tani Pringsewu (Admin & Nasabah/Warga)

$active_page = 'login';
$page_title = 'Masuk ke TPS3R Gang Tani';
require_once __DIR__ . '/includes/db.php';

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: warga_dashboard.php");
    }
    exit;
}

$error = '';
$active_tab = 'warga'; // Default active tab

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = sanitize($_POST['role'] ?? 'warga');
    $active_tab = $role; // Persist tab on submission error

    if ($role === 'admin') {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username dan password wajib diisi!';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Start session & save info
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = 'admin';
                    $_SESSION['name'] = $user['name'];
                    
                    header("Location: admin_dashboard.php?msg=Selamat+datang+kembali,+Admin!&msg_type=success");
                    exit;
                } else {
                    $error = 'Username atau password admin salah!';
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    } else {
        // Warga/Nasabah login
        $identifier = sanitize($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $error = 'Username/NIK dan password wajib diisi!';
        } else {
            try {
                // Search by username OR nik
                $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR nik = ?) AND role = 'warga'");
                $stmt->execute([$identifier, $identifier]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Start session & save info
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = 'warga';
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['username'] = $user['username'];

                    header("Location: warga_dashboard.php?msg=Selamat+datang+kembali,+Nasabah!&msg_type=success");
                    exit;
                } else {
                    $error = 'Username/NIK atau password nasabah salah!';
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background-color: var(--bg-main);">
    <div class="glass-card animate-fade-in" style="max-width: 440px; width: 100%; padding: 40px; border-radius: 24px; box-shadow: var(--shadow-soft);">
        <!-- Logo & Branding -->
        <div style="text-align: center; margin-bottom: 30px;">
            <div class="brand-logo" style="display: inline-flex; margin-bottom: 12px; background-color: var(--primary-light); color: var(--primary); transform: scale(1.15);">
                <i class="fa-solid fa-recycle"></i>
            </div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin-top: 8px;">TPS3R Gang Tani</h2>
            <p style="color: var(--text-secondary); font-size: 12px; margin-top: 2px;">Sistem Informasi & Gamifikasi Sampah</p>
        </div>

        <?php if ($error): ?>
            <div style="background: rgba(220, 53, 69, 0.08); border: 1px solid rgba(220, 53, 69, 0.15); color: var(--danger); padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 13px; text-align: center;">
                <i class="fa-solid fa-triangle-exclamation" style="margin-right: 6px;"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Role Tabs -->
        <div style="display: flex; background: var(--bg-main); border-radius: 12px; padding: 4px; margin-bottom: 24px; border: 1px solid var(--border-color);">
            <button type="button" onclick="switchTab('warga')" id="tab-warga" class="btn" style="flex: 1; padding: 10px; border-radius: 8px; justify-content: center; font-size: 13px; background: <?php echo $active_tab === 'warga' ? 'var(--surface)' : 'transparent'; ?>; color: <?php echo $active_tab === 'warga' ? 'var(--primary)' : 'var(--text-secondary)'; ?>; border-color: <?php echo $active_tab === 'warga' ? 'var(--border-color)' : 'transparent'; ?>; font-weight: 600;">
                <i class="fa-solid fa-user-tag" style="margin-right: 6px;"></i> Nasabah
            </button>
            <button type="button" onclick="switchTab('admin')" id="tab-admin" class="btn" style="flex: 1; padding: 10px; border-radius: 8px; justify-content: center; font-size: 13px; background: <?php echo $active_tab === 'admin' ? 'var(--surface)' : 'transparent'; ?>; color: <?php echo $active_tab === 'admin' ? 'var(--primary)' : 'var(--text-secondary)'; ?>; border-color: <?php echo $active_tab === 'admin' ? 'var(--border-color)' : 'transparent'; ?>; font-weight: 600;">
                <i class="fa-solid fa-user-shield" style="margin-right: 6px;"></i> Petugas Admin
            </button>
        </div>

        <!-- Forms Container -->
        <div>
            <!-- Form Warga -->
            <form action="login.php" method="POST" id="form-warga" style="display: <?php echo $active_tab === 'warga' ? 'block' : 'none'; ?>;">
                <input type="hidden" name="role" value="warga">
                
                <div class="form-group">
                    <label class="form-label" for="identifier">Username / NIK Nasabah</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <i class="fa-solid fa-user-large" style="position: absolute; left: 14px; color: var(--text-muted); font-size: 13px;"></i>
                        <input type="text" name="identifier" id="identifier" class="form-control" style="padding-left: 36px;" placeholder="Masukkan Username / NIK" required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="password_warga">Kata Sandi</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <i class="fa-solid fa-lock" style="position: absolute; left: 14px; color: var(--text-muted); font-size: 13px;"></i>
                        <input type="password" name="password" id="password_warga" class="form-control" style="padding-left: 36px;" placeholder="Masukkan kata sandi" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; border-radius: var(--radius-md);">
                    <i class="fa-solid fa-right-to-bracket" style="margin-right: 6px;"></i> Masuk sebagai Nasabah
                </button>
            </form>

            <!-- Form Admin -->
            <form action="login.php" method="POST" id="form-admin" style="display: <?php echo $active_tab === 'admin' ? 'block' : 'none'; ?>;">
                <input type="hidden" name="role" value="admin">
                
                <div class="form-group">
                    <label class="form-label" for="username">Username Admin</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <i class="fa-solid fa-user-lock" style="position: absolute; left: 14px; color: var(--text-muted); font-size: 13px;"></i>
                        <input type="text" name="username" id="username" class="form-control" style="padding-left: 36px;" placeholder="Contoh: admin" required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="password_admin">Kata Sandi</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <i class="fa-solid fa-lock" style="position: absolute; left: 14px; color: var(--text-muted); font-size: 13px;"></i>
                        <input type="password" name="password" id="password_admin" class="form-control" style="padding-left: 36px;" placeholder="Masukkan kata sandi" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; border-radius: var(--radius-md); background: var(--primary);">
                    <i class="fa-solid fa-shield-halved" style="margin-right: 6px;"></i> Masuk sebagai Admin
                </button>
            </form>
        </div>

        <div style="margin-top: 24px; text-align: center; font-size: 13px;">
            <a href="index.php" style="color: var(--primary); font-weight: 600;"><i class="fa-solid fa-arrow-left" style="margin-right: 6px;"></i>Kembali ke Portal Utama</a>
        </div>
    </div>
</div>

<script>
function switchTab(role) {
    const tabWarga = document.getElementById('tab-warga');
    const tabAdmin = document.getElementById('tab-admin');
    const formWarga = document.getElementById('form-warga');
    const formAdmin = document.getElementById('form-admin');

    if (role === 'warga') {
        tabWarga.style.background = 'var(--surface)';
        tabWarga.style.color = 'var(--primary)';
        tabWarga.style.borderColor = 'var(--border-color)';
        
        tabAdmin.style.background = 'transparent';
        tabAdmin.style.color = 'var(--text-secondary)';
        tabAdmin.style.borderColor = 'transparent';

        formWarga.style.display = 'block';
        formAdmin.style.display = 'none';
        
        document.getElementById('identifier').focus();
    } else {
        tabAdmin.style.background = 'var(--surface)';
        tabAdmin.style.color = 'var(--primary)';
        tabAdmin.style.borderColor = 'var(--border-color)';
        
        tabWarga.style.background = 'transparent';
        tabWarga.style.color = 'var(--text-secondary)';
        tabWarga.style.borderColor = 'transparent';

        formAdmin.style.display = 'block';
        formWarga.style.display = 'none';

        document.getElementById('username').focus();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
