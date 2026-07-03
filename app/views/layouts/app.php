<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= \App\Core\View::e($title ?? 'CreatorzHive') ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="icon" type="image/svg+xml" href="/assets/images/icon.svg">
</head>
<body>
<nav class="navbar">
  <a href="/?page=dashboard" class="navbar-brand">CreatorzHive</a>
  <div class="navbar-menu">
    <a href="/?page=dashboard" class="nav-link <?= (($_GET['page'] ?? '') === 'dashboard') ? 'active' : '' ?>">Dashboard</a>
    <a href="/?page=posts" class="nav-link <?= (($_GET['page'] ?? '') === 'posts') ? 'active' : '' ?>">Posts</a>
    <a href="/?page=settings" class="nav-link <?= (($_GET['page'] ?? '') === 'settings') ? 'active' : '' ?>">Settings</a>
    <a href="/?page=logout" class="nav-link nav-link--logout">Logout</a>
  </div>
</nav>
<main class="main-content">
  <?= $content ?>
</main>
</body>
</html>
