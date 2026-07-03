<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= \App\Core\View::e($title ?? 'CreatorzHive') ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="icon" type="image/svg+xml" href="/assets/images/icon.svg">
</head>
<body class="auth-body">
<div class="auth-container">
  <div class="auth-logo">
    <a href="/?page=login">CreatorzHive</a>
  </div>
  <?= $content ?>
</div>
</body>
</html>
