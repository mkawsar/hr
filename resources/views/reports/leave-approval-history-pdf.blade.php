<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Approval History Report - {{ $start_date }} to {{ $end_date }}</title>
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
            font-size: 11px;
        }
        
        .applications-table th, .applications-table td {
            border: 1px solid #ddd;
            padding: 5px;
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
        
        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }
        
        .processing-time-fast {
            color: #28a745;
            font-weight: bold;
        }
        
        .processing-time-medium {
            color: #ffc107;
            font-weight: bold;
        }
        
        .processing-time-slow {
            color: #dc3545;
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
        <h1>Leave Approval History Report</h1>
        <p>Date Range: {{ $start_date }} to {{ $end_date }}</p>
        <p>Approver: {{ $approver }}</p>
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <div class="summary-cards">
        <div class="summary-card">
            <h3>Total Processed</h3>
            <div class="value">{{ $statistics['total_processed'] }}</div>
        </div>
        <div class="summary-card">
            <h3>Approved</h3>
            <div class="value">{{ $statistics['approved_count'] }}</div>
        </div>
        <div class="summary-card">
            <h3>Rejected</h3>
            <div class="value">{{ $statistics['rejected_count'] }}</div>
        </div>
        <div class="summary-card">
            <h3>Avg Processing Time</h3>
            <div class="value">{{ round($statistics['average_processing_time_hours'], 1) }}h</div>
        </div>
    </div>

    <div class="statistics-section">
        <h2>Statistics by Approver</h2>
        <table class="statistics-table">
            <thead>
                <tr>
                    <th>Approver</th>
                    <th>Total Processed</th>
                    <th>Approved</th>
                    <th>Rejected</th>
                    <th>Approval Rate</th>
                    <th>Avg Processing Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statistics['by_approver'] as $approver => $stats)
                    <tr>
                        <td>{{ $stats['approver'] }}</td>
                        <td>{{ $stats['total_processed'] }}</td>
                        <td>{{ $stats['approved'] }}</td>
                        <td>{{ $stats['rejected'] }}</td>
                        <td>{{ $stats['approval_rate'] }}%</td>
                        <td>{{ round($stats['average_processing_time'], 1) }}h</td>
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
                    <th>Total Processed</th>
                    <th>Approved</th>
                    <th>Rejected</th>
                    <th>Approval Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statistics['by_department'] as $department => $stats)
                    <tr>
                        <td>{{ $stats['department'] }}</td>
                        <td>{{ $stats['total_processed'] }}</td>
                        <td>{{ $stats['approved'] }}</td>
                        <td>{{ $stats['rejected'] }}</td>
                        <td>{{ $stats['approval_rate'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="statistics-section">
        <h2>Approval History</h2>
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
                        <th>Approved At</th>
                        <th>Processing Time</th>
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
                            <td>{{ $application['approved_at'] }}</td>
                            <td class="
                                @if($application['processing_time_hours'] <= 24) processing-time-fast
                                @elseif($application['processing_time_hours'] <= 72) processing-time-medium
                                @else processing-time-slow
                                @endif
                            ">{{ $application['processing_time_hours'] }}h</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">
                <p>No approval history found for the selected criteria.</p>
            </div>
        @endif
    </div>

    <div class="footer">
        <p>This report was generated automatically by the HR Management System</p>
        <p>For any queries, please contact the HR department</p>
    </div>
</body>
</html>
