<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlogPostRequest;
use App\Http\Requests\UpdateBlogPostRequest;
use App\Models\BlogPost;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function __construct()
    {
        $this->middleware('module:blog');
    }

    public function index(Request $request): View
    {
        $posts = TableListing::paginate(
            TableListing::applySearch(
                BlogPost::query()
                    ->with('createdBy')
                    ->latest('published_at')
                    ->latest('id'),
                $request->string('search')->toString(),
                ['title', 'slug', 'excerpt', 'content']
            ),
            $request,
            10
        );

        return view('blog.index', [
            'posts' => $posts,
        ]);
    }

    public function create(): View
    {
        return view('blog.create', [
            'blogPost' => new BlogPost([
                'status' => true,
                'published_at' => now(),
            ]),
        ]);
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $data = $this->payloadFromRequest($request);

        $post = BlogPost::create([
            ...$data,
            'slug' => $this->uniqueSlug($data['slug'] ?: $data['title']),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('blog.index')
            ->with('status', "\"{$post->title}\" blog post created successfully.");
    }

    public function edit(BlogPost $blogPost): View
    {
        return view('blog.edit', [
            'blogPost' => $blogPost,
        ]);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $blogPost): RedirectResponse
    {
        $data = $this->payloadFromRequest($request, $blogPost);

        $blogPost->update([
            ...$data,
            'slug' => $this->uniqueSlug($data['slug'] ?: $data['title'], $blogPost->id),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('blog.index')
            ->with('status', "\"{$blogPost->title}\" blog post updated successfully.");
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $title = $blogPost->title;
        $this->deleteManagedImage($blogPost->featured_image);
        $blogPost->delete();

        return redirect()
            ->route('blog.index')
            ->with('status', "\"{$title}\" blog post deleted successfully.");
    }

    protected function payloadFromRequest(Request $request, ?BlogPost $blogPost = null): array
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            $this->deleteManagedImage($blogPost?->featured_image);
            $data['featured_image'] = $request->file('featured_image')->store('blogs/featured', 'public');
        } else {
            unset($data['featured_image']);
        }

        if (($data['status'] ?? false) && empty($data['published_at'])) {
            $data['published_at'] = $blogPost?->published_at ?? now();
        }

        return $data;
    }

    protected function deleteManagedImage(?string $path): void
    {
        if (! $path || Str::startsWith($path, ['frontend/', 'http://', 'https://'])) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    protected function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'blog-post';
        $slug = $base;
        $counter = 2;

        while (
            BlogPost::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
