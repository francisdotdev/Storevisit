<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 8pt;
            color: #111;
            margin: 15px;
        }
        .header {
            margin-bottom: 15px;
            border-bottom: 2px solid #222;
            padding-bottom: 8px;
        }
        .title {
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 8pt;
            color: #555;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 4px 5px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 7.5pt;
            text-transform: uppercase;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">{{ $title }}</h1>
        <p class="subtitle">{{ $subtitle }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">Code</th>
                <th style="width: 9%;">Scheduled</th>
                <th style="width: 10%;">Window</th>
                <th style="width: 10%;">Area</th>
                <th style="width: 18%;">Store</th>
                <th style="width: 8%;">Type</th>
                <th style="width: 13%;">Supervisor</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 14%;">Actual Started</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r['schedule_code'] }}</td>
                    <td>{{ $r['scheduled_date'] }}</td>
                    <td>{{ $r['time_window'] }}</td>
                    <td>{{ $r['area_name'] }}</td>
                    <td>{{ $r['store_name'] }}</td>
                    <td>{{ $r['audit_type'] }}</td>
                    <td>{{ $r['supervisor_name'] }}</td>
                    <td>{{ $r['status'] }}</td>
                    <td>{{ $r['actual_started'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 15px;">No calendar visit records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

