@php
    $branchFormData = $branchFormData ?? [];
    $submitLabel = $submitLabel ?? 'Save branch';
    $submitIcon = $submitIcon ?? 'fas fa-save';
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
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-exco">
            <i class="fas fa-plus mr-1"></i>
            Add another exco
        </button>
    </div>
    <div class="card-body">
        <div class="alert alert-light border">
            Excos are stored as users. If an exco is removed during editing, the user record stays in the system and is marked as a former exco.
        </div>

        <div id="exco-list">
            @foreach ($excos as $index => $exco)
                <div class="border rounded p-3 mb-3 exco-item">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Exco #<span class="exco-number">{{ $index + 1 }}</span></h5>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-exco" @disabled(count($excos) === 1)>
                            Remove
                        </button>
                    </div>

                    <input type="hidden" name="excos[{{ $index }}][user_id]" value="{{ $exco['user_id'] ?? '' }}">

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>First name</label>
                            <input type="text" name="excos[{{ $index }}][first_name]" value="{{ $exco['first_name'] ?? '' }}" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Last name</label>
                            <input type="text" name="excos[{{ $index }}][last_name]" value="{{ $exco['last_name'] ?? '' }}" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Phone</label>
                            <input type="text" name="excos[{{ $index }}][phone]" value="{{ $exco['phone'] ?? '' }}" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
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

                    <div class="form-group mb-0">
                        <label>Exco image</label>
                        <div class="custom-file">
                            <input type="file" name="excos[{{ $index }}][image]" class="custom-file-input" accept="image/*" data-preview-target="exco-image-preview-{{ $index }}">
                            <label class="custom-file-label">Choose exco image</label>
                        </div>
                        <div class="mt-3 {{ !($exco['image_url'] ?? null) ? 'd-none' : '' }}" id="exco-image-preview-{{ $index }}-wrapper">
                            <div class="small text-muted mb-2">Selected exco image preview</div>
                            <img id="exco-image-preview-{{ $index }}" src="{{ $exco['image_url'] ?? '' }}" alt="Exco image preview" class="img-thumbnail" style="max-height: 180px; width: auto;">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-success btn-lg">
            <i class="{{ $submitIcon }} mr-1"></i>
            {{ $submitLabel }}
        </button>
        <a href="{{ route('branches.index') }}" class="btn btn-link">Back to branches</a>
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

            function buildDesignationOptions() {
                let options = '<option value="">Select designation</option>';

                designationOptions.forEach(function (designation) {
                    options += `<option value="${designation.id}">${designation.name}</option>`;
                });

                return options;
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

                    const imageInput = item.querySelector('.custom-file-input');
                    const previewImage = item.querySelector('img[id^="exco-image-preview"]');
                    const previewWrapper = item.querySelector('div[id^="exco-image-preview"][id$="-wrapper"]');

                    if (imageInput && previewImage && previewWrapper) {
                        const previewId = `exco-image-preview-${index}`;
                        imageInput.setAttribute('data-preview-target', previewId);
                        previewImage.id = previewId;
                        previewWrapper.id = `${previewId}-wrapper`;
                    }
                });

                excoList.querySelectorAll('.remove-exco').forEach(function (button) {
                    button.disabled = items.length === 1;
                });
            }

            function wireFileLabels(scope) {
                scope.querySelectorAll('.custom-file-input').forEach(function (input) {
                    if (input.dataset.previewBound === 'true') {
                        return;
                    }

                    input.dataset.previewBound = 'true';
                    input.addEventListener('change', function () {
                        const label = this.nextElementSibling;
                        const fileName = this.files.length ? this.files[0].name : 'Choose file';
                        const previewTarget = this.getAttribute('data-preview-target');
                        const previewImage = previewTarget ? document.getElementById(previewTarget) : null;
                        const previewWrapper = previewTarget ? document.getElementById(`${previewTarget}-wrapper`) : null;

                        if (label) {
                            label.textContent = fileName;
                        }

                        if (!previewImage || !previewWrapper) {
                            return;
                        }

                        if (!this.files.length || !this.files[0].type.startsWith('image/')) {
                            previewImage.src = '';
                            previewWrapper.classList.add('d-none');
                            return;
                        }

                        const objectUrl = URL.createObjectURL(this.files[0]);
                        previewImage.src = objectUrl;
                        previewWrapper.classList.remove('d-none');
                        previewImage.onload = function () {
                            URL.revokeObjectURL(objectUrl);
                        };
                    });
                });
            }

            addButton.addEventListener('click', function () {
                const wrapper = document.createElement('div');
                wrapper.className = 'border rounded p-3 mb-3 exco-item';
                wrapper.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Exco #<span class="exco-number"></span></h5>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-exco">Remove</button>
                    </div>
                    <input type="hidden" name="excos[0][user_id]" value="">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>First name</label>
                            <input type="text" name="excos[0][first_name]" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Last name</label>
                            <input type="text" name="excos[0][last_name]" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Phone</label>
                            <input type="text" name="excos[0][phone]" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Designation</label>
                            <select name="excos[0][designation_id]" class="form-control" required>
                                ${buildDesignationOptions()}
                            </select>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Exco image</label>
                        <div class="custom-file">
                            <input type="file" name="excos[0][image]" class="custom-file-input" accept="image/*" data-preview-target="exco-image-preview-new">
                            <label class="custom-file-label">Choose exco image</label>
                        </div>
                        <div class="mt-3 d-none" id="exco-image-preview-new-wrapper">
                            <div class="small text-muted mb-2">Selected exco image preview</div>
                            <img id="exco-image-preview-new" src="" alt="Exco image preview" class="img-thumbnail" style="max-height: 180px; width: auto;">
                        </div>
                    </div>
                `;

                excoList.appendChild(wrapper);
                wireFileLabels(wrapper);
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

            wireFileLabels(document);
            refreshExcoState();
        })();
    </script>
@endpush
