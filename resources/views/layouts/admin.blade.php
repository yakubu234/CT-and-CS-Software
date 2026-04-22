<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') | Oreoluwapo CT&CS</title>
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap4-theme/1.5.2/select2-bootstrap4.min.css">
    <style>
        .select2-container {
            width: 100% !important;
        }

        .select2-container--bootstrap4 .select2-selection,
        .select2-container .select2-selection {
            min-height: calc(2.25rem + 2px);
            border: 1px solid #ced4da !important;
            border-radius: 0.25rem !important;
            background-color: #fff !important;
            box-shadow: none !important;
        }

        .select2-container--bootstrap4 .select2-selection--single,
        .select2-container .select2-selection--single {
            height: calc(2.25rem + 2px);
            display: flex !important;
            align-items: center;
        }

        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered,
        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: calc(2.25rem + 2px);
            color: #495057 !important;
            padding-left: 0.75rem !important;
            padding-right: 2.25rem !important;
        }

        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow,
        .select2-container .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 2px);
            right: 0.5rem !important;
        }

        .select2-container--bootstrap4 .select2-selection--multiple,
        .select2-container .select2-selection--multiple {
            min-height: calc(2.25rem + 2px);
            padding: 0.175rem 0.375rem !important;
        }

        .select2-container--bootstrap4.select2-container--focus .select2-selection,
        .select2-container.select2-container--focus .select2-selection {
            border-color: #80bdff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        }

        .select2-container--disabled .select2-selection {
            background-color: #e9ecef !important;
            opacity: 1;
        }

        .select2-dropdown .select2-results > .select2-results__options,
        .select2-container--bootstrap4 .select2-results > .select2-results__options {
            max-height: 150px !important;
            overflow-y: auto !important;
        }

        .select2-results__option {
            white-space: normal;
            word-break: break-word;
        }

        input[type="date"] {
            cursor: pointer;
        }

        .current-branch-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            max-width: 280px;
            padding: 0.35rem 0.85rem;
            border: 1px solid #d7dde5;
            border-radius: 999px;
            background: #f8fafc;
            color: #1f2937;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .current-branch-chip i {
            color: #1d4ed8;
        }

        .page-branch-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.9rem 1rem;
            margin-bottom: 1rem;
            border: 1px solid #dbe5f0;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
        }

        .page-branch-banner strong {
            display: block;
            color: #0f172a;
        }

        .page-branch-banner span {
            color: #475569;
        }

        .sidebar {
            min-height: calc(100vh - 57px);
            display: flex;
            flex-direction: column;
        }

        .sidebar-logout {
            margin-top: auto;
            padding-top: 0.75rem;
            padding-bottom: 0.5rem;
        }

        .sidebar-logout .nav-link {
            background: transparent;
            border: 0;
        }

        .table-responsive {
            overflow-x: visible;
        }

        .table-responsive > .table,
        .card-body.table-responsive > .table {
            width: 100%;
            margin-bottom: 0;
            table-layout: fixed;
        }

        .table-responsive > .table th,
        .table-responsive > .table td,
        .card-body.table-responsive > .table th,
        .card-body.table-responsive > .table td {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            vertical-align: middle;
        }

        .table-responsive > .table th,
        .card-body.table-responsive > .table th {
            font-weight: 700;
        }

        .table-responsive > .table td .btn,
        .card-body.table-responsive > .table td .btn {
            margin-bottom: 0.25rem;
        }

        @media (max-width: 991.98px) {
            .table-responsive > .table,
            .card-body.table-responsive > .table {
                font-size: 0.92rem;
            }

            .table-responsive > .table th,
            .table-responsive > .table td,
            .card-body.table-responsive > .table th,
            .card-body.table-responsive > .table td {
                padding: 0.55rem 0.45rem;
            }
        }

        @media (max-width: 767.98px) {
            .table-responsive > .table,
            .card-body.table-responsive > .table {
                font-size: 0.84rem;
            }

            .table-responsive > .table th,
            .table-responsive > .table td,
            .card-body.table-responsive > .table th,
            .card-body.table-responsive > .table td {
                padding: 0.45rem 0.35rem;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
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
                <a href="{{ route('branches.switch.index') }}" class="current-branch-chip text-decoration-none" title="{{ $currentBranch?->name ?? 'No active branch selected' }}">
                    <i class="fas fa-code-branch"></i>
                    <span>{{ $currentBranch?->name ?? 'No Active Branch' }}</span>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user mr-1"></i>
                    {{ auth()->user()->name }}
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <div class="px-3 py-2">
                        <div class="text-muted text-uppercase small mb-1">Current branch</div>
                        <div class="font-weight-bold">{{ $currentBranch?->name ?? 'No Active Branch' }}</div>
                        <a href="{{ route('branches.switch.index') }}" class="btn btn-outline-primary btn-sm btn-block mt-2">
                            Switch branch
                        </a>
                    </div>
                    <div class="dropdown-divider"></div>
                    <span class="dropdown-item dropdown-header">{{ auth()->user()->email }}</span>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST" class="px-3 py-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-block">Sign out</button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <img src="{{ asset('vendor/adminlte/dist/img/AdminLTELogo.png') }}" alt="Oreoluwapo CT&CS" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">Oreoluwapo CT&amp;CS</span>
        </a>

        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="{{ asset('vendor/adminlte/dist/img/user2-160x160.jpg') }}" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="{{ route('dashboard') }}" class="d-block">{{ auth()->user()->name }}</a>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    @foreach (config('adminlte.menu', []) as $menuItem)
                        @include('layouts.partials.sidebar-menu-item', ['item' => $menuItem])
                    @endforeach
                </ul>
            </nav>

            <div class="sidebar-logout">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-link btn btn-link w-100 text-left text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p class="mb-0">Logout</p>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>@yield('page_title', 'Dashboard')</h1>
                    </div>
                    <div class="col-sm-6 text-sm-right">
                        <a href="{{ route('branches.switch.index') }}" class="btn btn-outline-primary btn-sm mt-2 mt-sm-0">
                            <i class="fas fa-code-branch mr-1"></i>
                            Switch Branch
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <div class="page-branch-banner">
                    <div>
                        <strong>Current Branch: {{ $currentBranch?->name ?? 'No Active Branch Selected' }}</strong>
                        <span>
                            {{ $currentBranch?->prefix ? 'Prefix: ' . $currentBranch->prefix . ' · ' : '' }}
                            This page is working inside the selected branch context.
                        </span>
                    </div>
                    <a href="{{ route('branches.switch.index') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-exchange-alt mr-1"></i>
                        Change Branch
                    </a>
                </div>

                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>Oreoluwapo CT&amp;CS Admin Panel.</strong>
        <div class="float-right d-none d-sm-inline-block">
            AdminLTE starter
        </div>
    </footer>
</div>

<script src="{{ asset('vendor/adminlte/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
<script>
    (function ($) {
        function initializeSelect2(scope) {
            if (typeof $.fn.select2 !== 'function') {
                return;
            }

            const $scope = scope ? $(scope) : $(document);

            $scope.find('select').each(function () {
                const $select = $(this);

                if ($select.hasClass('select2-hidden-accessible') || $select.data('nativeSelect') === true) {
                    return;
                }

                $select.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: $select.attr('placeholder') || undefined,
                    allowClear: ! $select.prop('required') && $select.find('option[value=""]').length > 0,
                });
            });
        }

        window.initializeSelect2 = initializeSelect2;

        $(function () {
            initializeSelect2(document);

            $(document).on('focus click', 'input[type="date"]', function () {
                if (typeof this.showPicker === 'function') {
                    this.showPicker();
                }
            });
        });
    })(jQuery);
</script>
@stack('scripts')
</body>
</html>
