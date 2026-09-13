<?php
function asset_url($path) {
    // backward-compatible: keep function but use base_path() internally
    $base = base_path();
    return $base . '/assets/' . ltrim($path, '/');
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');

}

// Return application base path (e.g. '/worklog' or empty string for webroot)
function base_path() {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    return $scriptDir === '/' ? '' : $scriptDir;

}

// Build a site URL relative to the application base. Example: site_url('index.php')
function site_url($path = '') {
    $base = base_path();
    $path = ltrim($path, '/');
    return $base . ($path !== '' ? '/' . $path : '');
}

// Return an absolute filesystem path rooted at the application directory
function app_path($path = '') {
    $root = dirname(__DIR__);
    $path = ltrim($path, '/');
    return $root . ($path !== '' ? '/' . $path : '');
}

// Short wrapper for require_once relative to application root
function require_app($path) {
    $file = app_path($path);
    require_once $file;
}

// Session helpers
function ensure_session_started() {
    if (session_status() === PHP_SESSION_NONE) {
        // Cookie session aman: hanya bisa diakses via HTTP (bukan JS), same-site Lax
        // mencegah pembacaan cookie lewat XSS dan pengiriman silang antar situs.
        // Flag `secure` diaktifkan otomatis ketika aplikasi diakses via HTTPS.
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 0) == 443;
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function set_user_npp($npp, $nama_emp = null, $nama_bagian = null, $role_id = null) {
    ensure_session_started();
    $_SESSION['npp'] = $npp;
    if ($nama_emp !== null) $_SESSION['nama_emp'] = $nama_emp;
    if ($nama_bagian !== null) $_SESSION['nama_bagian'] = $nama_bagian;
    if ($role_id !== null) $_SESSION['role_id'] = $role_id;

    if (function_exists('session_regenerate_id')) session_regenerate_id(true);
}

function clear_user_session() {
    ensure_session_started();
    unset($_SESSION['npp'], $_SESSION['nama_emp'], $_SESSION['nama_bagian'], $_SESSION['role_id'], $_SESSION['role_name']);
    if (function_exists('session_regenerate_id')) session_regenerate_id(true);
}

function is_logged_in($key = 'npp') {
    ensure_session_started();
    return !empty($_SESSION[$key]);
}

/**
 * require_login(array $opts = [])
 * Redirects to login page if session key not present.
 * Options: 'key' => session key (default 'npp'), 'login' => login path, 'public' => array of public filenames
 */
function require_login(array $opts = []) {
    ensure_session_started();
    $key = $opts['key'] ?? 'npp';
    $login = $opts['login'] ?? site_url('login.php');
    $public = $opts['public'] ?? ['login.php', 'register.php', 'forgot.php'];

    $current = basename($_SERVER['PHP_SELF']);
    if (in_array($current, $public, true)) return;

    if (empty($_SESSION[$key])) {
        $next = urlencode($_SERVER['REQUEST_URI']);
        header('Location: ' . $login . ($next ? "?next={$next}" : ''));
        exit;
    }
}

// SweetAlert flash helpers
function flash_swal($icon, $title, $text = '', array $options = []) {
    ensure_session_started();
    $_SESSION['flash_swal'] = [
        'icon' => $icon,
        'title' => $title,
        'text' => $text,
        'options' => $options,
    ];
}

function pull_flash_swal() {
    ensure_session_started();
    if (isset($_SESSION['flash_swal'])) {
        $data = $_SESSION['flash_swal'];
        unset($_SESSION['flash_swal']);
        return $data;
    }
    return null;
}

function render_flash_swal() {
    $data = pull_flash_swal();
    if (empty($data)) return;
    $payload = array_merge([
        'icon' => $data['icon'] ?? '',
        'title' => $data['title'] ?? '',
        'text' => $data['text'] ?? '',
    ], $data['options'] ?? []);
    $json = json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    echo "<script>document.addEventListener('DOMContentLoaded', function(){ if(window.Swal){ Swal.fire($json); } else { console.warn('SweetAlert not loaded'); } });</script>";
}

// Format a date string YYYY-MM-DD to "YYYY MonthName DD" in Indonesian
function format_date_id($yyyyMmDd)
{
    if (empty($yyyyMmDd)) return '-';
    $ts = strtotime($yyyyMmDd);
    if ($ts === false) return $yyyyMmDd;
    $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $year = date('Y', $ts);
    $month = (int)date('n', $ts);
    $day = date('j', $ts);
    $mname = $months[$month] ?? date('F', $ts);
    return $year . ' ' . $mname . ' ' . $day;
}

// Format a datetime string to a readable Indonesian datetime (e.g. "15 Feb 2026 14:30")
function format_datetime_id($dateTimeStr)
{
    if (empty($dateTimeStr)) return '-';
    $ts = strtotime($dateTimeStr);
    if ($ts === false) return $dateTimeStr;
    // short month names in Indonesian
    $months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
    $day = date('d', $ts);
    $month = (int)date('n', $ts);
    $year = date('Y', $ts);
    $time = date('H:i', $ts);
    $mname = $months[$month] ?? date('M', $ts);
    return $day . ' ' . $mname . ' ' . $year . ' ' . $time;
}
