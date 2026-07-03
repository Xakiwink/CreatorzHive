<?php
declare(strict_types=1);

use App\Core\Router;
use App\Controllers\{AuthController, DashboardController, InstagramController, PostController};

$auth      = new AuthController();
$dashboard = new DashboardController();
$instagram = new InstagramController();
$post      = new PostController();

Router::get('',                   fn() => $auth->loginPage());
Router::get('login',              fn() => $auth->loginPage());
Router::post('login',             fn() => $auth->login());
Router::get('register',           fn() => $auth->registerPage());
Router::post('register',          fn() => $auth->register());
Router::get('logout',             fn() => $auth->logout());

Router::get('dashboard',          fn() => $dashboard->index());

Router::get('posts',              fn() => $post->index());
Router::get('create-post',        fn() => $post->createPage());
Router::post('create-post',       fn() => $post->create());

Router::get('settings',           fn() => $instagram->settingsPage());
Router::get('instagram-connect',  fn() => $instagram->connect());
Router::get('instagram-callback', fn() => $instagram->callback());
