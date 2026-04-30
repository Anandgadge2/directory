<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans; font-size: 12px; }
        table { width:100%; border-collapse: collapse; }
        th,td { border:1px solid #ccc; padding:6px; }
        th { background:#eee; }
    </style>
</head>
<body>

<h3>Monthly Patrol Report - {{ $year }}</h3>

<table>
<tr>
    <th>Month</th>
    <th>Sessions</th>
    <th>Active Guards</th>
    <th>Distance (KM)</th>
</tr>
@foreach($data as $d)
<tr>
    <td>{{ \Carbon\Carbon::create()->month($d->month)->format('F') }}</td>
    <td>{{ $d->sessions }}</td>
    <td>{{ $d->guards }}</td>
    <td>{{ number_format($d->distance,2) }}</td>
</tr>
@endforeach
</table>

</body>
</html>
