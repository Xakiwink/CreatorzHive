<div class="page-header">
  <h1 class="page-title">Create Post</h1>
  <a href="/?page=posts" class="btn btn--secondary">Back to Posts</a>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert--error"><?= \App\Core\View::e($error) ?></div>
<?php endif; ?>

<div class="form-card">
  <form method="POST" action="/?page=create-post" class="post-form">
    <input type="hidden" name="_csrf" value="<?= \App\Core\View::e($csrf) ?>">

    <div class="form-group">
      <label for="title" class="form-label">Title <span class="form-required">*</span></label>
      <input type="text" id="title" name="title" class="form-input" required placeholder="My Instagram Post">
    </div>

    <div class="form-group">
      <label for="caption" class="form-label">Caption <span class="form-required">*</span></label>
      <textarea id="caption" name="caption" class="form-input form-textarea" rows="5" required
        placeholder="Write your caption here... #hashtags"></textarea>
    </div>

    <div class="form-group">
      <label class="form-label">Platforms</label>
      <div class="platform-checkboxes">
        <label class="checkbox-label">
          <input type="checkbox" name="platforms[]" value="instagram">
          <span>Instagram</span>
        </label>
      </div>
    </div>

    <div class="form-group">
      <label for="scheduled_at" class="form-label">Schedule for</label>
      <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-input">
      <span class="form-hint">Leave blank to save as draft.</span>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Save Post</button>
      <a href="/?page=posts" class="btn btn--ghost">Cancel</a>
    </div>
  </form>
</div>
