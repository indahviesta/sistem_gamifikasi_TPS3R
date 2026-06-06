<?php
// includes/db.php
// Database connection and core helper functions for TPS3R Gang Tani Pringsewu

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'gamifikasi_sampah';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If database connection fails, redirect to setup page if we're not already on it
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($currentPage !== 'setup.php') {
        header("Location: setup.php");
        exit;
    } else {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

// Global Helper Functions

/**
 * Clean user input
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is warga/nasabah
 */
function is_warga() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'warga';
}

/**
 * Require login helper
 */
function require_login($role = null) {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
    
    if ($role && $_SESSION['role'] !== $role) {
        header("Location: index.php");
        exit;
    }
}

/**
 * Get citizen total points (active balance)
 */
function get_warga_points($pdo, $warga_id) {
    $stmt = $pdo->prepare("SELECT poin FROM users WHERE id = ?");
    $stmt->execute([$warga_id]);
    return (int)$stmt->fetchColumn();
}

/**
 * Get citizen total weight of waste deposited
 */
function get_warga_weight($pdo, $warga_id) {
    $stmt = $pdo->prepare("SELECT SUM(weight) FROM transactions WHERE warga_id = ?");
    $stmt->execute([$warga_id]);
    return (float)$stmt->fetchColumn();
}

/**
 * Format date in Indonesian style
 */
function format_date_id($dateStr) {
    if (!$dateStr) return '';
    $timestamp = strtotime($dateStr);
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $day = $days[date('w', $timestamp)];
    $d = date('j', $timestamp);
    $m = $months[(int)date('n', $timestamp)];
    $y = date('Y', $timestamp);
    $time = date('H:i', $timestamp);
    
    return "$d $m $y, $time";
}

/**
 * Get badge information based on accumulated points
 */
function get_badge($points_accumulated) {
    if ($points_accumulated >= 1000) {
        return [
            'name' => 'Eco Champion',
            'badge' => '🥇',
            'class' => 'badge-eco-champion',
            'color' => '#d97706',
            'bg' => '#fef3c7'
        ];
    } elseif ($points_accumulated >= 500) {
        return [
            'name' => 'Green Hero',
            'badge' => '🥈',
            'class' => 'badge-green-hero',
            'color' => '#15803d',
            'bg' => '#dcfce7'
        ];
    } elseif ($points_accumulated >= 200) {
        return [
            'name' => 'Waste Warrior',
            'badge' => '🥉',
            'class' => 'badge-waste-warrior',
            'color' => '#1d4ed8',
            'bg' => '#dbeafe'
        ];
    } else {
        return [
            'name' => 'Eco Starter',
            'badge' => '🌱',
            'class' => 'badge-eco-starter',
            'color' => '#7c3aed',
            'bg' => '#f3e8ff'
        ];
    }
}

/**
 * Sync points and cash balance for a specific citizen (nasabah) based on transactions and claims
 */
function sync_nasabah_balance($pdo, $warga_id) {
    try {
        // Calculate accumulated points (points_earned from transactions)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(points_earned), 0) FROM transactions WHERE warga_id = ?");
        $stmt->execute([$warga_id]);
        $poin_akumulasi = (int)$stmt->fetchColumn();

        // Calculate total points spent (points_spent from reward_claims)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(points_spent), 0) FROM reward_claims WHERE warga_id = ?");
        $stmt->execute([$warga_id]);
        $poin_spent = (int)$stmt->fetchColumn();

        // Calculate active point balance
        $poin_aktif = $poin_akumulasi - $poin_spent;
        if ($poin_aktif < 0) $poin_aktif = 0;

        // Calculate cash balance (cash_earned from transactions)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(cash_earned), 0) FROM transactions WHERE warga_id = ?");
        $stmt->execute([$warga_id]);
        $saldo_aktif = (int)$stmt->fetchColumn();

        // Update user record
        $stmt = $pdo->prepare("
            UPDATE users 
            SET poin_akumulasi = :poin_akumulasi, poin = :poin, saldo = :saldo 
            WHERE id = :id
        ");
        $stmt->execute([
            'poin_akumulasi' => $poin_akumulasi,
            'poin' => $poin_aktif,
            'saldo' => $saldo_aktif,
            'id' => $warga_id
        ]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>
