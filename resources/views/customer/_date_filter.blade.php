<div class="card customer-card mb-3">
    <form method="GET" action="{{ $action }}">
        <div class="card-body">
            <div class="row">
                <div class="col-md-5">
                    <div class="form-group mb-md-0">
                        <label for="{{ $prefix }}_start_date">Start Date</label>
                        <input
                            type="date"
                            name="start_date"
                            id="{{ $prefix }}_start_date"
                            class="form-control"
                            value="{{ $filters['start_date'] ?? '' }}"
                        >
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group mb-md-0">
                        <label for="{{ $prefix }}_end_date">End Date</label>
                        <input
                            type="date"
                            name="end_date"
                            id="{{ $prefix }}_end_date"
                            class="form-control"
                            value="{{ $filters['end_date'] ?? '' }}"
                        >
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                </div>
            </div>
        </div>
        <div class="card-footer text-right">
            <a href="{{ $action }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>
