<?php
// includes/header.php
// Header & Navigation Layout for TPS3R Gang Tani Pringsewu Admin Portal

require_once __DIR__ . '/db.php';

if (!isset($active_page)) {
    $active_page = 'admin_dashboard';
}

$user_name = '';
$user_role = '';

if (is_logged_in()) {
    $user_name = $_SESSION['name'] ?? 'Admin';
    $user_role = $_SESSION['role'] ?? 'admin';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sistem Informasi Gamifikasi TPS3R'; ?></title>
    
    <!-- Meta SEO Tags -->
    <meta name="description" content="Sistem Informasi Gamifikasi TPS3R Gang Tani Pringsewu - Aplikasi modern pencatatan tabungan sampah dan reward poin.">
    <meta name="author" content="TPS3R Gang Tani Pringsewu">
    
    <!-- Font Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php if (is_logged_in() && $active_page !== 'login' && $active_page !== 'landing'): ?>
<div class="layout-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-logo"><i class="fa-solid fa-recycle text-dark"></i></div>
            <div class="brand-name">
                TPS3R Gang Tani
                <span>Pringsewu Barat</span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="menu-list">
                <!-- Dashboard -->
                <li class="menu-item <?php echo $active_page === 'admin_dashboard' ? 'active' : ''; ?>">
                    <a href="admin_dashboard.php">
                        <i class="fa-solid fa-chart-pie"></i>
                        Dashboard
                    </a>
                </li>
                <!-- Data Nasabah -->
                <li class="menu-item <?php echo $active_page === 'admin_warga' ? 'active' : ''; ?>">
                    <a href="admin_warga.php">
                        <i class="fa-solid fa-users"></i>
                        Data Nasabah
                    </a>
                </li>
                <!-- Data Sampah -->
                <li class="menu-item <?php echo $active_page === 'admin_sampah' ? 'active' : ''; ?>">
                    <a href="admin_sampah.php">
                        <i class="fa-solid fa-trash-can"></i>
                        Data Sampah
                    </a>
                </li>
                <!-- Reward -->
                <li class="menu-item <?php echo $active_page === 'admin_reward' ? 'active' : ''; ?>">
                    <a href="admin_reward.php">
                        <i class="fa-solid fa-gift"></i>
                        Reward
                    </a>
                </li>
                <!-- Edukasi Sampah -->
                <li class="menu-item <?php echo $active_page === 'admin_edukasi' ? 'active' : ''; ?>">
                    <a href="admin_edukasi.php">
                        <i class="fa-solid fa-book-open-reader"></i>
                        Edukasi Sampah
                    </a>
                </li>
                <!-- Leaderboard -->
                <li class="menu-item <?php echo $active_page === 'admin_leaderboard' ? 'active' : ''; ?>">
                    <a href="admin_leaderboard.php">
                        <i class="fa-solid fa-ranking-star"></i>
                        Leaderboard
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">
                    <i class="fa-solid fa-user-tie" style="font-size: 18px; color: var(--primary-light);"></i>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo sanitize($user_name); ?></div>
                    <div class="user-role">Online</div>
                </div>
            </div>
            <ul class="menu-list">
                <li class="menu-item">
                    <a href="logout.php" style="color: #fca5a5; padding-left: 16px;">
                        <i class="fa-solid fa-right-from-bracket" style="color: #fca5a5;"></i>
                        Keluar
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- Main Content Panel Wrapper -->
    <main class="main-content">
        <!-- Topbar Navbar -->
        <div class="topbar animate-fade-in">
            <div class="menu-toggle" id="menu-toggle-btn">
                <i class="fa-solid fa-bars"></i>
            </div>
            
            <!-- Navbar Search Bar -->
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="table-search" placeholder="Cari data di halaman ini...">
            </div>
            
            <!-- Admin Profile Header Indicator -->
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="text-align: right; line-height: 1.2;">
                        <div style="font-size: 13px; font-weight: 600; color: var(--text-main);"><?php echo sanitize($user_name); ?></div>
                        <span style="font-size: 10px; color: var(--success); font-weight: 500;"><i class="fa-solid fa-circle" style="font-size: 6px; margin-right: 4px;"></i>Aktif</span>
                    </div>
                </div>
            </div>
        </div>
<?php endif; ?>
