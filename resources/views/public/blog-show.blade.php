@extends('public.layout')

@section('title', ($blogPost->meta_title ?: $blogPost->title) . ' | Oreoluwapo Ilaro Cooperative')
@section('meta_description', $blogPost->meta_description ?: $blogPost->excerpt_text)

@section('content')
    <section class="oreo-page-hero oreo-page-hero--article">
        <div class="container">
            <div class="witr_section_title white">
                <div class="witr_section_title_inner text-center">
                    <h2>Our Blog</h2>
                    <h3>{{ $blogPost->title }}</h3>
                    <p class="oreo-page-hero-copy">{{ strtoupper($blogPost->published_label) }} | {{ strtoupper($blogPost->createdBy?->name ?? 'ADMIN') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="oreo-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <article class="oreo-article-card">
                        <img class="oreo-article-image" src="{{ $blogPost->image_url }}" alt="{{ $blogPost->title }}">
                        @if ($blogPost->excerpt_text)
                            <div class="oreo-article-excerpt">{{ $blogPost->excerpt_text }}</div>
                        @endif
                        <div class="oreo-article-body">
                            {!! nl2br(e($blogPost->content)) !!}
                        </div>
                    </article>
                </div>
                <div class="col-lg-4">
                    <aside class="oreo-sidebar-card">
                        <h4>More News</h4>
                        @forelse ($relatedPosts as $relatedPost)
                            <a class="oreo-sidebar-post" href="{{ route('blogs.show', $relatedPost->slug) }}">
                                <img src="{{ $relatedPost->image_url }}" alt="{{ $relatedPost->title }}">
                                <div>
                                    <strong>{{ $relatedPost->title }}</strong>
                                    <span>{{ $relatedPost->published_label }}</span>
                                </div>
                            </a>
                        @empty
                            <p class="mb-0">No related posts yet.</p>
                        @endforelse

                        <div class="mt-4">
                            <a class="witr_btn" href="{{ route('blogs.index') }}">View All Posts</a>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </section>
@endsection
