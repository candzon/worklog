<?php
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/includes/header.php';
?>
<div class="login-box">
  <div class="login-logo"><a href="/"><b>Work</b>log</a></div>
  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Sign in to start your session</p>

      <form action="/login.php" method="post">
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email" required>
          <div class="input-group-text">
            <span class="bi bi-envelope"></span>
          </div>
        </div>

        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" required>
          <div class="input-group-text">
            <span class="bi bi-lock-fill"></span>
          </div>
        </div>

        <div class="row">
          <div class="col-8">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="remember" id="remember">
              <label class="form-check-label" for="remember"> Remember Me </label>
            </div>
          </div>

          <div class="col-4">
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary">Sign In</button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<?php
require_once __DIR__ . '/includes/footer.php';
?>
