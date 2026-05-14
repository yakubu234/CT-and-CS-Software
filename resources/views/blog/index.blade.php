@extends('layouts.admin')

@section('title', 'Blog')
@section('page_title', 'Blog')

@push('styles')
    <style>
        .blog-thumb {
            width: 72px;
            height: 56px;
            object-fit: cover;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }
    </style>
@endpush

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Blog Posts',
            'subtitle' => 'Create, manage, and publish blog content for the public website.',
            'action' => route('blog.index'),
            'placeholder' => 'Search blog posts',
        ])

        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('blog.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Add Blog Post
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th style="width: 90px;">Image</th>
                            <th>Post</th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 150px;">Published</th>
                            <th style="width: 130px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($posts as $post)
                            <tr>
                                <td>
                                    <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="blog-thumb">
                                </td>
                                <td>
                                    <div class="font-weight-semibold">{{ $post->title }}</div>
                                    <small class="text-muted d-block">{{ $post->slug }}</small>
                                    <small class="text-muted d-block mt-1">{{ $post->excerpt_text }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $post->status ? 'success' : 'secondary' }}">
                                        {{ $post->status ? 'Published' : 'Draft' }}
                                    </span>
                                </td>
                                <td>{{ $post->published_at?->format('d M Y h:i A') ?? 'Not scheduled' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('blog.edit', $post) }}" class="btn btn-sm btn-outline-primary mb-1">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('blog.destroy', $post) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this blog post?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger mb-1">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No blog posts found yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $posts->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
