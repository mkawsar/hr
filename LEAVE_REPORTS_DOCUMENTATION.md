# Leave Reports System Documentation

## Overview

The Leave Reports System provides comprehensive reporting capabilities for leave management with download functionality in both Excel and PDF formats. The system includes four main report types with filtering options and export capabilities.

## Features

### 📊 **Report Types**

#### 1. **Employee Leave Balance Report**
- Shows current leave balances for all employees
- Displays balance, consumed, accrued, and carry-forward days by leave type
- Filterable by year and department
- Useful for HR to track leave entitlements and usage

#### 2. **Leave Summary Report**
- Shows leave applications within a specified date range
- Includes application details, status, and approval information
- Filterable by date range, department, and status
- Provides statistics by leave type and department

#### 3. **Leave Analysis Report**
- Comprehensive analysis of leave patterns and utilization
- Shows utilization rates by leave type and department
- Includes monthly trends and performance metrics
- Filterable by year and department

#### 4. **Leave Approval History Report**
- Shows approval history with processing times
- Tracks approver performance and efficiency
- Filterable by date range, approver, and status
- Useful for monitoring approval workflows

### 📈 **Key Metrics**

#### **Leave Balance Metrics:**
- **Balance**: Remaining leave days available
- **Consumed**: Leave days already used
- **Accrued**: Leave days allocated for the year
- **Carry Forward**: Leave days carried from previous year
- **Total Available**: Balance + Carry Forward

#### **Summary Metrics:**
- **Total Applications**: Number of leave requests
- **Approved/Pending/Rejected**: Status breakdown
- **Total Leave Days**: Sum of approved leave days
- **By Leave Type**: Breakdown by leave categories
- **By Department**: Department-wise statistics

#### **Analysis Metrics:**
- **Utilization Rate**: Percentage of allocated leave consumed
- **Approval Rate**: Percentage of applications approved
- **Processing Time**: Average time to process applications
- **Monthly Trends**: Leave patterns throughout the year

## System Architecture

### **Core Components**

#### 1. **LeaveReportsController** (`app/Http/Controllers/LeaveReportsController.php`)
- **Purpose**: Handles all report generation logic
- **Key Methods**:
  - `leaveBalanceReport()`: Generate leave balance reports
  - `leaveSummaryReport()`: Generate summary reports with date range
  - `leaveAnalysisReport()`: Generate comprehensive analysis
  - `leaveApprovalHistoryReport()`: Generate approval history
  - `getFilterOptions()`: Get filter options for forms

#### 2. **Excel Export Classes**
- **LeaveBalanceReportExport**: Excel export for balance reports
- **LeaveSummaryReportExport**: Excel export for summary reports (multi-sheet)
- **LeaveAnalysisReportExport**: Excel export for analysis reports (multi-sheet)
- **LeaveApprovalHistoryReportExport**: Excel export for approval history (multi-sheet)

#### 3. **PDF Templates** (`resources/views/reports/`)
- **leave-balance-pdf.blade.php**: PDF template for balance reports
- **leave-summary-pdf.blade.php**: PDF template for summary reports
- **leave-analysis-pdf.blade.php**: PDF template for analysis reports
- **leave-approval-history-pdf.blade.php**: PDF template for approval history

#### 4. **Filament Interface** (`app/Filament/Pages/LeaveReports.php`)
- **Purpose**: User-friendly interface for report generation
- **Features**:
  - Dynamic form based on report type
  - Real-time report generation
  - Export functionality (Excel/PDF)
  - Responsive data display

## Usage Guide

### **Accessing Reports**

1. **Login as Admin**: Ensure you have admin privileges
2. **Navigate to Reports**: Go to `/admin/leave-reports` or use the Filament navigation
3. **Select Report Type**: Choose from the four available report types
4. **Configure Filters**: Set appropriate filters based on report type
5. **Generate Report**: Click "Generate Report" to view data
6. **Export**: Download in Excel or PDF format

### **Report Configuration Options**

#### **Common Filters:**
- **Department**: Filter by specific department or view all
- **Year**: Select year for balance and analysis reports
- **Date Range**: Set start and end dates for summary and approval history

#### **Specific Filters:**
- **Status**: Filter by application status (pending, approved, rejected, cancelled)
- **Approver**: Filter by specific approver for approval history

### **Export Options**

