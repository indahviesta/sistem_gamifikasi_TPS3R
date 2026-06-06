<?php
// logout.php
// Destroy session and redirect to login page

session_start();
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: login.php?msg=Anda+telah+berhasil+keluar&msg_type=success");
exit;
?>
