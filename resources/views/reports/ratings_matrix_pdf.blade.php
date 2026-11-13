<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 9pt;
            color: #111;
            margin: 15px;
        }
        .header {
            margin-bottom: 15px;
            border-bottom: 2px solid #222;
            padding-bottom: 8px;
        }
        .title {
            font-size: 14pt;
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
            padding: 5px 6px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 8pt;
            text-transform: uppercase;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .summary-row {
            font-weight: bold;
            background-color: #f9fafb;
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
                <th style="width: 12%;">Area</th>
                <th style="width: 10%;">Store Code</th>
                <th style="width: 30%;">Store Name</th>
                <th class="text-right" style="width: 12%;">Week 1 (1-7)</th>
                <th class="text-right" style="width: 12%;">Week 2 (8-14)</th>
                <th class="text-right" style="width: 12%;">Week 3 (15-21)</th>
                <th class="text-right" style="width: 12%;">Week 4 (22-End)</th>
                <th class="text-right" style="width: 12%;">Month Avg</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['rows'] as $r)
                <tr>
                    <td>{{ $r['area_name'] }}</td>
                    <td>{{ $r['store_code'] }}</td>
                    <td>{{ $r['store_name'] }}</td>
                    <td class="text-right">{{ $r['week_1'] !== null ? number_format($r['week_1'], 2) . '%' : '-' }}</td>
                    <td class="text-right">{{ $r['week_2'] !== null ? number_format($r['week_2'], 2) . '%' : '-' }}</td>
                    <td class="text-right">{{ $r['week_3'] !== null ? number_format($r['week_3'], 2) . '%' : '-' }}</td>
                    <td class="text-right">{{ $r['week_4'] !== null ? number_format($r['week_4'], 2) . '%' : '-' }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ $r['month_average'] !== null ? number_format($r['month_average'], 2) . '%' : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px;">No ratings records found for this period.</td>
                </tr>
            @endforelse
            @if(!empty($data['rows']))
                <tr class="summary-row">
                    <td colspan="3">AVERAGE</td>
                    <td class="text-right">{{ $data['summary']['week_1_avg'] !== null ? number_format($data['summary']['week_1_avg'], 2) . '%' : '-' }}</td>
                    <td class="text-right">{{ $data['summary']['week_2_avg'] !== null ? number_format($data['summary']['week_2_avg'], 2) . '%' : '-' }}</td>
                    <td class="text-right">{{ $data['summary']['week_3_avg'] !== null ? number_format($data['summary']['week_3_avg'], 2) . '%' : '-' }}</td>
                    <td class="text-right">{{ $data['summary']['week_4_avg'] !== null ? number_format($data['summary']['week_4_avg'], 2) . '%' : '-' }}</td>
                    <td class="text-right">-</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>

