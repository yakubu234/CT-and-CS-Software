@extends('layouts.admin')

@section('title', 'Merge Duplicate Members')
@section('page_title', 'Merge Duplicate Members')

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('members.merge.store') }}" id="member-merge-form">
                @csrf

                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Choose the two records</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            The original member remains the login identity. The second record is archived after its societies,
                            accounts, loans, transactions, payments, documents, and messages are moved.
                        </div>

                        <section class="border rounded p-3 mb-4 bg-light">
                        <h4 class="mb-1">1. Original account holder</h4>
                        <p class="text-muted">This user ID and login will remain as the main account.</p>

                        <div class="form-group">
                            <label for="canonical_branch_id">Base society</label>
                            <select name="canonical_branch_id" id="canonical_branch_id" class="form-control @error('canonical_branch_id') is-invalid @enderror" required>
                                <option value="">Select the base society first</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('canonical_branch_id') == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('canonical_branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label for="canonical_user_id">Original member to keep</label>
                            <select name="canonical_user_id" id="canonical_user_id" data-native-select="true" class="form-control @error('canonical_user_id') is-invalid @enderror" required>
                                <option value="">Select a base society first</option>
                                @if (old('canonical_user_id') && $selectedMembers->has((string) old('canonical_user_id')))
                                    @php($selected = $selectedMembers->get((string) old('canonical_user_id')))
                                    <option value="{{ $selected['id'] }}" selected>{{ $selected['name'] }} — {{ $selected['email'] }} — {{ $selected['mobile'] ?: 'no phone' }}</option>
                                @endif
                            </select>
                            @error('canonical_user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div id="canonical-summary" class="member-summary"></div>
                        </section>

                        <section class="border rounded p-3">
                        <h4 class="mb-1">2. Society account to merge</h4>
                        <p class="text-muted">This account’s society membership and financial records will move to the original account holder.</p>

                        <div class="form-group">
                            <label for="merged_branch_id">Society being merged</label>
                            <select name="merged_branch_id" id="merged_branch_id" class="form-control @error('merged_branch_id') is-invalid @enderror" required>
                                <option value="">Select the society first</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('merged_branch_id') == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('merged_branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label for="merged_user_id">Duplicate society account to merge</label>
                            <select name="merged_user_id" id="merged_user_id" data-native-select="true" class="form-control @error('merged_user_id') is-invalid @enderror" required>
                                <option value="">Select the society first</option>
                                @if (old('merged_user_id') && $selectedMembers->has((string) old('merged_user_id')))
                                    @php($selected = $selectedMembers->get((string) old('merged_user_id')))
                                    <option value="{{ $selected['id'] }}" selected>{{ $selected['name'] }} — {{ $selected['email'] }} — {{ $selected['mobile'] ?: 'no phone' }}</option>
                                @endif
                            </select>
                            @error('merged_user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div id="merged-summary" class="member-summary"></div>
                        <div id="overlap-warning" class="alert alert-danger mt-3 d-none"></div>
                        </section>
                    </div>
                </div>

                <div class="card card-outline card-primary">
                    <div class="card-header"><h3 class="card-title">Choose the final contact and default society</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="email_source_user_id">Email used for login</label>
                            <select name="email_source_user_id" id="email_source_user_id" class="form-control @error('email_source_user_id') is-invalid @enderror" required>
                                <option value="">Select both members first</option>
                            </select>
                            @error('email_source_user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label for="mobile_source_user_id">Phone number to keep</label>
                            <select name="mobile_source_user_id" id="mobile_source_user_id" class="form-control @error('mobile_source_user_id') is-invalid @enderror" required>
                                <option value="">Select both members first</option>
                            </select>
                            @error('mobile_source_user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label for="primary_membership_id">Default society after login</label>
                            <select name="primary_membership_id" id="primary_membership_id" class="form-control @error('primary_membership_id') is-invalid @enderror" required>
                                <option value="">Select both members first</option>
                            </select>
                            @error('primary_membership_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="custom-control custom-checkbox mt-4">
                            <input type="checkbox" name="confirmation" value="1" class="custom-control-input @error('confirmation') is-invalid @enderror" id="confirmation" required>
                            <label class="custom-control-label" for="confirmation">
                                I have verified these records belong to the same person and understand this merge cannot be undone from this screen.
                            </label>
                            @error('confirmation') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('members.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning" id="merge-button" disabled>
                            <i class="fas fa-object-group mr-1"></i> Merge Member Records
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-secondary">
                <div class="card-header"><h3 class="card-title">What the merge preserves</h3></div>
                <div class="card-body">
                    <ul class="pl-3 mb-0">
                        <li>Every society membership and member number</li>
                        <li>All savings accounts and existing balances</li>
                        <li>Loans, repayments, and transaction history</li>
                        <li>Documents and communication history</li>
                        <li>An audit record of both identities and moved records</li>
                    </ul>
                    <hr>
                    <p class="text-danger mb-0"><strong>Blocked:</strong> two records that already share the same society. This avoids combining financially distinct memberships by mistake.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const members = {{ Illuminate\Support\Js::from($selectedMembers) }};
        const oldValues = {{ Illuminate\Support\Js::from($mergeOldValues) }};
        const canonicalSelect = document.getElementById('canonical_user_id');
        const mergedSelect = document.getElementById('merged_user_id');
        const canonicalBranchSelect = document.getElementById('canonical_branch_id');
        const mergedBranchSelect = document.getElementById('merged_branch_id');
        const emailSelect = document.getElementById('email_source_user_id');
        const mobileSelect = document.getElementById('mobile_source_user_id');
        const primarySelect = document.getElementById('primary_membership_id');
        const overlapWarning = document.getElementById('overlap-warning');
        const mergeButton = document.getElementById('merge-button');

        function initializeMemberSearch(select, branchSelect) {
            const $select = $(select);

            $select.select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Search by name, email, phone, or member number',
                minimumInputLength: 2,
                ajax: {
                    url: {{ Illuminate\Support\Js::from($memberSearchUrl) }},
                    dataType: 'json',
                    delay: 300,
                    data: params => ({
                        q: params.term || '',
                        branch_id: branchSelect.value,
                    }),
                    processResults: data => data,
                    cache: true,
                },
            }).on('select2:select', function (event) {
                const member = event.params.data;
                members[String(member.id)] = member;
                refresh();
            }).on('select2:clear', refresh);

            function syncWithBranch() {
                const previousMemberId = select.value;
                if (previousMemberId) {
                    delete members[String(previousMemberId)];
                }

                $select.val(null).trigger('change');
                $select.prop('disabled', !branchSelect.value).trigger('change.select2');
                refresh();
            }

            $select.prop('disabled', !branchSelect.value).trigger('change.select2');
            $(branchSelect).on('change select2:select select2:clear', syncWithBranch);
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value == null ? '' : String(value);
            return div.innerHTML;
        }

        function renderSummary(targetId, member) {
            const target = document.getElementById(targetId);
            if (!member) {
                target.innerHTML = '';
                return;
            }
            const societies = member.memberships.map(item =>
                `<span class="badge badge-light border mr-1 mb-1">${escapeHtml(item.branch)} · ${escapeHtml(item.member_number)}</span>`
            ).join('');
            target.innerHTML = `<div class="border rounded p-3 bg-light">
                <strong>${escapeHtml(member.name)}</strong>
                <div class="small text-muted">${escapeHtml(member.email)} · ${escapeHtml(member.mobile || 'No phone')}</div>
                <div class="small mt-2">${member.accounts} savings accounts · ${member.loans} loans</div>
                <div class="mt-2">${societies || '<span class="text-danger">No society membership</span>'}</div>
            </div>`;
        }

        function option(value, label, selectedValue) {
            const selected = String(value) === String(selectedValue) ? ' selected' : '';
            return `<option value="${escapeHtml(value)}"${selected}>${escapeHtml(label)}</option>`;
        }

        function refresh() {
            const canonical = members[canonicalSelect.value];
            const merged = members[mergedSelect.value];
            renderSummary('canonical-summary', canonical);
            renderSummary('merged-summary', merged);

            emailSelect.innerHTML = '<option value="">Choose an email</option>';
            mobileSelect.innerHTML = '<option value="">Choose a phone number</option>';
            primarySelect.innerHTML = '<option value="">Choose the default society</option>';
            overlapWarning.classList.add('d-none');
            mergeButton.disabled = true;

            if (!canonical || !merged || canonical.id === merged.id) {
                return;
            }

            [canonical, merged].forEach(member => {
                emailSelect.insertAdjacentHTML('beforeend', option(member.id, `${member.email} (${member.name})`, oldValues.email || canonical.id));
                mobileSelect.insertAdjacentHTML('beforeend', option(member.id, `${member.mobile || 'No phone'} (${member.name})`, oldValues.mobile || canonical.id));
                member.memberships.forEach(membership => {
                    primarySelect.insertAdjacentHTML('beforeend', option(
                        membership.id,
                        `${membership.branch} — ${membership.member_number}`,
                        oldValues.primary || (membership.is_primary ? membership.id : '')
                    ));
                });
            });

            const canonicalBranches = new Set(canonical.memberships.map(item => String(item.branch_id)));
            const overlap = merged.memberships.filter(item => canonicalBranches.has(String(item.branch_id)));
            if (overlap.length) {
                overlapWarning.textContent = 'Merge blocked: both records belong to ' + overlap.map(item => item.branch).join(', ') + '.';
                overlapWarning.classList.remove('d-none');
                return;
            }
            mergeButton.disabled = canonical.memberships.length + merged.memberships.length === 0;
        }

        initializeMemberSearch(canonicalSelect, canonicalBranchSelect);
        initializeMemberSearch(mergedSelect, mergedBranchSelect);
        canonicalSelect.addEventListener('change', refresh);
        mergedSelect.addEventListener('change', refresh);
        refresh();
    })();
</script>
@endpush
