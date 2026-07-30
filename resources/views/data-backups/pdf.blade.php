<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 13px; margin: 0 0 8px; }
        .meta { margin-bottom: 16px; color: #555; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #bbb; padding: 3px; word-wrap: break-word; }
        th { background: #e9ecef; }
        .table-section { page-break-after: always; }
        .empty { padding: 12px; border: 1px solid #bbb; }
    </style>
</head>
<body>
<h1>Database Backup</h1>
<div class="meta">Generated {{ now()->format('d M Y, h:i:s A') }}</div>

@foreach ($tables as $table)
    <section class="table-section">
        <h2>{{ str_replace('_', ' ', strtoupper($table['name'])) }} ({{ $table['rows']->count() }} rows)</h2>
        @if ($table['rows']->isEmpty())
            <div class="empty">No records.</div>
        @else
            <table>
                <thead>
                <tr>
                    @foreach ($table['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($table['rows'] as $row)
                    <tr>
                        @foreach ($table['columns'] as $column)
                            <td>{{ is_scalar($row->{$column}) || $row->{$column} === null ? $row->{$column} : json_encode($row->{$column}) }}</td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endforeach
</body>
</html>
