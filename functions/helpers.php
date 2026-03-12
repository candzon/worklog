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
