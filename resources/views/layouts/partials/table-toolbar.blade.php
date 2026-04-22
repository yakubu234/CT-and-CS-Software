<div class="card-header border-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2 mb-md-0">
            <h3 class="card-title mb-0">{{ $title ?? 'Records' }}</h3>
            @isset($subtitle)
                <small class="text-muted d-block mt-1">{{ $subtitle }}</small>
            @endisset
        </div>

        <form method="GET" action="{{ $action ?? url()->current() }}" class="form-inline">
            <div class="input-group input-group-sm" style="min-width: 280px;">
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    class="form-control"
                    placeholder="{{ $placeholder ?? 'Search records' }}"
                    aria-label="Search"
                >
                <div class="input-group-append">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fas fa-search"></i>
                    </button>
                    @if (request()->filled('search'))
                        <a href="{{ $action ?? url()->current() }}" class="btn btn-outline-secondary">
                            Clear
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
