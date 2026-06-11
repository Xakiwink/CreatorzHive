<?php

declare(strict_types=1);

/**
 * Shared window globals for authenticated app pages.
 * Requires backend bootstrap (session, helpers) via http_view / index.php.
 */
?>
<script>
  window.__BASE_PATH__ = <?= json_encode(base_url_path()) ?>;
  window.__CSRF__ = <?= json_encode((string) session_get('_csrf_token', '')) ?>;
  window.__USER__ = <?= json_encode(frontend_session_user_payload()) ?>;
</script>
