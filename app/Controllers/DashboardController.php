<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Session, View};
use App\Models\{Post, SocialAccount};

class DashboardController
{
    public function index(): void
    {
        Session::requireAuth();
        $user   = Session::user();
        $userId = (int) $user['id'];

        View::render('dashboard/index', [
            'user'        => $user,
            'counts'      => Post::counts($userId),
            'accounts'    => SocialAccount::forUser($userId),
            'recentPosts' => Post::forUser($userId, 5),
            'success'     => Session::getFlash('success'),
        ]);
    }
}
