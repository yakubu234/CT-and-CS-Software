@php
    $isHome = request()->routeIs('home');
    $homeUrl = route('home');
    $navLinks = [
        ['label' => 'Home', 'href' => $isHome ? '#home' : $homeUrl, 'active' => request()->routeIs('home')],
        ['label' => 'About Us', 'href' => route('about'), 'active' => request()->routeIs('about')],
        ['label' => 'Our History', 'href' => route('history'), 'active' => request()->routeIs('history')],
        ['label' => 'Our Leadership', 'href' => $homeUrl . '#leaders', 'active' => false],
        ['label' => 'Blogs', 'href' => route('blogs.index'), 'active' => request()->routeIs('blogs.*')],
        ['label' => 'Contact Us', 'href' => $homeUrl . '#contact', 'active' => false],
    ];
@endphp

<div class="em40_header_area_main">
    <div class="temkuri-header-top">
        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-lg-9 col-xl-9 col-md-9 col-sm-12">
                    <div class="top-address text-left">
                        <p>
                            <span><i class="fas fa-map-marker-alt"></i>Udoji Road, along Odo-Aje Road, opposite Okobo Joint, Ilaro, Ogun State.</span>
                            <a href="tel:+2348151273635"><i class="fas fa-phone-alt"></i>+234 815 127 3635</a>
                            <a href="mailto:info@oreoluwapo.org.ng"><i class="fas fa-envelope"></i>info@oreoluwapo.org.ng</a>
                        </p>
                    </div>
                </div>
                <div class="col-xs-12 col-lg-3 col-xl-3 col-md-3 col-sm-12">
                    <div class="top-right-menu">
                        <ul class="social-icons text-right text_m_center">
                            <li><a href="https://www.facebook.com/share/16Lz9nwTi2/" target="_blank" rel="noreferrer"><i class="fa fa-facebook-f"></i></a></li>
                            <li><a href="https://www.tiktok.com/@oreoluwapoilaro.c?_t=ZM-8vnbD2BCGnO&_r=1" target="_blank" rel="noreferrer"><i class="fab fa-tiktok"></i></a></li>
                            <li><a href="https://www.youtube.com/channel/UCsyB1PQWgzpC0BwCw3mPO6w" target="_blank" rel="noreferrer"><i class="fab fa-youtube"></i></a></li>
                            <li><a href="{{ $homeUrl }}#footer-info">RCN: 14043</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tx_top2_relative">
        <div class="">
            <div class="tx_relative_m">
                <div class="">
                    <div class="mainmenu_width_tx">
                        <div class="temkuri-main-menu one_page hidden-xs hidden-sm witr_h_h10">
                            <div class="temkuri_nav_area scroll_fixed postfix">
                                <div class="container">
                                    <div class="row logo-left">
                                        <div class="col-md-3 col-sm-3 col-xs-4">
                                            <div class="logo">
                                                <a class="main_sticky_main_l" href="{{ route('home') }}" title="Oreoluwapo">
                                                    <img src="{{ asset('frontend/images/logo.png') }}" alt="Oreoluwapo logo">
                                                </a>
                                                <a class="main_sticky_l" href="{{ route('home') }}" title="Oreoluwapo">
                                                    <img src="{{ asset('frontend/images/logo.png') }}" alt="Oreoluwapo logo">
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-9 col-sm-9 col-xs-8">
                                            <nav class="temkuri_menu">
                                                <ul class="sub-menu">
                                                    @foreach ($navLinks as $item)
                                                        <li class="{{ $item['active'] ? 'current' : '' }}">
                                                            <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                                <div class="donate-btn-header">
                                                    <a class="dtbtn" href="{{ route('login') }}">Login</a>
                                                </div>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mobile_logo_area hidden-md hidden-lg">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="mobile_menu_logo text-center">
                    <a href="{{ route('home') }}" title="Oreoluwapo">
                        <img src="{{ asset('frontend/images/logo.png') }}" alt="Oreoluwapo logo">
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="home-2 mbm hidden-md hidden-lg header_area main-menu-area">
    <div class="menu_area mobile-menu">
        <nav class="temkuri_menu">
            <ul class="sub-menu">
                @foreach ($navLinks as $item)
                    <li class="{{ $item['active'] ? 'current' : '' }}">
                        <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                    </li>
                @endforeach
                <li><a href="{{ route('login') }}">Login</a></li>
            </ul>
        </nav>
    </div>
</div>
