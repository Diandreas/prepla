<?php

namespace App\Http\Controllers;

use App\Services\Blog\BlogService;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('blog.index', [
            'posts' => BlogService::posts(),
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogService::find($slug);
        abort_unless($post, 404);

        return view('blog.show', [
            'post' => $post,
            'others' => BlogService::others($slug),
        ]);
    }
}
