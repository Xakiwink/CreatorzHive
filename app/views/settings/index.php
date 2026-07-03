<div class="page-header">
  <h1 class="page-title">Settings</h1>
</div>

<?php if (!empty($success)): ?>
  <div class="alert alert--success"><?= \App\Core\View::e($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="alert alert--error"><?= \App\Core\View::e($error) ?></div>
<?php endif; ?>

<div class="section">
  <h2 class="section-title">Connected Social Accounts</h2>

  <div class="account-card">
    <div class="account-card__header">
      <div class="account-card__info">
        <span class="account-card__platform">Instagram</span>
        <?php
          $ig = null;
          foreach ($accounts as $a) {
              if ($a['platform'] === 'instagram') { $ig = $a; break; }
          }
        ?>
        <?php if ($ig): ?>
          <span class="account-card__username">@<?= \App\Core\View::e($ig['username'] ?? '') ?></span>
        <?php else: ?>
          <span class="account-card__username text-muted">Not connected</span>
        <?php endif; ?>
      </div>
      <?php if ($ig): ?>
        <span class="badge badge--success">Connected</span>
      <?php else: ?>
        <a href="/?page=instagram-connect" class="btn btn--primary btn--sm">Connect</a>
      <?php endif; ?>
    </div>

    <?php if ($ig): ?>
      <div class="account-card__body">
        <div class="account-detail">
          <span class="account-detail__label">Display name</span>
          <span class="account-detail__value"><?= \App\Core\View::e($ig['display_name'] ?? '') ?></span>
        </div>
        <?php if (!empty($ig['token_expires_at'])): ?>
          <div class="account-detail">
            <span class="account-detail__label">Token expires</span>
            <span class="account-detail__value"><?= \App\Core\View::e(date('M j, Y', strtotime($ig['token_expires_at']))) ?></span>
          </div>
        <?php endif; ?>
        <div class="account-card__actions">
          <a href="/?page=instagram-connect" class="btn btn--secondary btn--sm">Reconnect</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="section">
  <h2 class="section-title">Account</h2>
  <div class="account-detail">
    <span class="account-detail__label">Name</span>
    <span class="account-detail__value"><?= \App\Core\View::e($user['name'] ?? '') ?></span>
  </div>
  <div class="account-detail">
    <span class="account-detail__label">Email</span>
    <span class="account-detail__value"><?= \App\Core\View::e($user['email'] ?? '') ?></span>
  </div>
</div>
