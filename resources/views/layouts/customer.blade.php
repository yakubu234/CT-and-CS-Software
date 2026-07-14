<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Customer Portal') | Oreoluwapo CT&CU</title>
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <style>
        .customer-shell {
            background: #f5f7fb;
        }

        .customer-sidebar .brand-link {
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .customer-branch-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.75rem;
            border: 1px solid #dbe5f0;
            border-radius: 999px;
            background: #fff;
            color: #334155;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .customer-page-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
            padding: 1rem 0 0.75rem;
        }

        .customer-page-head h1 {
            margin: 0;
            font-size: 1.55rem;
            font-weight: 800;
            color: #122033;
        }

        .customer-card {
            border: 1px solid #dbe5f0;
            border-radius: 0.5rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .money-value {
            font-weight: 800;
            color: #10213a;
        }

        .nav-sidebar .nav-link.active {
            background: #2563eb;
        }

        @media print {
            .main-header,
            .main-sidebar,
            .customer-page-actions,
            .main-footer {
                display: none !important;
            }

            .content-wrapper {
                margin-left: 0 !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed customer-shell">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item d-none d-md-flex align-items-center mr-2">
                <span class="customer-branch-chip">
                    <i class="fas fa-code-branch"></i>
                    {{ auth()->user()->branch?->name ?? 'No Branch' }}
                </span>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user mr-1"></i>
                    {{ auth()->user()->name }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('customer.profile') }}" class="dropdown-item">
                        <i class="fas fa-user-circle mr-2"></i> Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST" class="px-3 py-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-block">Sign out</button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4 customer-sidebar">
        <a href="{{ route('customer.dashboard') }}" class="brand-link">
            <img src="{{ asset('vendor/adminlte/dist/img/AdminLTELogo.png') }}" alt="Oreoluwapo CT&CU" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">Member Portal</span>
        </a>

        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="{{ asset('vendor/adminlte/dist/img/user2-160x160.jpg') }}" class="img-circle elevation-2" alt="Member">
                </div>
                <div class="info">
                    <a href="{{ route('customer.profile') }}" class="d-block">{{ auth()->user()->name }}</a>
                    <span class="text-muted small">{{ auth()->user()->display_member_no ?? 'Member' }}</span>
                </div>
            </div>

            @php
                $customerMenu = [
                    ['route' => 'customer.dashboard', 'icon' => 'fas fa-home', 'label' => 'Dashboard'],
                    ['route' => 'customer.accounts', 'icon' => 'fas fa-wallet', 'label' => 'My Accounts'],
                    ['route' => 'customer.statement', 'icon' => 'fas fa-file-invoice', 'label' => 'Statement'],
                    ['route' => 'customer.loans', 'icon' => 'fas fa-hand-holding-usd', 'label' => 'Loans'],
                    ['route' => 'customer.repayments', 'icon' => 'fas fa-receipt', 'label' => 'Repayments'],
                    ['route' => 'customer.transactions', 'icon' => 'fas fa-exchange-alt', 'label' => 'Transactions'],
                    ['route' => 'customer.notifications', 'icon' => 'fas fa-bell', 'label' => 'Notifications'],
                    ['route' => 'customer.profile', 'icon' => 'fas fa-user-circle', 'label' => 'Profile'],
                    ['route' => 'customer.support', 'icon' => 'fas fa-life-ring', 'label' => 'Support'],
                ];
            @endphp

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
                    @foreach ($customerMenu as $item)
                        <li class="nav-item">
                            <a href="{{ route($item['route']) }}" class="nav-link @if(request()->routeIs($item['route'])) active @endif">
                                <i class="nav-icon {{ $item['icon'] }}"></i>
                                <p>{{ $item['label'] }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content">
            <div class="container-fluid">
                <div class="customer-page-head">
                    <div>
                        <h1>@yield('page_title', 'Customer Portal')</h1>
                        <div class="text-muted">@yield('page_subtitle', 'Manage your cooperative records and activity.')</div>
                    </div>
                    <div class="customer-page-actions">
                        @yield('page_actions')
                    </div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>Oreoluwapo CT &amp; CU Member Portal.</strong>
    </footer>
</div>

<script src="{{ asset('vendor/adminlte/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
@stack('scripts')
</body>
</html>
