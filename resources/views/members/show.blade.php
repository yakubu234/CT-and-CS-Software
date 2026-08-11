@extends('layouts.admin')

@section('title', $archived ? 'Archived Member' : 'View Member')
@section('page_title', $archived ? 'Archived Member' : 'View Member')

@section('content')
    @php
        $hasMemberPhoto = $member->profile_picture
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($member->profile_picture);
        $memberPhotoUrl = $hasMemberPhoto ? asset('storage/' . $member->profile_picture) : null;
    @endphp
    <div class="row">
        <div class="col-lg-4">
            <div class="card card-outline card-primary">
                <div class="card-body box-profile">
                    <div class="text-center">
                        @if ($hasMemberPhoto)
                            @unless ($archived)
                                <button type="button" class="btn btn-link p-0 border-0" data-toggle="modal" data-target="#member-photo-actions" aria-label="Manage {{ $member->name }}'s photo">
                            @endunless
                            <img class="profile-user-img img-fluid img-circle" src="{{ $memberPhotoUrl }}" alt="{{ $member->name }}" style="width:112px;height:112px;object-fit:cover;">
                            @unless ($archived)
                                </button>
                            @endunless
                        @else
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width:96px;height:96px;">
                                <i class="fas fa-user fa-2x text-muted"></i>
                            </div>
                        @endif
                    </div>

                    @unless ($archived)
                        <div class="text-center mt-2 mb-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#member-photo-actions">
                                <i class="fas fa-camera mr-1"></i> {{ $hasMemberPhoto ? 'Manage Photo' : 'Add Photo' }}
                            </button>
                            @error('photo')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    @endunless

                    <h3 class="profile-username text-center">{{ $member->name }}</h3>
                    <p class="text-muted text-center">{{ $member->display_member_no ?: 'N/A' }}</p>
                    @if ($archived)
                        <p class="text-center">
                            <span class="badge badge-secondary">Archived {{ optional($member->deleted_at)->format('d M Y, h:i A') }}</span>
                        </p>
                    @endif

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item"><b>Email</b> <span class="float-right">{{ $member->email }}</span></li>
                        <li class="list-group-item"><b>Mobile</b> <span class="float-right">{{ $member->detail?->mobile ?: 'N/A' }}</span></li>
                        <li class="list-group-item"><b>Occupation</b> <span class="float-right">{{ $member->detail?->occupation ?: 'N/A' }}</span></li>
                        <li class="list-group-item"><b>Designation</b> <span class="float-right">{{ $member->designation ?: 'Member' }}</span></li>
                    </ul>

                    @if ($archived)
                        <form action="{{ route('members.restore', $member->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success btn-block" onclick="return confirm('Restore this member and the accounts archived with them?')">
                                <i class="fas fa-undo mr-1"></i> Restore Member
                            </button>
                        </form>
                        <a href="{{ route('members.archived') }}" class="btn btn-outline-secondary btn-block">Back to Archived Members</a>
                    @else
                        <a href="{{ route('members.edit', $member) }}" class="btn btn-primary btn-block">Edit Member</a>
                        <a href="{{ route('members.id-card', $member) }}" class="btn btn-outline-primary btn-block">
                            <i class="fas fa-id-card mr-1"></i> View / Download ID Card
                        </a>
                        <a href="#documents" class="btn btn-outline-secondary btn-block">Add Document</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Profile Details</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><strong>City:</strong> {{ $member->detail?->city ?: 'N/A' }}</div>
                        <div class="col-md-6 mb-3"><strong>State:</strong> {{ $member->detail?->state ?: 'N/A' }}</div>
                        <div class="col-md-6 mb-3"><strong>Date of Birth:</strong> {{ optional($member->detail?->date_of_birth)->format('d M Y') ?: 'N/A' }}</div>
                        <div class="col-md-6 mb-3"><strong>Gender:</strong> {{ $member->detail?->gender ?: 'N/A' }}</div>
                        <div class="col-md-6 mb-3"><strong>Address:</strong> {{ $member->detail?->address ?: 'N/A' }}</div>
                        <div class="col-md-4 mb-3"><strong>Account Number:</strong> {{ $member->detail?->account_number ?: 'N/A' }}</div>
                        <div class="col-md-4 mb-3"><strong>Account Name:</strong> {{ $member->detail?->account_name ?: 'N/A' }}</div>
                        <div class="col-md-4 mb-3"><strong>Bank Name:</strong> {{ $member->detail?->bank_name ?: 'N/A' }}</div>
                    </div>

                    @if ($member->signature)
                        <hr>
                        <div>
                            <strong class="d-block mb-2">Signature Preview</strong>
                            <a href="{{ asset('storage/' . $member->signature) }}" target="_blank" rel="noopener noreferrer">
                                <img
                                    src="{{ asset('storage/' . $member->signature) }}"
                                    alt="{{ $member->name }} signature"
                                    class="img-thumbnail"
                                    style="max-width: 260px;"
                                >
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Accounts</h3></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                            <tr>
                                <th>Product</th>
                                <th>Account Number</th>
                                <th>Balance</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($member->savingsAccounts as $account)
                                <tr>
                                    <td>{{ $account->product?->type ?: 'N/A' }}</td>
                                    <td>{{ $account->account_number }}</td>
                                    <td>{{ number_format((float) $account->balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">No accounts found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Loans</h3>
                    @if ($member->loans->isNotEmpty())
                        <span class="badge badge-info">{{ $member->loans->count() }} loan record{{ $member->loans->count() === 1 ? '' : 's' }}</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                            <tr>
                                <th>Loan ID</th>
                                <th>Loan Amount</th>
                                <th>Total Paid</th>
                                <th>Outstanding Balance</th>
                                <th>Status</th>
                                <th style="width: 120px;">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($member->loans as $loan)
                                @php
                                    $loanAmount = (float) ($loan->amount_due ?? $loan->applied_amount ?? 0);
                                    $outstanding = (float) ($loan->balanace ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $loan->loan_id }}</td>
                                    <td>&#8358;{{ number_format($loanAmount, 2) }}</td>
                                    <td>&#8358;{{ number_format((float) ($loan->total_paid ?? 0), 2) }}</td>
                                    <td class="font-weight-bold {{ $outstanding > 0 ? 'text-danger' : 'text-success' }}">
                                        &#8358;{{ number_format($outstanding, 2) }}
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $outstanding > 0 ? 'success' : 'secondary' }}">
                                            {{ $outstanding > 0 ? 'Active' : 'Closed' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('loans.show', $loan) }}" class="btn btn-sm btn-outline-info">View Loan</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No loan record found for this member.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card" id="documents">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Documents</h3>
                    <span class="badge badge-info">{{ $member->documents->count() }}</span>
                </div>
                <div class="card-body">
                    @unless ($archived)
                    <div class="border rounded p-3 mb-4 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Upload Member Documents</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-document-row">
                                <i class="fas fa-plus mr-1"></i> Add Another
                            </button>
                        </div>
                        <form action="{{ route('members.documents.store', $member) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @php($oldDocuments = old('documents', [['document_type' => '', 'name' => '']]))
                            <div id="document-upload-rows">
                                @foreach ($oldDocuments as $index => $oldDocument)
                                    <div class="document-upload-row border rounded p-3 mb-3 bg-white" data-document-row>
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Document <span data-document-number>{{ $index + 1 }}</span></strong>
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0" data-remove-document-row aria-label="Remove document row">
                                                <i class="fas fa-times"></i> Remove
                                            </button>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 form-group">
                                                <label>Document Type <span class="field-label-meta required">Required</span></label>
                                                <select name="documents[{{ $index }}][document_type]" class="form-control @error("documents.{$index}.document_type") is-invalid @enderror" data-document-type>
                                                    <option value="">Select type</option>
                                                    @foreach (['Membership Form', 'Nominee Form', 'National ID', 'Voter’s Card', 'Driver’s Licence', 'Passport', 'Utility Bill', 'Other Supporting Document'] as $type)
                                                        <option value="{{ $type }}" @selected(($oldDocument['document_type'] ?? '') === $type)>{{ $type }}</option>
                                                    @endforeach
                                                </select>
                                                @error("documents.{$index}.document_type")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label>Document Name <span class="field-label-meta required">Required</span></label>
                                                <input type="text" name="documents[{{ $index }}][name]" value="{{ $oldDocument['name'] ?? '' }}" class="form-control @error("documents.{$index}.name") is-invalid @enderror" placeholder="e.g. 2026 Membership Form" data-document-name>
                                                @error("documents.{$index}.name")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label>Document File <span class="field-label-meta required">Required</span></label>
                                                <input type="file" name="documents[{{ $index }}][file]" class="form-control-file @error("documents.{$index}.file") is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                                                @error("documents.{$index}.file")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <small class="form-text text-muted mb-3">PDF, images, Word documents; maximum 10 MB each and 10 files per upload.</small>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-1"></i> Upload All Documents</button>
                        </form>
                    </div>
                    @endunless

                    @if ($member->documents->isEmpty())
                        <p class="text-muted mb-0">No documents uploaded for this member yet.</p>
                    @else
                        <div class="list-group">
                            @foreach ($member->documents as $document)
                                <div class="list-group-item">
                                    <div class="d-md-flex justify-content-between align-items-center">
                                        <div class="mb-2 mb-md-0">
                                            <strong class="d-block">{{ $document->name }}</strong>
                                            <span class="badge badge-light border">{{ $document->document_type ?: 'Unclassified' }}</span>
                                            <small class="text-muted ml-1">Uploaded {{ $document->created_at->format('d M Y') }}</small>
                                        </div>
                                        <div class="d-flex flex-wrap" style="gap:.35rem;">
                                            <a href="{{ route('members.documents.view', [$member, $document]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye mr-1"></i> View</a>
                                            <a href="{{ route('members.documents.view', [$member, $document, 'download' => 1]) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download mr-1"></i> Download</a>
                                            @unless ($archived)
                                                <button type="button" class="btn btn-sm btn-outline-info" data-toggle="collapse" data-target="#replace-document-{{ $document->id }}" aria-expanded="false">
                                                    <i class="fas fa-sync-alt mr-1"></i> Replace / Update
                                                </button>
                                                <form action="{{ route('members.documents.destroy', [$member, $document]) }}" method="POST" onsubmit="return confirm('Permanently delete this document? This cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash mr-1"></i> Delete</button>
                                                </form>
                                            @endunless
                                        </div>
                                    </div>
                                    @unless ($archived)
                                        <div class="collapse mt-3" id="replace-document-{{ $document->id }}">
                                            <form action="{{ route('members.documents.update', [$member, $document]) }}" method="POST" enctype="multipart/form-data" class="border rounded bg-light p-3">
                                                @csrf
                                                @method('PUT')
                                                <div class="row">
                                                    <div class="col-md-4 form-group mb-md-0">
                                                        <label>Document Type</label>
                                                        <select name="document_type" class="form-control" required>
                                                            @foreach (['Membership Form', 'Nominee Form', 'National ID', 'Voter’s Card', 'Driver’s Licence', 'Passport', 'Utility Bill', 'Other Supporting Document'] as $type)
                                                                <option value="{{ $type }}" @selected($document->document_type === $type)>{{ $type }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 form-group mb-md-0">
                                                        <label>Document Name</label>
                                                        <input type="text" name="name" value="{{ $document->name }}" class="form-control" required maxlength="191">
                                                    </div>
                                                    <div class="col-md-4 form-group mb-0">
                                                        <label>Replacement File <small class="text-muted">(optional)</small></label>
                                                        <input type="file" name="document" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                                                        <button type="submit" class="btn btn-info btn-sm mt-2">Save Changes</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    @endunless
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Custom Fields</h3></div>
                <div class="card-body">
                    @php($customFields = collect($member->detail?->custom_fields ?? []))
                    @if ($customFields->isEmpty())
                        <p class="text-muted mb-0">No custom field values saved for this member.</p>
                    @else
                        <div class="row">
                            @foreach ($customFields as $field)
                                <div class="col-md-6 mb-3">
                                    <strong>{{ $field['label'] ?? 'Custom Field' }}</strong>
                                    <div class="mt-1">
                                        @if (($field['type'] ?? null) === 'file' && ! empty($field['value']))
                                            <a href="{{ asset('storage/' . $field['value']) }}" target="_blank">View File</a>
                                        @else
                                            {{ $field['value'] ?? 'N/A' }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @unless ($archived)
        <div class="modal fade" id="member-photo-actions" tabindex="-1" role="dialog" aria-labelledby="member-photo-actions-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="member-photo-actions-title">{{ $hasMemberPhoto ? 'Manage Member Photo' : 'Add Member Photo' }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @if ($hasMemberPhoto)
                            <div class="text-center mb-4">
                                <button type="button" class="btn btn-link p-0" id="view-member-photo">
                                    <img src="{{ $memberPhotoUrl }}" alt="{{ $member->name }}" class="img-thumbnail" style="width:160px;height:160px;object-fit:cover;">
                                    <span class="d-block mt-2"><i class="fas fa-search-plus mr-1"></i> View Photo</span>
                                </button>
                            </div>
                        @endif

                        <form action="{{ route('members.photo.update', $member) }}" method="POST" enctype="multipart/form-data" class="mb-3">
                            @csrf
                            @method('PUT')
                            <label for="member-camera-photo" class="btn btn-primary btn-block mb-0">
                                <i class="fas fa-camera mr-1"></i> Take a Picture
                            </label>
                            <input type="file" name="photo" id="member-camera-photo" accept="image/jpeg,image/png,image/webp" capture="user" class="d-none" data-photo-submit>
                            <small class="form-text text-muted text-center">Opens the device camera when supported.</small>
                        </form>

                        <form action="{{ route('members.photo.update', $member) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <label for="member-upload-photo" class="btn btn-outline-primary btn-block mb-0">
                                <i class="fas fa-upload mr-1"></i> {{ $hasMemberPhoto ? 'Replace Photo from Device' : 'Upload Photo from Device' }}
                            </label>
                            <input type="file" name="photo" id="member-upload-photo" accept="image/jpeg,image/png,image/webp" class="d-none" data-photo-submit>
                            <small class="form-text text-muted text-center">JPEG, PNG or WebP, up to 5 MB.</small>
                        </form>
                    </div>
                    @if ($hasMemberPhoto)
                        <div class="modal-footer justify-content-between">
                            <form action="{{ route('members.photo.destroy', $member) }}" method="POST" onsubmit="return confirm('Remove this member photo and restore the default avatar?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i> Remove Photo</button>
                            </form>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($hasMemberPhoto)
            <div class="modal fade" id="member-photo-view" tabindex="-1" role="dialog" aria-labelledby="member-photo-view-title" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content bg-dark">
                        <div class="modal-header border-0 text-white">
                            <h5 class="modal-title" id="member-photo-view-title">{{ $member->name }}</h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body text-center pt-0">
                            <img src="{{ $memberPhotoUrl }}" alt="{{ $member->name }}" class="img-fluid" style="max-height:75vh;object-fit:contain;">
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endunless
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-photo-submit]').forEach(function (input) {
            input.addEventListener('change', function () {
                if (this.files && this.files.length) {
                    this.form.submit();
                }
            });
        });

        @if (! $archived && $hasMemberPhoto)
            document.getElementById('view-member-photo').addEventListener('click', function () {
                $('#member-photo-actions').modal('hide');
                $('#member-photo-actions').one('hidden.bs.modal', function () {
                    $('#member-photo-view').modal('show');
                });
            });
        @endif

        @error('photo')
            $('#member-photo-actions').modal('show');
        @enderror

        @unless ($archived)
            (function () {
                const rowsContainer = document.getElementById('document-upload-rows');
                const addButton = document.getElementById('add-document-row');
                if (! rowsContainer || ! addButton) return;

                const documentTypes = ['Membership Form', 'Nominee Form', 'National ID', 'Voter’s Card', 'Driver’s Licence', 'Passport', 'Utility Bill', 'Other Supporting Document'];

                function rowMarkup() {
                    const options = documentTypes.map(type => `<option value="${type}">${type}</option>`).join('');
                    return `<div class="document-upload-row border rounded p-3 mb-3 bg-white" data-document-row>
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Document <span data-document-number></span></strong>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0" data-remove-document-row aria-label="Remove document row"><i class="fas fa-times"></i> Remove</button>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group"><label>Document Type <span class="field-label-meta required">Required</span></label><select class="form-control" data-document-type required><option value="">Select type</option>${options}</select></div>
                            <div class="col-md-4 form-group"><label>Document Name <span class="field-label-meta required">Required</span></label><input type="text" class="form-control" placeholder="e.g. 2026 Membership Form" data-document-name required maxlength="191"></div>
                            <div class="col-md-4 form-group"><label>Document File <span class="field-label-meta required">Required</span></label><input type="file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" data-document-file required></div>
                        </div>
                    </div>`;
                }

                function reindexRows() {
                    const rows = rowsContainer.querySelectorAll('[data-document-row]');
                    rows.forEach(function (row, index) {
                        row.querySelector('[data-document-number]').textContent = index + 1;
                        row.querySelector('[data-document-type]').name = `documents[${index}][document_type]`;
                        row.querySelector('[data-document-name]').name = `documents[${index}][name]`;
                        const file = row.querySelector('[data-document-file]') || row.querySelector('input[type="file"]');
                        file.name = `documents[${index}][file]`;
                        row.querySelector('[data-remove-document-row]').classList.toggle('d-none', rows.length === 1);
                    });
                    addButton.disabled = rows.length >= 10;
                }

                addButton.addEventListener('click', function () {
                    if (rowsContainer.querySelectorAll('[data-document-row]').length >= 10) return;
                    rowsContainer.insertAdjacentHTML('beforeend', rowMarkup());
                    reindexRows();
                });

                rowsContainer.addEventListener('click', function (event) {
                    const removeButton = event.target.closest('[data-remove-document-row]');
                    if (! removeButton) return;
                    removeButton.closest('[data-document-row]').remove();
                    reindexRows();
                });

                rowsContainer.addEventListener('change', function (event) {
                    if (! event.target.matches('[data-document-type]')) return;
                    const nameInput = event.target.closest('[data-document-row]').querySelector('[data-document-name]');
                    if (! nameInput.value.trim()) nameInput.value = event.target.value;
                });

                reindexRows();
            })();
        @endunless
    </script>
@endpush
