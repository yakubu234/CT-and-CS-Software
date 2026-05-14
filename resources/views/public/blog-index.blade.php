@extends('public.layout')

@section('title', 'Blogs | Oreoluwapo Ilaro Cooperative Thrift & Credit Union Ltd.')
@section('meta_description', 'Read the latest Oreoluwapo Ilaro cooperative news, updates, outreach stories, and investment insights.')

@section('content')
    <section class="oreo-page-hero">
        <div class="container">
            <div class="witr_section_title white">
                <div class="witr_section_title_inner text-center">
                    <h2>News & Insights</h2>
                    <h3>Stories from Oreoluwapo Ilaro</h3>
                    <p class="oreo-page-hero-copy">Follow our latest outreach, cooperative progress, community development stories, and member-focused updates.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="oreo-section oreo-surface">
        <div class="container">
            <div class="oreo-blog-toolbar">
                <form method="GET" action="{{ route('blogs.index') }}" class="oreo-blog-search">
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search blog posts">
                    <button type="submit">Search</button>
                </form>
            </div>

            <div class="row oreo-founders">
                @forelse ($posts as $post)
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <article class="oreo-news-card oreo-blog-card">
                            <a href="{{ route('blogs.show', $post->slug) }}">
                                <img src="{{ $post->image_url }}" alt="{{ $post->title }}">
                            </a>
                            <div class="oreo-news-content">
                                <div class="oreo-news-meta">{{ strtoupper($post->published_label) }} | {{ strtoupper($post->createdBy?->name ?? 'ADMIN') }}</div>
                                <h4><a href="{{ route('blogs.show', $post->slug) }}">{{ $post->title }}</a></h4>
                                <p>{{ $post->excerpt_text }}</p>
                                <a class="oreo-read-more" href="{{ route('blogs.show', $post->slug) }}">Read More</a>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-lg-12">
                        <div class="oreo-empty-state">
                            <h4>No published blog posts yet</h4>
                            <p>Once posts are added and published from the admin panel, they will appear here automatically.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($posts->hasPages())
                <div class="mt-4">
                    {{ $posts->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
