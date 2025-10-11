<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Summary Report - {{ $start_date }} to {{ $end_date }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #366092;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #366092;
            margin: 0;
            font-size: 24px;
        }
        
        .header p {
            margin: 5px 0;
            color: #666;
        }
        
        .summary-cards {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .summary-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            min-width: 150px;
            margin: 5px;
        }
        
        .summary-card h3 {
            margin: 0 0 10px 0;
            color: #366092;
            font-size: 14px;
        }
        
        .summary-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .statistics-section {
            margin-bottom: 30px;
        }
        
        .statistics-section h2 {
            color: #366092;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        
        .statistics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .statistics-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .statistics-table th, .statistics-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .statistics-table th {
            background-color: #366092;
            color: white;
            font-weight: bold;
        }
        
        .statistics-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .applications-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        
        .applications-table th, .applications-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        
        .applications-table th {
            background-color: #366092;
            color: white;
            font-weight: bold;
        }
        
        .applications-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .status-approved {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        
        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }
        
        .status-cancelled {
            color: #6c757d;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Leave Summary Report</h1>
        <p>Date Range: {{ $start_date }} to {{ $end_date }}</p>
        <p>Department: {{ $department }}</p>
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <div class="summary-cards">
        <div class="summary-card">
            <h3>Total Applications</h3>
            <div class="value">{{ $statistics['total_applications'] }}</div>
        </div>
        <div class="summary-card">
            <h3>Approved</h3>
            <div class="value">{{ $statistics['approved_applications'] }}</div>
        </div>
        <div class="summary-card">
            <h3>Pending</h3>
            <div class="value">{{ $statistics['pending_applications'] }}</div>
        </div>
        <div class="summary-card">
            <h3>Rejected</h3>
            <div class="value">{{ $statistics['rejected_applications'] }}</div>
        </div>
        <div class="summary-card">
            <h3>Total Leave Days</h3>
            <div class="value">{{ $statistics['total_leave_days'] }}</div>
        </div>
    </div>

    <div class="statistics-section">
        <h2>Statistics by Leave Type</h2>
        <table class="statistics-table">
            <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Applications</th>
                    <th>Total Days</th>
                    <th>Approved Days</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statistics['by_leave_type'] as $leaveType => $stats)
                    <tr>
                        <td>{{ $leaveType }}</td>
                        <td>{{ $stats['count'] }}</td>
                        <td>{{ $stats['total_days'] }}</td>
                        <td>{{ $stats['approved_days'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="statistics-section">
        <h2>Statistics by Department</h2>
        <table class="statistics-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Applications</th>
                    <th>Total Days</th>
                    <th>Approved Days</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statistics['by_department'] as $department => $stats)
                    <tr>
                        <td>{{ $department }}</td>
                        <td>{{ $stats['count'] }}</td>
                        <td>{{ $stats['total_days'] }}</td>
                        <td>{{ $stats['approved_days'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="statistics-section">
        <h2>Leave Applications</h2>
        @if(count($data) > 0)
            <table class="applications-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Applied At</th>
                        <th>Approved By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $application)
                        <tr>
                            <td>{{ $application['id'] }}</td>
                            <td>{{ $application['employee_name'] }}<br><small>{{ $application['employee_id'] }}</small></td>
                            <td>{{ $application['department'] }}</td>
                            <td>{{ $application['leave_type'] }}</td>
                            <td>{{ $application['start_date'] }}</td>
                            <td>{{ $application['end_date'] }}</td>
                            <td>{{ $application['days_count'] }}</td>
                            <td class="status-{{ strtolower($application['status']) }}">{{ $application['status'] }}</td>
                            <td>{{ $application['applied_at'] }}</td>
                            <td>{{ $application['approved_by'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">
                <p>No leave applications found for the selected criteria.</p>
            </div>
        @endif
    </div>

    <div class="footer">
        <p>This report was generated automatically by the HR Management System</p>
        <p>For any queries, please contact the HR department</p>
    </div>
</body>
</html>
