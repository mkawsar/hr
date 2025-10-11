<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Leave Balance Report - {{ $year }}</title>
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
        
        .table-container {
            margin-top: 20px;
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
        
        .employee-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .employee-header {
            background-color: #366092;
            color: white;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .employee-header h3 {
            margin: 0;
            font-size: 16px;
        }
        
        .leave-balance-table {
            width: 100%;
            margin-bottom: 15px;
        }
        
        .leave-balance-table th {
            background-color: #5a7ba7;
            font-size: 12px;
        }
        
        .leave-balance-table td {
            font-size: 12px;
            text-align: center;
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
        <h1>Employee Leave Balance Report</h1>
        <p>Year: {{ $year }}</p>
        <p>Department: {{ $department }}</p>
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <div class="summary-cards">
        <div class="summary-card">
            <h3>Total Employees</h3>
            <div class="value">{{ count($data) }}</div>
        </div>
        <div class="summary-card">
            <h3>Total Leave Types</h3>
            <div class="value">{{ collect($data)->pluck('leave_balances')->flatten(1)->groupBy('leave_type')->count() }}</div>
        </div>
        <div class="summary-card">
            <h3>Total Balance</h3>
            <div class="value">{{ collect($data)->sum('total_balance') }}</div>
        </div>
        <div class="summary-card">
            <h3>Total Consumed</h3>
            <div class="value">{{ collect($data)->sum('total_consumed') }}</div>
        </div>
    </div>

    @if(count($data) > 0)
        @foreach($data as $employee)
            <div class="employee-section">
                <div class="employee-header">
                    <h3>{{ $employee['name'] }} ({{ $employee['employee_id'] }}) - {{ $employee['department'] }}</h3>
                    <p>Designation: {{ $employee['designation'] }}</p>
                </div>
                
                <table class="leave-balance-table">
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Leave Code</th>
                            <th>Balance</th>
                            <th>Consumed</th>
                            <th>Accrued</th>
                            <th>Carry Forward</th>
                            <th>Total Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employee['leave_balances'] as $balance)
                            <tr>
                                <td>{{ $balance['leave_type'] }}</td>
                                <td>{{ $balance['leave_code'] }}</td>
                                <td>{{ $balance['balance'] }}</td>
                                <td>{{ $balance['consumed'] }}</td>
                                <td>{{ $balance['accrued'] }}</td>
                                <td>{{ $balance['carry_forward'] }}</td>
                                <td>{{ $balance['total_available'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <div class="no-data">
            <p>No leave balance data found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was generated automatically by the HR Management System</p>
        <p>For any queries, please contact the HR department</p>
    </div>
</body>
</html>
