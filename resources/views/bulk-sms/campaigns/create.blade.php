@extends('layouts.admin')

@section('title', 'Create SMS Campaign')
@section('page_title', 'Create SMS Campaign')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-body">
            <div class="alert alert-light border">
                Current branch default: <strong>{{ $currentBranch?->name ?: 'No active branch selected' }}</strong>
            </div>

            <form method="POST" action="{{ route('bulk-sms.campaigns.store') }}">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Campaign Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="branch_id">Target Branch</label>
                            <select name="branch_id" id="branch_id" class="form-control select2" data-placeholder="Choose branch">
                                <option value="">All accessible branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $currentBranch?->id) === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="template_id">Template</label>
                            <select name="template_id" id="template_id" class="form-control select2">
                                <option value="">Write custom message</option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}" data-body="{{ e($template->body) }}" @selected((string) old('template_id') === (string) $template->id)>
                                        {{ $template->name }} ({{ ucfirst(str_replace('_', ' ', $template->category)) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="audience_type">Audience</label>
                            <select name="audience_type" id="audience_type" class="form-control select2">
                                <option value="branch_members" @selected(old('audience_type', 'branch_members') === 'branch_members')>All members in target branch</option>
                                <option value="selected_members" @selected(old('audience_type') === 'selected_members')>Selected individual members</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 audience-selected-members d-none">
                        <div class="form-group">
                            <label for="member_ids">Select Members</label>
                            <select name="member_ids[]" id="member_ids" class="form-control select2" multiple data-placeholder="Choose members">
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}" @selected(collect(old('member_ids', []))->contains($member->id))>
                                        {{ $member->detail?->member_no ?: $member->member_no ?: 'N/A' }} - {{ $member->name }} ({{ $member->branch?->name ?: 'No branch' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea name="message" id="message" rows="7" class="form-control">{{ old('message') }}</textarea>
                            <small class="text-muted">Templates can use placeholders like <code>{{ '{{member_name}}' }}</code>, <code>{{ '{{branch_name}}' }}</code>, <code>{{ '{{statement_summary}}' }}</code>.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="scheduled_at">Schedule Time</label>
                            <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}">
                            <small class="text-muted">Leave empty to send immediately.</small>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Save Campaign</button>
                    <a href="{{ route('bulk-sms.campaigns.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const audience = document.getElementById('audience_type');
            const memberWrap = document.querySelector('.audience-selected-members');
            const template = document.getElementById('template_id');
            const message = document.getElementById('message');

            const toggleMemberWrap = () => {
                if (! audience || ! memberWrap) {
                    return;
                }

                memberWrap.classList.toggle('d-none', audience.value !== 'selected_members');
            };

            const fillTemplate = () => {
                if (! template || ! message || message.value.trim() !== '') {
                    return;
                }

                const selected = template.options[template.selectedIndex];

                if (selected && selected.dataset.body) {
                    message.value = selected.dataset.body;
                }
            };

            audience?.addEventListener('change', toggleMemberWrap);
            template?.addEventListener('change', fillTemplate);
            toggleMemberWrap();
        })();
    </script>
@endpush
