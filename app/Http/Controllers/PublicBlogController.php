<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicBlogController extends Controller
{
    public function index(Request $request): View
    {
        $posts = BlogPost::query()
            ->with('createdBy')
            ->published()
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(function ($builder) use ($request): void {
                    $search = trim((string) $request->string('search'));
                    $builder
                        ->where('title', 'like', '%' . $search . '%')
                        ->orWhere('excerpt', 'like', '%' . $search . '%')
                        ->orWhere('content', 'like', '%' . $search . '%');
                })
            )
            ->latest('published_at')
            ->latest('id')
            ->paginate(9)
            ->withQueryString();

        return view('public.blog-index', [
            'posts' => $posts,
        ]);
    }

    public function show(BlogPost $blogPost): View
    {
        abort_unless(
            $blogPost->status && (! $blogPost->published_at || $blogPost->published_at->lte(now())),
            404
        );

        $blogPost->loadMissing('createdBy');

        $relatedPosts = BlogPost::query()
            ->with('createdBy')
            ->published()
            ->whereKeyNot($blogPost->id)
            ->latest('published_at')
            ->latest('id')
            ->take(3)
            ->get();

        return view('public.blog-show', [
            'blogPost' => $blogPost,
            'relatedPosts' => $relatedPosts,
        ]);
    }
}
