@php
    $viewer = auth()->user();

    if ($viewer && ! $viewer->canAccessMenuItem($item)) {
        return;
    }

    $hasSubmenu = ! empty($item['submenu']);
    $visibleSubmenu = $hasSubmenu
        ? collect($item['submenu'])->filter(fn (array $child) => ! $viewer || $viewer->canAccessMenuItem($child))->values()->all()
        : [];
    $patterns = (array) ($item['active'] ?? []);
    $isDirectActive = collect($patterns)->contains(fn ($pattern) => request()->is($pattern));
    $isChildActive = $hasSubmenu
        ? collect($visibleSubmenu)->contains(function (array $child): bool {
            $childPatterns = (array) ($child['active'] ?? []);
            $childActive = collect($childPatterns)->contains(fn ($pattern) => request()->is($pattern));

            if ($childActive) {
                return true;
            }

            return ! empty($child['submenu'])
                && collect($child['submenu'])->contains(function (array $nestedChild): bool {
                    $nestedPatterns = (array) ($nestedChild['active'] ?? []);

                    return collect($nestedPatterns)->contains(fn ($pattern) => request()->is($pattern));
                });
        })
        : false;
    $isActive = $isDirectActive || $isChildActive;
@endphp

@if (! empty($item['header']))
    <li class="nav-header text-uppercase">{{ $item['header'] }}</li>
@else
    <li class="nav-item {{ $hasSubmenu && $isActive ? 'menu-open' : '' }}">
        <a
            href="{{ $hasSubmenu ? '#' : url($item['url'] ?? '#') }}"
            class="nav-link {{ $isActive ? 'active' : '' }}"
        >
            <i class="nav-icon {{ $item['icon'] ?? 'far fa-circle' }}"></i>
            <p>
                {{ $item['text'] ?? 'Menu item' }}
                @if ($hasSubmenu)
                    <i class="right fas fa-angle-left"></i>
                @endif
            </p>
        </a>

        @if ($hasSubmenu)
            <ul class="nav nav-treeview">
                @foreach ($visibleSubmenu as $submenuItem)
                    @include('layouts.partials.sidebar-menu-item', ['item' => $submenuItem])
                @endforeach
            </ul>
        @endif
    </li>
@endif
