<!DOCTYPE HTML>
<html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>@yield('title', 'Oreoluwapo Ilaro Cooperative Thrift & Credit Union Ltd.')</title>
        <meta
            name="description"
            content="@yield('meta_description', 'Oreoluwapo Ilaro Cooperative Thrift & Credit Union Ltd. empowers communities through cooperative growth, trusted savings culture, and member-focused development.')"
        >
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/png" href="{{ asset('frontend/images/logo.png') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/template/assets/css/bootstrap.min.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/template/venobox/venobox.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/template/assets/css/plugin_theme_css.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/template/style.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/template/assets/css/responsive.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('frontend/template/oreoluwapo-overrides.css') }}">
        @stack('styles')
    </head>
    <body>
        @include('public.partials.header')

        @yield('content')

        @include('public.partials.footer')

        <script src="{{ asset('frontend/template/assets/js/vendor/jquery-3.5.1.min.js') }}"></script>
        <script src="{{ asset('frontend/template/assets/js/bootstrap.min.js') }}"></script>
        <script src="{{ asset('frontend/template/assets/js/isotope.pkgd.min.js') }}"></script>
        <script src="{{ asset('frontend/template/assets/js/slick.min.js') }}"></script>
        <script src="{{ asset('frontend/template/assets/js/imagesloaded.pkgd.min.js') }}"></script>
        <script src="{{ asset('frontend/template/venobox/venobox.min.js') }}"></script>
        <script src="{{ asset('frontend/template/assets/js/theme-pluginjs.js') }}"></script>
        <script src="{{ asset('frontend/template/assets/js/jquery.meanmenu.js') }}"></script>
        <script src="{{ asset('frontend/template/assets/js/ajax-mail.js') }}"></script>
        <script src="{{ asset('frontend/template/assets/js/map.js') }}"></script>
        <script src="{{ asset('frontend/template/assets/js/theme.js') }}"></script>
        @stack('scripts')
    </body>
</html>
