@extends('layouts.admin')

@section('title', $reportTitle)
@section('page_title', $reportTitle)

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-body text-center py-5">
            <div class="mb-3">
                <i class="fas fa-chart-bar fa-3x text-primary"></i>
            </div>
            <h3 class="mb-2">{{ $reportTitle }}</h3>
            <p class="text-muted mb-4">
                This report page is reserved and will be implemented next.
            </p>
            <a href="{{ route('reports.member-balance') }}" class="btn btn-primary">
                Go to Member Balance Report
            </a>
        </div>
    </div>
@endsection
