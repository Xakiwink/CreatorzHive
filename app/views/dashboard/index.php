<div class="page-header">
  <h1 class="page-title">Dashboard</h1>
  <a href="/?page=create-post" class="btn btn--primary">+ New Post</a>
</div>

<?php if (!empty($success)): ?>
  <div class="alert alert--success"><?= \App\Core\View::e($success) ?></div>
<?php endif; ?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-value"><?= (int)($counts['total'] ?? 0) ?></div>
    <div class="stat-label">Total Posts</div>
  </div>
  <div class="stat-card">
    <div class="stat-value"><?= (int)($counts['scheduled'] ?? 0) ?></div>
    <div class="stat-label">Scheduled</div>
  </div>
  <div class="stat-card">
    <div class="stat-value"><?= (int)($counts['published'] ?? 0) ?></div>
    <div class="stat-label">Published</div>
  </div>
  <div class="stat-card">
    <div class="stat-value"><?= count($accounts ?? []) ?></div>
    <div class="stat-label">Connected Accounts</div>
  </div>
</div>

<div class="section">
  <div class="section-header">
    <h2 class="section-title">Connected Accounts</h2>
    <a href="/?page=settings" class="btn btn--secondary btn--sm">Manage</a>
  </div>
  <?php if (empty($accounts)): ?>
    <div class="empty-state">
      <p>No social accounts connected yet.</p>
      <a href="/?page=settings" class="btn btn--primary">Connect Instagram</a>
    </div>
  <?php else: ?>
    <div class="accounts-list">
      <?php foreach ($accounts as $account): ?>
        <div class="account-item">
          <span class="account-platform"><?= \App\Core\View::e(ucfirst($account['platform'])) ?></span>
          <span class="account-username">@<?= \App\Core\View::e($account['username'] ?? '') ?></span>
          <span class="account-status account-status--connected">Connected</span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="section">
  <div class="section-header">
    <h2 class="section-title">Recent Posts</h2>
    <a href="/?page=posts" class="btn btn--secondary btn--sm">View all</a>
  </div>
  <?php if (empty($recentPosts)): ?>
    <div class="empty-state">
      <p>No posts yet.</p>
      <a href="/?page=create-post" class="btn btn--primary">Create your first post</a>
    </div>
  <?php else: ?>
    <div class="posts-list">
      <?php foreach ($recentPosts as $post): ?>
        <div class="post-item">
          <div class="post-info">
            <span class="post-title"><?= \App\Core\View::e($post['title']) ?></span>
            <span class="post-platforms"><?= \App\Core\View::e(implode(', ', $post['platforms'] ?? [])) ?></span>
          </div>
          <div class="post-meta">
            <span class="post-status post-status--<?= \App\Core\View::e($post['status']) ?>"><?= \App\Core\View::e(ucfirst($post['status'])) ?></span>
            <?php if (!empty($post['scheduled_at'])): ?>
              <span class="post-date"><?= \App\Core\View::e(date('M j, Y g:i A', strtotime($post['scheduled_at']))) ?></span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
