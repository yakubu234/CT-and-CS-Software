@php
    $branchFormData = $branchFormData ?? [];
    $submitLabel = $submitLabel ?? 'Save branch';
    $submitIcon = $submitIcon ?? 'fas fa-save';
    $branchMembers = $branchMembers ?? collect();
@endphp

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Step 1: Branch profile</h3>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="branch_name">Branch name</label>
                <input type="text" id="branch_name" name="branch_name" value="{{ old('branch_name', $branchFormData['branch_name'] ?? '') }}" class="form-control @error('branch_name') is-invalid @enderror" required>
            </div>
            <div class="form-group col-md-3">
                <label for="branch_prefix">Branch prefix</label>
                <input type="text" id="branch_prefix" name="branch_prefix" value="{{ old('branch_prefix', $branchFormData['branch_prefix'] ?? '') }}" class="form-control @error('branch_prefix') is-invalid @enderror" required>
            </div>
            <div class="form-group col-md-3">
                <label for="loan_prefix">Loan prefix</label>
                <input type="text" id="loan_prefix" name="loan_prefix" value="{{ old('loan_prefix', $branchFormData['loan_prefix'] ?? '') }}" class="form-control @error('loan_prefix') is-invalid @enderror" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="contact_email">Contact email</label>
                <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $branchFormData['contact_email'] ?? '') }}" class="form-control @error('contact_email') is-invalid @enderror" required>
            </div>
            <div class="form-group col-md-6">
                <label for="contact_phone">Contact phone</label>
                <input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $branchFormData['contact_phone'] ?? '') }}" class="form-control @error('contact_phone') is-invalid @enderror">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="registration_number">Registration number</label>
                <input type="text" id="registration_number" name="registration_number" value="{{ old('registration_number', $branchFormData['registration_number'] ?? '') }}" class="form-control @error('registration_number') is-invalid @enderror">
            </div>
            <div class="form-group col-md-4">
                <label for="year_of_registration">Year of registration</label>
                <input type="number" id="year_of_registration" name="year_of_registration" value="{{ old('year_of_registration', $branchFormData['year_of_registration'] ?? '') }}" class="form-control @error('year_of_registration') is-invalid @enderror" min="1900" max="{{ now()->year }}">
            </div>
            <div class="form-group col-md-4">
                <label for="branch_meeting_days">Branch meeting days</label>
                <select id="branch_meeting_days" name="branch_meeting_days" class="form-control @error('branch_meeting_days') is-invalid @enderror" required>
                    <option value="">Select a cycle</option>
                    @foreach (['weekly', 'every 2 weeks', 'every 3 weeks', 'monthly'] as $meetingCycle)
                        <option value="{{ $meetingCycle }}" @selected(old('branch_meeting_days', $branchFormData['branch_meeting_days'] ?? '') === $meetingCycle)>
                            {{ ucfirst($meetingCycle) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="address">Branch address</label>
            <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $branchFormData['address'] ?? '') }}</textarea>
        </div>
    </div>
