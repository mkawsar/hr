# Leave Deduction Records Guide

## Where Leave Deduction Records Are Stored

When the monthly deduction command processes leave deductions, it creates records in **two places**:

### 1. Leave Applications Table (`leave_applications`)

**Purpose**: Provides a complete audit trail of all leave deductions as proper leave application records.

**Key Fields**:
- `user_id`: Employee who had leave deducted
- `leave_type_id`: Type of leave deducted (casual/earned)
- `start_date`: First day of the deduction month
- `end_date`: Calculated based on deduction days
- `days_count`: Number of days deducted
- `status`: Always `'approved'` (auto-approved by system)
- `reason`: Explains why the deduction occurred
- `approved_by`: System user ID (1)
- `approved_at`: Timestamp when deduction was processed
- `approval_notes`: "Automatically approved by monthly deduction system"

**Example Record**:
```sql
SELECT 
    la.id,
    u.name as employee_name,
    lt.name as leave_type,
    la.days_count,
    la.reason,
    la.approved_at
FROM leave_applications la
JOIN users u ON la.user_id = u.id
JOIN leave_types lt ON la.leave_type_id = lt.id
WHERE la.reason LIKE '%Automatic deduction%'
ORDER BY la.approved_at DESC;
```

### 2. Salary Deductions Table (`salary_deductions`)

**Purpose**: Tracks the overall deduction summary for each employee per month.

**Key Fields**:
- `user_id`: Employee ID
- `deduction_month`: Month when deduction was processed
- `late_early_count`: Number of late/early occurrences
- `deduction_days`: Total days deducted
- `leave_deduction_amount`: Days deducted from leave balances
- `cash_deduction_amount`: Always 0 (no cash deductions)
- `deduction_details`: JSON with detailed breakdown

**Example Record**:
```sql
SELECT 
    sd.id,
    u.name as employee_name,
    sd.deduction_month,
    sd.late_early_count,
    sd.deduction_days,
    sd.leave_deduction_amount,
    sd.cash_deduction_amount
FROM salary_deductions sd
JOIN users u ON sd.user_id = u.id
ORDER BY sd.deduction_month DESC;
```

## How to View Leave Deduction Records

### 1. View All Deduction Leave Applications

```sql
-- Get all automatic leave deductions
SELECT 
    la.id,
    u.employee_id,
    u.name as employee_name,
    lt.name as leave_type,
    la.days_count,
    la.start_date,
    la.end_date,
    la.reason,
    la.approved_at
FROM leave_applications la
JOIN users u ON la.user_id = u.id
JOIN leave_types lt ON la.leave_type_id = lt.id
WHERE la.reason LIKE '%Automatic deduction%'
ORDER BY la.approved_at DESC;
```

### 2. View Deduction Summary by Month

```sql
-- Get deduction summary for each employee by month
SELECT 
    u.employee_id,
    u.name as employee_name,
    sd.deduction_month,
    sd.late_early_count,
    sd.deduction_days,
    sd.leave_deduction_amount,
    sd.cash_deduction_amount,
    JSON_EXTRACT(sd.deduction_details, '$.details') as deduction_breakdown
FROM salary_deductions sd
JOIN users u ON sd.user_id = u.id
ORDER BY sd.deduction_month DESC, u.name;
```

### 3. View Leave Balance Changes

```sql
-- See how leave balances were affected by deductions
SELECT 
    u.name as employee_name,
    lt.name as leave_type,
    lb.year,
    lb.balance as current_balance,
    lb.consumed as total_consumed,
    lb.accrued
FROM leave_balances lb
JOIN users u ON lb.user_id = u.id
JOIN leave_types lt ON lb.leave_type_id = lt.id
WHERE lb.year = YEAR(CURDATE())
ORDER BY u.name, lt.name;
```

## Filament Admin Interface

You can also view these records through the Filament admin interface:

### Leave Applications
- Navigate to **Leave Applications** in the admin panel
- Filter by reason containing "Automatic deduction"
- All deduction leave applications will show with status "Approved"

### Salary Deductions
- Navigate to **Salary Deductions** (if you create a resource for it)
- View monthly deduction summaries
- See detailed breakdown in the `deduction_details` field

## Example Deduction Flow

When an employee has 6 late entries in a month:

1. **Calculation**: (6-3) × 0.5 = 1.5 days deduction
2. **Leave Application Created**: 
   - Casual leave: 1.0 day (if available)
   - Earned leave: 0.5 day (can go negative)
3. **Salary Deduction Record**: Summary of the entire process
4. **Leave Balance Updated**: Balances reduced accordingly

## Benefits of This Approach

1. **Complete Audit Trail**: Every deduction is recorded as a proper leave application
2. **Transparency**: Employees can see their deduction leave applications
3. **Reporting**: Easy to generate reports on deductions
4. **Integration**: Works with existing leave management system
5. **Compliance**: Maintains proper records for HR and payroll

## Query Examples for Common Reports

### Monthly Deduction Report
```sql
SELECT 
    DATE_FORMAT(sd.deduction_month, '%Y-%m') as month,
    COUNT(DISTINCT sd.user_id) as employees_affected,
    SUM(sd.deduction_days) as total_days_deducted,
    SUM(sd.leave_deduction_amount) as total_leave_deducted,
    SUM(sd.cash_deduction_amount) as total_cash_deducted
FROM salary_deductions sd
GROUP BY DATE_FORMAT(sd.deduction_month, '%Y-%m')
ORDER BY month DESC;
```

### Employee Deduction History
```sql
SELECT 
    u.employee_id,
    u.name,
    sd.deduction_month,
    sd.late_early_count,
    sd.deduction_days,
    sd.leave_deduction_amount,
    sd.cash_deduction_amount
FROM salary_deductions sd
JOIN users u ON sd.user_id = u.id
WHERE u.employee_id = 'EMP003'  -- Replace with specific employee ID
ORDER BY sd.deduction_month DESC;
```
