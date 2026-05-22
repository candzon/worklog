<?php
/**
 * profile.php (Controller)
 * Halaman Profil Pengguna
 */
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';

ensure_session_started();
require_login();

$npp = $_SESSION['npp'] ?? null;

if (!$npp) {
    header('Location: login.php');
    exit;
}

// Fetch user data
$user = null;
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT * FROM employee WHERE npp = ? LIMIT 1");
    $stmt->bind_param('s', $npp);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$user) {
    die('Data pengguna tidak ditemukan.');
}

// Render Tampilan
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Masukkan file View Profile
require_once __DIR__ . '/views/profile.php';

require_once __DIR__ . '/includes/footer.php';
