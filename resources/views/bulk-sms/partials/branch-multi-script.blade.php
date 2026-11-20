{{-- Keeps "All accessible branches" and individual branches mutually exclusive in .branch-multi selects. --}}
@push('scripts')
    <script>
        (() => {
            if (! window.jQuery) {
                return;
            }

            window.jQuery('.branch-multi').each(function () {
                const $select = window.jQuery(this);

                $select.on('select2:select', (event) => {
                    const picked = String(event.params.data.id);
                    const values = ($select.val() || []).map(String);
                    const next = picked === 'all' ? ['all'] : values.filter((value) => value !== 'all');

                    if (next.length !== values.length) {
                        $select.val(next).trigger('change');
                    }
                });
            });
        })();
    </script>
@endpush
