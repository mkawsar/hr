<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Analysis Report - {{ $data['year'] }}</title>
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
        
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .section h2 {
            color: #366092;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #366092;
            color: white;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .utilization-high {
            color: #28a745;
            font-weight: bold;
        }
        
        .utilization-medium {
            color: #ffc107;
            font-weight: bold;
        }
        
        .utilization-low {
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
        <h1>Leave Analysis Report</h1>
        <p>Year: {{ $data['year'] }}</p>
        <p>Department: {{ $department }}</p>
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <div class="summary-cards">
        <div class="summary-card">
            <h3>Total Employees</h3>
            <div class="value">{{ $data['total_employees'] }}</div>
        </div>
        <div class="summary-card">
            <h3>Total Leave Types</h3>
            <div class="value">{{ $data['total_leave_types'] }}</div>
        </div>
        <div class="summary-card">
            <h3>Total Departments</h3>
            <div class="value">{{ $data['total_departments'] }}</div>
        </div>
        <div class="summary-card">
            <h3>Overall Utilization</h3>
            <div class="value">{{ $data['summary']['overall_utilization_rate'] }}%</div>
        </div>
        <div class="summary-card">
            <h3>Approval Rate</h3>
            <div class="value">{{ $data['summary']['approval_rate'] }}%</div>
        </div>
    </div>

    <div class="section">
        <h2>Summary Statistics</h2>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Allocated Days</td>
                    <td>{{ $data['summary']['total_allocated_days'] }}</td>
                    <td>Total leave days allocated to all employees</td>
                </tr>
                <tr>
                    <td>Total Consumed Days</td>
                    <td>{{ $data['summary']['total_consumed_days'] }}</td>
                    <td>Total leave days consumed by all employees</td>
                </tr>
                <tr>
                    <td>Total Remaining Days</td>
                    <td>{{ $data['summary']['total_remaining_days'] }}</td>
                    <td>Total leave days remaining for all employees</td>
                </tr>
                <tr>
                    <td>Overall Utilization Rate</td>
                    <td>{{ $data['summary']['overall_utilization_rate'] }}%</td>
                    <td>Percentage of allocated leave days consumed</td>
                </tr>
                <tr>
                    <td>Total Applications</td>
                    <td>{{ $data['summary']['total_applications'] }}</td>
                    <td>Total number of leave applications</td>
                </tr>
                <tr>
                    <td>Approval Rate</td>
                    <td>{{ $data['summary']['approval_rate'] }}%</td>
                    <td>Percentage of applications approved</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Leave Type Analysis</h2>
        @if(count($data['leave_type_analysis']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Total Allocated</th>
                        <th>Total Consumed</th>
                        <th>Total Balance</th>
                        <th>Carry Forward</th>
                        <th>Utilization Rate</th>
                        <th>Applications</th>
                        <th>Approved</th>
                        <th>Pending</th>
                        <th>Rejected</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['leave_type_analysis'] as $type)
                        <tr>
                            <td>{{ $type['leave_type'] }}</td>
                            <td>{{ $type['total_allocated'] }}</td>
                            <td>{{ $type['total_consumed'] }}</td>
                            <td>{{ $type['total_balance'] }}</td>
                            <td>{{ $type['total_carry_forward'] }}</td>
                            <td class="
                                @if($type['utilization_rate'] >= 80) utilization-high
                                @elseif($type['utilization_rate'] >= 50) utilization-medium
                                @else utilization-low
                                @endif
                            ">{{ $type['utilization_rate'] }}%</td>
                            <td>{{ $type['applications_count'] }}</td>
                            <td>{{ $type['approved_applications'] }}</td>
                            <td>{{ $type['pending_applications'] }}</td>
                            <td>{{ $type['rejected_applications'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">
                <p>No leave type analysis data available.</p>
            </div>
        @endif
    </div>

    <div class="section">
        <h2>Department Analysis</h2>
        @if(count($data['department_analysis']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Total Employees</th>
                        <th>Total Allocated</th>
                        <th>Total Consumed</th>
                        <th>Total Balance</th>
                        <th>Average Utilization</th>
                        <th>Applications</th>
                        <th>Approved</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['department_analysis'] as $dept)
                        <tr>
                            <td>{{ $dept['department'] }}</td>
                            <td>{{ $dept['total_employees'] }}</td>
                            <td>{{ $dept['total_allocated'] }}</td>
                            <td>{{ $dept['total_consumed'] }}</td>
                            <td>{{ $dept['total_balance'] }}</td>
                            <td class="
                                @if($dept['average_utilization'] >= 80) utilization-high
                                @elseif($dept['average_utilization'] >= 50) utilization-medium
                                @else utilization-low
                                @endif
                            ">{{ round($dept['average_utilization'], 2) }}%</td>
                            <td>{{ $dept['applications_count'] }}</td>
                            <td>{{ $dept['approved_applications'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">
                <p>No department analysis data available.</p>
            </div>
        @endif
    </div>

    <div class="section">
        <h2>Monthly Trends</h2>
        @if(count($data['monthly_trends']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Applications Count</th>
                        <th>Approved Count</th>
                        <th>Total Days</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['monthly_trends'] as $month)
                        <tr>
                            <td>{{ $month['month'] }}</td>
                            <td>{{ $month['applications_count'] }}</td>
                            <td>{{ $month['approved_count'] }}</td>
                            <td>{{ $month['total_days'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-data">
                <p>No monthly trends data available.</p>
            </div>
        @endif
    </div>

    <div class="footer">
        <p>This report was generated automatically by the HR Management System</p>
        <p>For any queries, please contact the HR department</p>
    </div>
</body>
</html>
