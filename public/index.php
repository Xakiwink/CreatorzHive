<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

require ROOT . '/vendor/autoload.php';

use App\Core\{Env, Session, View};

Env::load(ROOT . '/.env');
Session::start();
View::setBase(ROOT . '/app/views');

require ROOT . '/routes.php';

App\Core\Router::dispatch();
