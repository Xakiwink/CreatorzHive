<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, Session, View};
use App\Models\{Post, JobQueue};

class PostController
{
    public function index(): void
    {
        Session::requireAuth();
        $user = Session::user();

        View::render('posts/index', [
            'user'    => $user,
            'posts'   => Post::forUser((int) $user['id']),
            'success' => Session::getFlash('success'),
            'error'   => Session::getFlash('error'),
        ]);
    }

    public function createPage(): void
    {
        Session::requireAuth();
        View::render('posts/create', [
            'user'  => Session::user(),
            'error' => Session::getFlash('error'),
        ]);
    }

    public function create(): void
    {
        Session::requireAuth();
        Request::verifyCsrf();

        $user     = Session::user();
        $userId   = (int) $user['id'];
        $title    = Request::post('title');
        $caption  = Request::post('caption');
        $schedAt  = Request::post('scheduled_at');
        $platforms = Request::postArray('platforms');

        if ($title === '' || $caption === '') {
            Session::flash('error', 'Title and caption are required.');
            header('Location: /?page=create-post'); exit;
        }

        $scheduledAt = ($schedAt !== '') ? $schedAt : null;
        $postId = Post::create($userId, $title, $caption, $platforms, $scheduledAt);

        if ($scheduledAt !== null && !empty($platforms)) {
            $delay = max(0, strtotime($scheduledAt) - time());
            JobQueue::dispatch('publish_post', ['post_id' => $postId], $delay);
        }

        Session::flash('success', $scheduledAt !== null ? 'Post scheduled successfully.' : 'Draft saved.');
        header('Location: /?page=posts'); exit;
    }
}
