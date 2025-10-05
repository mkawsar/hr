<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yearly Report {{ $year }} - {{ ucfirst($reportType) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
        }
        
        .header h2 {
            color: #6b7280;
            margin: 5px 0 0 0;
            font-size: 16px;
            font-weight: normal;
        }
        
        .report-info {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .report-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .report-info td {
            padding: 5px 10px;
            border: none;
        }
        
        .report-info td:first-child {
            font-weight: bold;
            width: 150px;
        }
        
        .summary-cards {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .summary-card {
            background-color: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            min-width: 150px;
            margin: 5px;
        }
        
        .summary-card h3 {
            margin: 0 0 10px 0;
            color: #374151;
            font-size: 14px;
        }
        
        .summary-card .value {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .data-table th,
        .data-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }
        
        .data-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .performance-excellent { color: #059669; font-weight: bold; }
        .performance-good { color: #0891b2; font-weight: bold; }
        .performance-average { color: #d97706; font-weight: bold; }
        .performance-below { color: #dc2626; font-weight: bold; }
        .performance-poor { color: #dc2626; font-weight: bold; }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Yearly Attendance & Leave Report</h1>
        <h2>{{ ucfirst($reportType) }} Report for {{ $year }}</h2>
    </div>
    
    <div class="report-info">
        <table>
            <tr>
                <td>Report Type:</td>
                <td>{{ ucfirst($reportType) }} Report</td>
            </tr>
            <tr>
                <td>Year:</td>
                <td>{{ $year }}</td>
            </tr>
            <tr>
                <td>Generated On:</td>
                <td>{{ $generatedAt->format('F j, Y \a\t g:i A') }}</td>
            </tr>
            <tr>
                <td>Total Records:</td>
                <td>{{ $data->count() }}</td>
            </tr>
        </table>
    </div>
    
    @if($reportType === 'individual')
        <div class="summary-cards">
            <div class="summary-card">
                <h3>Total Employees</h3>
                <div class="value">{{ $data->count() }}</div>
            </div>
            <div class="summary-card">
                <h3>Avg Attendance Rate</h3>
                <div class="value">{{ number_format($data->avg('summary.attendance_rate'), 1) }}%</div>
            </div>
            <div class="summary-card">
                <h3>Avg Punctuality Score</h3>
                <div class="value">{{ number_format($data->avg('summary.punctuality_score'), 1) }}/100</div>
            </div>
            <div class="summary-card">
                <h3>Total Absent Days</h3>
                <div class="value">{{ $data->sum('attendance.absent') }}</div>
            </div>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Present Days</th>
                    <th>Absent Days</th>
                    <th>Late Days</th>
                    <th>Leave Days</th>
                    <th>Attendance Rate</th>
                    <th>Punctuality Score</th>
                    <th>Performance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                <tr>
                    <td>{{ $row['user']['employee_id'] }}</td>
                    <td>{{ $row['user']['name'] }}</td>
                    <td>{{ $row['user']['department'] ?? 'N/A' }}</td>
                    <td>{{ $row['attendance']['present'] }}</td>
                    <td>{{ $row['attendance']['absent'] }}</td>
                    <td>{{ $row['attendance']['late'] }}</td>
                    <td>{{ $row['leave']['total_leave_days'] }}</td>
                    <td>{{ $row['summary']['attendance_rate'] }}%</td>
                    <td>{{ $row['summary']['punctuality_score'] }}/100</td>
                    <td class="performance-{{ strtolower(str_replace(' ', '-', $row['summary']['overall_performance'])) }}">
                        {{ $row['summary']['overall_performance'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
    @elseif($reportType === 'department')
        <div class="summary-cards">
            <div class="summary-card">
                <h3>Total Departments</h3>
                <div class="value">{{ $data->count() }}</div>
            </div>
            <div class="summary-card">
                <h3>Total Employees</h3>
                <div class="value">{{ $data->sum('total_users') }}</div>
            </div>
            <div class="summary-card">
                <h3>Avg Department Performance</h3>
                <div class="value">{{ number_format($data->avg('average_attendance_rate'), 1) }}%</div>
            </div>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Total Employees</th>
                    <th>Average Attendance Rate</th>
                    <th>Average Punctuality Score</th>
                    <th>Total Absent Days</th>
                    <th>Total Leave Days</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                <tr>
                    <td>{{ $row['department']['name'] }}</td>
                    <td>{{ $row['total_users'] }}</td>
                    <td>{{ $row['average_attendance_rate'] }}%</td>
                    <td>{{ $row['average_punctuality_score'] }}/100</td>
                    <td>{{ $row['total_absent_days'] }}</td>
                    <td>{{ $row['total_leave_days'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                <tr>
                    <td>{{ $row['metric'] }}</td>
                    <td>{{ $row['value'] }}</td>
                    <td>{{ $row['description'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    <div class="footer">
        <p>This report was generated automatically by the HR Management System</p>
        <p>For any questions or clarifications, please contact the HR department</p>
    </div>
</body>
</html>
