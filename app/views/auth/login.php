<div class="auth-card">
  <h1 class="auth-title">Welcome back</h1>
  <p class="auth-subtitle">Sign in to your account</p>

  <?php if (!empty($error)): ?>
    <div class="alert alert--error"><?= \App\Core\View::e($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="/?page=login" class="auth-form">
    <input type="hidden" name="_csrf" value="<?= \App\Core\View::e($csrf) ?>">

    <div class="form-group">
      <label for="email" class="form-label">Email</label>
      <input type="email" id="email" name="email" class="form-input" required autofocus>
    </div>

    <div class="form-group">
      <label for="password" class="form-label">Password</label>
      <input type="password" id="password" name="password" class="form-input" required>
    </div>

    <button type="submit" class="btn btn--primary btn--full">Sign in</button>
  </form>

  <p class="auth-switch">
    Don't have an account? <a href="/?page=register">Create one</a>
  </p>
</div>
