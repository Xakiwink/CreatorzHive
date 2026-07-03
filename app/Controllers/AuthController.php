<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, Session, View};
use App\Models\User;

class AuthController
{
    public function loginPage(): void
    {
        if (Session::isLoggedIn()) {
            header('Location: /?page=dashboard'); exit;
        }
        View::render('auth/login', ['error' => Session::getFlash('error')], 'auth');
    }

    public function login(): void
    {
        Request::verifyCsrf();

        $email    = Request::post('email');
        $password = Request::post('password');
        $user     = User::findByEmail($email);

        if ($user === null || !User::verify($password, (string) $user['password'])) {
            Session::flash('error', 'Invalid email or password.');
            header('Location: /?page=login'); exit;
        }

        Session::setUser(User::safe($user));
        header('Location: /?page=dashboard'); exit;
    }

    public function registerPage(): void
    {
        if (Session::isLoggedIn()) {
            header('Location: /?page=dashboard'); exit;
        }
        View::render('auth/register', ['error' => Session::getFlash('error')], 'auth');
    }

    public function register(): void
    {
        Request::verifyCsrf();

        $name     = Request::post('name');
        $email    = Request::post('email');
        $password = Request::post('password');

        if ($name === '' || $email === '' || $password === '') {
            Session::flash('error', 'All fields are required.');
            header('Location: /?page=register'); exit;
        }

        if (strlen($password) < 8) {
            Session::flash('error', 'Password must be at least 8 characters.');
            header('Location: /?page=register'); exit;
        }

        if (User::findByEmail($email) !== null) {
            Session::flash('error', 'An account with that email already exists.');
            header('Location: /?page=register'); exit;
        }

        $id   = User::create($name, $email, $password);
        $user = User::findById($id);
        Session::setUser(User::safe($user));
        header('Location: /?page=dashboard'); exit;
    }

    public function logout(): void
    {
        Session::destroy();
        header('Location: /?page=login'); exit;
    }
}
