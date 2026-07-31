@extends('layouts.customer')

@section('title', 'Support Conversation')
@section('page_title', 'Support Conversation')
@section('page_subtitle', $supportRequest->subject)

@section('content')
    <div class="card customer-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="card-title mb-1">{{ $supportRequest->subject }}</h3>
                <div class="small text-muted">{{ ucfirst($supportRequest->category) }} · {{ str_replace('_', ' ', ucfirst($supportRequest->status)) }}</div>
            </div>
            <a href="{{ route('customer.support') }}" class="btn btn-sm btn-outline-secondary">Back</a>
        </div>
        <div class="card-body" style="max-height: 560px; overflow-y: auto;">
            @forelse ($supportRequest->messages as $message)
                @php($mine = (int) $message->sender_id === (int) auth()->id())
                <div class="d-flex mb-3 {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
                    <div class="rounded p-3 {{ $mine ? 'bg-primary text-white' : 'bg-light border' }}" style="max-width: 85%;">
                        <div class="small font-weight-bold mb-1">{{ $mine ? 'You' : ($message->sender?->name ?: 'Cooperative Support') }}</div>
                        <div style="white-space: pre-wrap;">{{ $message->message }}</div>
                        <div class="small mt-2 {{ $mine ? 'text-white-50' : 'text-muted' }}">{{ optional($message->created_at)->format('d M Y, h:i A') }}</div>
                    </div>
                </div>
            @empty
                <p class="text-muted text-center">No messages yet.</p>
            @endforelse
        </div>
        <div class="card-footer">
            <form method="POST" action="{{ route('customer.support.reply', $supportRequest) }}">
                @csrf
                <div class="form-group">
                    <label for="message">Your Reply</label>
                    <textarea name="message" id="message" rows="4" class="form-control @error('message') is-invalid @enderror" required>{{ old('message') }}</textarea>
                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Send Reply</button>
            </form>
        </div>
    </div>
@endsection
