<?php
// Standalone login page (no global header/footer)
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';

    $errors = [];
    $npp = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $npp = trim($_POST['npp'] ?? '');
      $password = $_POST['password'] ?? '';

      if ($npp === '') $errors[] = 'NPP Wajib diisi';
      if ($password === '') $errors[] = 'Password Wajib diisi';

      if (empty($errors)) {
        if (isset($conn)) {
          $stmt = $conn->prepare("SELECT npp, password, nama_emp, nama_bagian FROM employee WHERE npp = ? LIMIT 1");
          if ($stmt) {
            $stmt->bind_param('s', $npp);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc() ?? null;
            if ($user && ( (function_exists('password_verify') && password_verify($password, $user['password'])) || $user['password'] === $password )) {
              set_user_npp($user['npp'], $user['nama_emp'], $user['nama_bagian']);
              // enqueue a SweetAlert success message for the next page
              if (function_exists('flash_swal')) {
                flash_swal('success', 'Login berhasil', 'Selamat datang, ' . $user['nama_emp']);
              }
              $redirect = !empty($_GET['next']) ? $_GET['next'] : site_url('index.php');
              header('Location: ' . $redirect);
              exit;
            }
          }
        }
        $errors[] = 'NPP atau password salah';
      }
    }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — Worklog</title>
  <link rel="stylesheet" href="<?php echo asset_url('css/adminlte.css'); ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <style>body{background:#f7f8fa} .login-card{max-width:420px;width:100%}</style>
</head>
<body>

  <div class="d-flex align-items-center justify-content-center" style="min-height:100vh; padding:24px;">
    <div class="card shadow login-card">
      <div class="card-body">
        <div class="text-center mb-3">
          <h2 class="mb-0">Worklog</h2>
          <p class="text-muted small">Sistem Pelaporan Pekerjaan</p>
        </div>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $err) echo '<li>' . htmlspecialchars($err) . '</li>'; ?></ul>
          </div>
          <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
          <script>
            document.addEventListener('DOMContentLoaded', function(){
              if (window.Swal) {
                Swal.fire({
                  icon: 'error',
                  title: 'Login gagal',
                  html: `<?php echo implode("<br>", array_map('htmlspecialchars', $errors)); ?>`
                });
              }
            });
          </script>
        <?php endif; ?>

        <form method="post" action="<?php echo site_url('login.php'); ?>" novalidate>
          <div class="mb-3">
            <label class="form-label">NPP</label>
            <div class="input-group">
              <input type="text" name="npp" class="form-control" placeholder="Nomor Pegawai (NPP)" value="<?php echo htmlspecialchars($npp ?? ''); ?>" required autofocus>
              <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
              <input type="password" name="password" class="form-control" placeholder="Password" required>
              <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            </div>
          </div>

          <div class="mb-3">
            <!-- no remember / forgot password on NPP login -->
          </div>

          <div class="d-grid">
            <button class="btn btn-primary btn-lg" type="submit">Sign In</button>
          </div>
        </form>
      </div>
      <div class="card-footer text-center small text-muted">&copy; <?php echo date('Y'); ?> Worklog</div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="<?php echo asset_url('js/adminlte.js'); ?>"></script>
<?php
// Render any queued SweetAlert flash message (e.g., logout message)
if (function_exists('render_flash_swal')) {
    render_flash_swal();
}
?>
</body>
</html>
