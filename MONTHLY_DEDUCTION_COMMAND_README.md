# Monthly Deduction Command

This document explains the monthly deduction system for handling late/early attendance penalties.

## Overview

The system automatically processes monthly deductions based on employee attendance patterns. The deduction rules are:

- **First 3 late/early occurrences**: No deduction (free allowance)
- **4th occurrence and beyond**: 0.5 days deduction per occurrence
- **Absent days**: 1 day deduction per absent day
- **Leave deduction priority**: 
  1. Casual Leave (current year balance)
  2. Earned Leave (can go negative)
  3. No cash deductions - only leave deductions

## Command Usage

### Basic Usage
```bash
# Process deductions for last month (default)
php artisan deductions:process-monthly

# Process deductions for specific month/year
php artisan deductions:process-monthly --month=10 --year=2025

# Dry run (see what would be processed without making changes)
php artisan deductions:process-monthly --dry-run
```

### Command Options

- `--month=MONTH`: Specify the month (1-12) to process deductions for
- `--year=YEAR`: Specify the year to process deductions for  
- `--dry-run`: Preview what would be processed without making actual changes

## Deduction Logic

### 1. Attendance Detection
The system identifies two types of attendance issues:

**Late/Early Detection:**
- `late_minutes > 0` OR `early_minutes > 0`

**Absent Detection:**
- `status = 'absent'` in daily_attendance table

### 2. Deduction Calculation
```php
// Late/Early deduction
late_early_deduction = late_early_count > 3 ? (late_early_count - 3) * 0.5 : 0;

// Absent deduction
absent_deduction = absent_count * 1.0;

// Total deduction
total_deduction = late_early_deduction + absent_deduction;
```

**Examples:**
- 4 late/early + 0 absent: (4-3) × 0.5 + 0 × 1.0 = **0.5 days**
- 5 late/early + 1 absent: (5-3) × 0.5 + 1 × 1.0 = **2.0 days**
- 2 late/early + 2 absent: 0 + 2 × 1.0 = **2.0 days**
- 6 late/early + 1 absent: (6-3) × 0.5 + 1 × 1.0 = **2.5 days**

### 3. Leave Deduction Priority

#### Step 1: Casual Leave
- Deducts from current year casual leave balance
- Only deducts available balance (cannot go negative)
- Updates `balance` and `consumed` fields
- **Creates leave application record** for audit trail

#### Step 2: Earned Leave  
- Deducts remaining amount from earned leave
- **Can go negative** (allows negative balance)
- Creates earned leave balance record if it doesn't exist
- **Creates leave application record** for audit trail

#### Step 3: No Cash Deductions
- If no leave balance available, logs the remaining deduction
- No cash deductions are processed
- Remaining deduction is tracked for reporting purposes

## Database Changes

### New Tables

#### `salary_deductions`
- `user_id`: Employee ID
- `deduction_month`: Month being processed (YYYY-MM-01 format)
- `late_early_count`: Number of late/early occurrences
- `deduction_days`: Total days to be deducted
- `leave_deduction_amount`: Days deducted from leave balances
- `cash_deduction_amount`: Always 0 (no cash deductions)
- `deduction_details`: JSON details of the deduction process

### Updated Tables

#### `users`
- Added `salary` field (decimal 10,2) for future use (not currently used for deductions)

#### `leave_applications`
- **New records created** for each leave deduction
- Status: `approved` (auto-approved by system)
- Reason: Automatic deduction explanation
- Approved by: System user (ID: 1)

## Example Scenarios

### Scenario 1: Employee with 5 late entries, 0 absent
- Late entries: 5, Absent days: 0
- Deduction days: (5-3) * 0.5 + 0 * 1.0 = 1.0 day
- Casual leave balance: 2.0 days
- Result: Deduct 1.0 day from casual leave

### Scenario 2: Employee with 2 late entries, 2 absent days
- Late entries: 2, Absent days: 2
- Deduction days: 0 + 2 * 1.0 = 2.0 days
- Casual leave balance: 1.0 day
- Earned leave balance: 1.0 day
- Result: Deduct 1.0 day from casual leave, 1.0 day from earned leave

### Scenario 3: Employee with 6 late entries, 1 absent day
- Late entries: 6, Absent days: 1
- Deduction days: (6-3) * 0.5 + 1 * 1.0 = 2.5 days
- Casual leave balance: 0 days
- Earned leave balance: 1.0 day
- Result: Deduct 1.0 day from earned leave, 1.5 days remaining (logged but not deducted)

### Scenario 4: Employee with 2 late entries, 0 absent
- Late entries: 2, Absent days: 0
- Deduction days: 0 + 0 = 0 days (≤3 free allowance)
- Result: No deduction

## Scheduling

To run this command monthly, add it to your Laravel scheduler in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Run on the 1st of each month at 9 AM
    $schedule->command('deductions:process-monthly')
             ->monthlyOn(1, '09:00');
}
```

## Monitoring

The command provides detailed output showing:
- Which employees are being processed
- Number of late/early entries found
- Deduction amounts calculated
- Leave balance updates
- Cash deductions applied

Use `--dry-run` option to preview changes before processing.

## Error Handling

- Database transactions ensure data consistency
- Duplicate processing is prevented (checks existing records)
- Detailed error messages for troubleshooting
- Rollback on any processing errors

## Leave Type Requirements

The system expects leave types with these codes:
- `casual`: For casual leave deductions
- `earned`: For earned leave deductions (can go negative)

Make sure these leave types exist in your `leave_types` table with the correct codes.
