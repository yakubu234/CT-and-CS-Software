@extends('layouts.admin')

@section('title', 'Asset Categories')
@section('page_title', 'Asset Categories')

@section('content')
    <div class="card card-outline card-primary">
        @include('layouts.partials.table-toolbar', [
            'title' => 'Asset Categories',
            'subtitle' => 'Manage the fixed asset categories available when recording cooperative assets.',
            'action' => route('asset-categories.index'),
            'placeholder' => 'Search asset categories',
        ])

        <div class="card-body">
            <div class="mb-3">
                <a href="{{ route('asset-categories.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Add Category
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th style="width: 130px;">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $category->name }}</div>
                                <small class="text-muted">{{ $category->description ?: 'No description added.' }}</small>
                            </td>
                            <td><code>{{ $category->slug }}</code></td>
                            <td><span class="badge badge-{{ $category->status ? 'success' : 'secondary' }}">{{ $category->status ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <a href="{{ route('asset-categories.edit', $category) }}" class="btn btn-sm btn-outline-info mb-1">Edit</a>
                                <form action="{{ route('asset-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this asset category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger mb-1">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No asset categories found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $categories->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
