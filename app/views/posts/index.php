<div class="page-header">
  <h1 class="page-title">Posts</h1>
  <a href="/?page=create-post" class="btn btn--primary">+ New Post</a>
</div>

<?php if (!empty($success)): ?>
  <div class="alert alert--success"><?= \App\Core\View::e($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="alert alert--error"><?= \App\Core\View::e($error) ?></div>
<?php endif; ?>

<?php if (empty($posts)): ?>
  <div class="empty-state">
    <h2>No posts yet</h2>
    <p>Schedule your first post to get started.</p>
    <a href="/?page=create-post" class="btn btn--primary">Create post</a>
  </div>
<?php else: ?>
  <div class="posts-table-wrap">
    <table class="posts-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Platforms</th>
          <th>Status</th>
          <th>Scheduled</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($posts as $post): ?>
          <tr>
            <td class="post-title-cell">
              <span class="post-title"><?= \App\Core\View::e($post['title']) ?></span>
              <span class="post-caption-preview"><?= \App\Core\View::e(mb_strimwidth($post['caption'] ?? '', 0, 60, '…')) ?></span>
            </td>
            <td><?= \App\Core\View::e(implode(', ', array_map('ucfirst', $post['platforms'] ?? []))) ?></td>
            <td>
              <span class="badge badge--<?= \App\Core\View::e($post['status']) ?>">
                <?= \App\Core\View::e(ucfirst($post['status'])) ?>
              </span>
            </td>
            <td>
              <?php if (!empty($post['scheduled_at'])): ?>
                <?= \App\Core\View::e(date('M j, Y g:i A', strtotime($post['scheduled_at']))) ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
