<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Contracts\View\View;

class PublicSiteController extends Controller
{
    public function home(): View
    {
        return view('public.home', [
            'latestPosts' => BlogPost::query()
                ->with('createdBy')
                ->published()
                ->latest('published_at')
                ->latest('id')
                ->take(4)
                ->get(),
        ]);
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function history(): View
    {
        return view('public.history');
    }
}
