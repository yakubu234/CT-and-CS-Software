@extends('layouts.admin')

@section('title', 'Switch Branch')
@section('page_title', 'Switch Branch')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Choose the branch you want to work on</h3>
        </div>
        <div class="card-body">
            <div id="branch-switch-toolbar">
                @include('layouts.partials.table-toolbar', [
                    'action' => route('branches.switch.index'),
                    'placeholder' => 'Search branches by name, prefix, phone, or registration number',
                    'search' => request('search'),
                ])
            </div>

            @error('branch_id')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Prefix</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th class="text-center" style="width: 180px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($branches as $branch)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $branch->name }}</div>
                                <div class="text-muted small">Reg No: {{ $branch->registration_number ?: 'N/A' }}</div>
                            </td>
                            <td>{{ $branch->prefix ?: $branch->id_prefix ?: 'N/A' }}</td>
                            <td>
                                <div>{{ $branch->contact_phone ?: 'N/A' }}</div>
                                <div class="text-muted small">{{ $branch->contact_email ?: 'N/A' }}</div>
                            </td>
                            <td>{{ $branch->address ?: 'N/A' }}</td>
                            <td class="text-center">
                                @if ($currentBranch && (int) $currentBranch->id === (int) $branch->id)
                                    <span class="badge badge-success px-3 py-2">Current Branch</span>
                                @else
                                    <form action="{{ route('branches.switch.store') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                        <input type="hidden" name="redirect_to" value="{{ url()->previous() === url()->current() ? route('dashboard') : url()->previous() }}">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Work Here
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No branches were found for this user.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $branches->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const storageKey = 'branch-switch-search-focus';
            const toolbar = document.getElementById('branch-switch-toolbar');

            if (! toolbar) {
                return;
            }

            const form = toolbar.querySelector('form');
            const searchInput = form ? form.querySelector('input[name="search"]') : null;

            if (! form || ! searchInput) {
                return;
            }

            const storedValue = window.sessionStorage.getItem(storageKey);

            if (storedValue !== null && storedValue === searchInput.value) {
                window.sessionStorage.removeItem(storageKey);

                window.requestAnimationFrame(function () {
                    searchInput.focus();

                    const length = searchInput.value.length;
                    searchInput.setSelectionRange(length, length);
                });
            }

            let debounceTimer = null;
            let lastSubmittedValue = searchInput.value;

            const submitSearch = () => {
                const nextValue = searchInput.value;

                if (nextValue === lastSubmittedValue) {
                    return;
                }

                lastSubmittedValue = nextValue;
                window.sessionStorage.setItem(storageKey, nextValue);
                form.submit();
            };

            searchInput.addEventListener('input', function () {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(submitSearch, 300);
            });

            searchInput.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                window.clearTimeout(debounceTimer);
                submitSearch();
            });
        })();
    </script>
@endpush
