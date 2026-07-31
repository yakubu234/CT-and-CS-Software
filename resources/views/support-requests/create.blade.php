@extends('layouts.admin')

@section('title', 'New Staff Complaint')
@section('page_title', 'New Staff Complaint')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header"><h3 class="card-title">Start an Admin-to-Admin Conversation</h3></div>
        <form method="POST" action="{{ route('support-requests.store') }}">
            @csrf
            <div class="card-body">
                <div class="alert alert-info">The complaint will be private between you and the selected administrator or staff member.</div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="recipient_user_id">Send To</label>
                        <select name="recipient_user_id" id="recipient_user_id" class="form-control @error('recipient_user_id') is-invalid @enderror" required>
                            <option value="">Select administrator or staff</option>
                            @foreach ($recipients as $recipient)
                                <option value="{{ $recipient->id }}" @selected((int) old('recipient_user_id') === (int) $recipient->id)>
                                    {{ $recipient->name }} — {{ $recipient->role?->name ?: 'Staff' }} — {{ $recipient->branch?->name ?: 'No branch' }}
                                </option>
                            @endforeach
                        </select>
                        @error('recipient_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="category">Category</label>
                        <select name="category" id="category" class="form-control" required>
                            @foreach ($categoryOptions as $value => $label)<option value="{{ $value }}" @selected(old('category', 'general') === $value)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="priority">Priority</label>
                        <select name="priority" id="priority" class="form-control" required>
                            @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $label)<option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input name="subject" id="subject" class="form-control" value="{{ old('subject') }}" maxlength="255" required>
                </div>
                <div class="form-group mb-0">
                    <label for="message">Complaint / Message</label>
                    <textarea name="message" id="message" class="form-control" rows="7" required>{{ old('message') }}</textarea>
                </div>
            </div>
            <div class="card-footer text-right">
                <a href="{{ route('support-requests.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane mr-1"></i> Start Conversation</button>
            </div>
        </form>
    </div>
@endsection