</div>

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title">Step 2: Optional branding</h3>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="photo">Branch logo</label>
                <div class="custom-file">
                    <input type="file" id="photo" name="photo" class="custom-file-input @error('photo') is-invalid @enderror" accept="image/*" data-preview-target="photo-preview">
                    <label class="custom-file-label" for="photo">Choose branch logo</label>
                </div>
                <div class="mt-3 {{ !($branchFormData['photo_url'] ?? null) ? 'd-none' : '' }}" id="photo-preview-wrapper">
                    <div class="small text-muted mb-2">Selected branch logo preview</div>
                    <img id="photo-preview" src="{{ $branchFormData['photo_url'] ?? '' }}" alt="Branch logo preview" class="img-thumbnail" style="max-height: 180px; width: auto;">
                </div>
                <small class="form-text text-muted">Optional. You can upload this later.</small>
            </div>
            <div class="form-group col-md-6">
                <label for="signature">Branch signature</label>
                <div class="custom-file">
                    <input type="file" id="signature" name="signature" class="custom-file-input @error('signature') is-invalid @enderror" accept="image/*" data-preview-target="signature-preview">
                    <label class="custom-file-label" for="signature">Choose branch signature</label>
                </div>
                <div class="mt-3 {{ !($branchFormData['signature_url'] ?? null) ? 'd-none' : '' }}" id="signature-preview-wrapper">
                    <div class="small text-muted mb-2">Selected branch signature preview</div>
                    <img id="signature-preview" src="{{ $branchFormData['signature_url'] ?? '' }}" alt="Branch signature preview" class="img-thumbnail" style="max-height: 180px; width: auto;">
                </div>
                <small class="form-text text-muted">Optional. You can upload this later.</small>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-success">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Step 3: Excos</h3>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-exco" @disabled($branchMembers->isEmpty())>
            <i class="fas fa-plus mr-1"></i>
            Add another exco
        </button>
    </div>
    <div class="card-body">
        <div class="alert alert-light border">
            Excos are selected from existing branch members only. If an exco is removed during editing, the user record stays in the system and is marked as a former exco.
        </div>

        @if ($branchMembers->isEmpty())
            <div class="alert alert-warning">
                No eligible branch members are available yet. Save the branch first, register members under it, then return here to assign excos.
            </div>
        @endif

        <div id="exco-list">
            @foreach ($excos as $index => $exco)
                <div class="border rounded p-3 mb-3 exco-item">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Exco #<span class="exco-number">{{ $index + 1 }}</span></h5>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-exco" @disabled(count($excos) === 1)>
                            Remove
                        </button>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label>Member</label>
                            <select name="excos[{{ $index }}][member_id]" class="form-control exco-member-select" @disabled($branchMembers->isEmpty()) required>
                                <option value="">Select branch member</option>
                                @foreach ($branchMembers as $member)
                                    <option value="{{ $member->id }}" @selected((string) ($exco['member_id'] ?? '') === (string) $member->id)>
                                        {{ $member->display_member_no ?: 'N/A' }} - {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-5">
                            <label>Designation</label>
                            <select name="excos[{{ $index }}][designation_id]" class="form-control" required>
                                <option value="">Select designation</option>
                                @foreach ($designations as $designation)
                                    <option value="{{ $designation->id }}" @selected(($exco['designation_id'] ?? '') == $designation->id)>
                                        {{ $designation->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-success btn-lg">
            <i class="{{ $submitIcon }} mr-1"></i>
            {{ $submitLabel }}
        </button>
        <x-browser-back-button :fallback="route('branches.index')" label="Back to branches" class="btn btn-link" />
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            const excoList = document.getElementById('exco-list');
            const addButton = document.getElementById('add-exco');

            if (!excoList || !addButton) {
                return;
            }

            const designationOptions = @json($designations->map(fn ($designation) => ['id' => $designation->id, 'name' => $designation->name])->values());
            const memberOptions = @json($branchMembers->map(fn ($member) => [
                'id' => $member->id,
                'label' => ($member->display_member_no ?: 'N/A') . ' - ' . $member->name,
            ])->values());

            function buildDesignationOptions() {
                let options = '<option value="">Select designation</option>';

                designationOptions.forEach(function (designation) {
                    options += `<option value="${designation.id}">${designation.name}</option>`;
                });

                return options;
            }

            function buildMemberOptions() {
                let options = '<option value="">Select branch member</option>';

                memberOptions.forEach(function (member) {
                    options += `<option value="${member.id}">${member.label}</option>`;
                });

                return options;
            }

            function syncMemberSelections() {
                const selects = Array.from(excoList.querySelectorAll('.exco-member-select'));
                const selectedValues = selects
                    .map(function (select) {
                        return select.value;
                    })
                    .filter(function (value) {
                        return value !== '';
                    });

                selects.forEach(function (select) {
                    const currentValue = select.value;
                    const excludedValues = selectedValues.filter(function (value) {
                        return value !== currentValue;
                    });

                    let options = '<option value="">Select branch member</option>';

                    memberOptions.forEach(function (member) {
                        const memberId = String(member.id);

                        if (excludedValues.includes(memberId)) {
                            return;
                        }

                        const selected = currentValue === memberId ? ' selected' : '';
                        options += `<option value="${memberId}"${selected}>${member.label}</option>`;
                    });

                    select.innerHTML = options;

                    if (select.classList.contains('select2-hidden-accessible')) {
                        $(select).trigger('change.select2');
                    }
                });
            }

            function refreshExcoState() {
                const items = excoList.querySelectorAll('.exco-item');

                items.forEach(function (item, index) {
                    item.querySelector('.exco-number').textContent = index + 1;

                    item.querySelectorAll('input, select').forEach(function (field) {
                        const name = field.getAttribute('name');

                        if (!name) {
                            return;
                        }

                        field.setAttribute('name', name.replace(/excos\[\d+]/, `excos[${index}]`));
                    });
                });

                excoList.querySelectorAll('.remove-exco').forEach(function (button) {
                    button.disabled = items.length === 1;
                });

                syncMemberSelections();

                if (typeof window.initializeSelect2 === 'function') {
                    window.initializeSelect2(excoList);
                }
            }

            addButton.addEventListener('click', function () {
                const wrapper = document.createElement('div');
                wrapper.className = 'border rounded p-3 mb-3 exco-item';
                wrapper.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Exco #<span class="exco-number"></span></h5>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-exco">Remove</button>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label>Member</label>
                            <select name="excos[0][member_id]" class="form-control exco-member-select" ${memberOptions.length === 0 ? 'disabled' : ''} required>
                                ${buildMemberOptions()}
                            </select>
                        </div>
                        <div class="form-group col-md-5">
                            <label>Designation</label>
                            <select name="excos[0][designation_id]" class="form-control" required>
                                ${buildDesignationOptions()}
                            </select>
                        </div>
                    </div>
                `;

                excoList.appendChild(wrapper);
                refreshExcoState();
            });

            excoList.addEventListener('click', function (event) {
                const removeButton = event.target.closest('.remove-exco');

                if (!removeButton || removeButton.disabled) {
                    return;
                }

                removeButton.closest('.exco-item').remove();
                refreshExcoState();
            });

            excoList.addEventListener('change', function (event) {
                if (!event.target.classList.contains('exco-member-select')) {
                    return;
                }

                syncMemberSelections();
            });

            refreshExcoState();
        })();
    </script>
@endpush