#### **Excel Export:**
- **Format**: `.xlsx` files
- **Features**: 
  - Multi-sheet support for complex reports
  - Formatted tables with headers
  - Auto-sized columns
  - Professional styling
- **Filename**: `{report_type}_{parameters}_{timestamp}.xlsx`

#### **PDF Export:**
- **Format**: `.pdf` files
- **Features**:
  - Professional layout with company branding
  - Summary cards and statistics
  - Print-ready format
  - Responsive tables
- **Filename**: `{report_type}_{parameters}_{timestamp}.pdf`

## API Endpoints

### **Report Endpoints:**
- `GET /reports/leave/balance` - Employee Leave Balance Report
- `GET /reports/leave/summary` - Leave Summary Report
- `GET /reports/leave/analysis` - Leave Analysis Report
- `GET /reports/leave/approval-history` - Leave Approval History Report
- `GET /reports/leave/filter-options` - Get filter options

### **Query Parameters:**
- `format`: json, excel, pdf (default: json)
- `year`: Year for balance and analysis reports
- `start_date`: Start date for summary and approval history
- `end_date`: End date for summary and approval history
- `department_id`: Department ID filter
- `status`: Status filter (pending, approved, rejected, cancelled)
- `approver_id`: Approver ID filter

### **Example API Calls:**
```bash
# Get leave balance report for 2024 in Excel format
GET /reports/leave/balance?year=2024&format=excel

# Get leave summary report for date range in PDF format
GET /reports/leave/summary?start_date=2024-01-01&end_date=2024-12-31&format=pdf

# Get leave analysis report for specific department
GET /reports/leave/analysis?year=2024&department_id=1&format=json
```

## Data Sources

### **Primary Data Tables:**
- **`users`**: Employee information and department assignments
- **`leave_applications`**: Leave requests and approvals
- **`leave_balances`**: Current leave balances by type and year
- **`leave_types`**: Leave type definitions and configurations
- **`departments`**: Department information

### **Data Relationships:**
- Users → Leave Applications (1:many)
- Users → Leave Balances (1:many)
- Leave Types → Leave Applications (1:many)
- Leave Types → Leave Balances (1:many)
- Departments → Users (1:many)

## Performance Optimizations

### **Database Optimizations:**
- **Eager Loading**: Relationships loaded efficiently to prevent N+1 queries
- **Indexed Queries**: Performance indexes on key columns
- **Filtered Queries**: Efficient filtering at database level
- **Batch Processing**: Efficient data retrieval and processing

### **Report Generation:**
- **Lazy Loading**: Data loaded only when needed
- **Memory Management**: Efficient data processing for large datasets
- **Caching**: Filter options cached for better performance
- **Background Processing**: Large exports processed efficiently

## Security & Access Control

### **Access Restrictions:**
- **Authentication Required**: All endpoints require user authentication
- **Admin Only**: Reports accessible only to authenticated users
- **Data Filtering**: Users can only access data they're authorized to see

### **Data Privacy:**
- **Sensitive Information**: Employee personal data handled securely
- **Audit Trail**: All report generation logged for compliance
- **Export Security**: Downloaded files contain only necessary information

## Troubleshooting

### **Common Issues:**

#### **Report Generation Fails:**
- Check if user has proper authentication
- Verify filter parameters are valid
- Ensure database connections are working

#### **Export Downloads Don't Work:**
- Check browser download settings
- Verify file permissions
- Ensure sufficient disk space

#### **Slow Report Generation:**
- Reduce date range for large datasets
- Use department filters to limit data
- Check database performance

### **Error Messages:**
- **"No data found"**: Adjust filter criteria
- **"Export failed"**: Check server logs and permissions
- **"Authentication required"**: Ensure user is logged in

## Future Enhancements

### **Planned Features:**
- **Scheduled Reports**: Automatic report generation and email delivery
- **Custom Report Builder**: User-defined report configurations
- **Advanced Analytics**: Machine learning insights and predictions
- **Mobile App Integration**: Mobile-friendly report viewing
- **Real-time Dashboards**: Live leave statistics and trends

### **Integration Opportunities:**
- **Email Integration**: Automatic report delivery via email
- **Calendar Integration**: Leave calendar views and scheduling
- **Notification System**: Alerts for leave balance thresholds
- **Third-party Tools**: Integration with external HR systems
