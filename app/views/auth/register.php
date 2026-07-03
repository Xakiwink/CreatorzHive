<div class="auth-card">
  <h1 class="auth-title">Create account</h1>
  <p class="auth-subtitle">Start scheduling your content</p>

  <?php if (!empty($error)): ?>
    <div class="alert alert--error"><?= \App\Core\View::e($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="/?page=register" class="auth-form">
    <input type="hidden" name="_csrf" value="<?= \App\Core\View::e($csrf) ?>">

    <div class="form-group">
      <label for="name" class="form-label">Full name</label>
      <input type="text" id="name" name="name" class="form-input" required autofocus>
    </div>

    <div class="form-group">
      <label for="email" class="form-label">Email</label>
      <input type="email" id="email" name="email" class="form-input" required>
    </div>

    <div class="form-group">
      <label for="password" class="form-label">Password</label>
      <input type="password" id="password" name="password" class="form-input" required minlength="8">
      <span class="form-hint">At least 8 characters</span>
    </div>

    <button type="submit" class="btn btn--primary btn--full">Create account</button>
  </form>

  <p class="auth-switch">
    Already have an account? <a href="/?page=login">Sign in</a>
  </p>
</div>
