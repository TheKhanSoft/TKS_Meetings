<!DOCTYPE html>
<html>
<head>
    <title>Meeting Types Breakdown</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h1 { font-size: 18px; }
        .meta { font-size: 12px; color: #666; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Meeting Types Breakdown</h1>
    <div class="meta">Date Range: {{ $dateRange }}</div>

    <table>
        <thead>
            <tr>
                <th>Meeting Type</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->meetings_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
