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
                            @php($selectedBranches = array_map('strval', (array) old('branch_ids', $currentBranch ? [$currentBranch->id] : ['all'])))
                            <label for="branch_ids">Target Branches</label>
                            <select name="branch_ids[]" id="branch_ids" class="form-control select2 branch-multi" multiple data-placeholder="Choose one or more branches">
                                <option value="all" @selected(in_array('all', $selectedBranches, true))>All accessible branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(in_array((string) $branch->id, $selectedBranches, true))>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Pick several branches (or all) and a separate campaign is created and sent for each one.</small>
                            @error('branch_ids')<div class="text-danger small">{{ $message }}</div>@enderror
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
                                    <option value="{{ $member->id }}" selected>
                                        {{ $member->display_member_no ?: 'N/A' }} - {{ $member->name }} ({{ $member->branch?->name ?: 'No branch' }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Type at least two characters of the member's name, number, email or phone. Results are loaded as you search.</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea name="message" id="message" rows="7" class="form-control">{{ old('message') }}</textarea>
                            <small class="text-muted">Templates can use placeholders like <code>@{{member_name}}</code>, <code>@{{branch_name}}</code>, <code>@{{statement_summary}}</code>.</small>
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

@include('bulk-sms.partials.branch-multi-script')

@push('scripts')
    <script>
        (() => {
            const audience = document.getElementById('audience_type');
            const memberWrap = document.querySelector('.audience-selected-members');
            const template = document.getElementById('template_id');
            const message = document.getElementById('message');
            const $branchSelect = window.jQuery ? window.jQuery('#branch_ids') : null;
            const $memberSelect = window.jQuery ? window.jQuery('#member_ids') : null;

            if ($memberSelect?.length && typeof window.jQuery.fn.select2 === 'function') {
                if ($memberSelect.hasClass('select2-hidden-accessible')) {
                    $memberSelect.select2('destroy');
                }

                $memberSelect.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: 'Search members',
                    minimumInputLength: 2,
                    ajax: {
                        url: @json(route('bulk-sms.campaigns.members.search')),
                        dataType: 'json',
                        delay: 300,
                        data: params => ({
                            q: params.term || '',
                            page: params.page || 1,
                            branch_ids: $branchSelect?.val() || [],
                        }),
                        processResults: data => data,
                        cache: false,
                    },
                });

                $branchSelect?.on('change', () => $memberSelect.val(null).trigger('change'));
            }

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

            // Select2 fires jQuery "change" events, which native addEventListener does not receive.
            if (window.jQuery) {
                window.jQuery(audience).on('change', toggleMemberWrap);
                window.jQuery(template).on('change', fillTemplate);
            } else {
                audience?.addEventListener('change', toggleMemberWrap);
                template?.addEventListener('change', fillTemplate);
            }
            toggleMemberWrap();
        })();
    </script>
@endpush
