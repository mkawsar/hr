# Earned Leave Calculation Command

## Overview
The `CalculateEarnedLeave` command calculates earned leave for all users based on their attendance from the previous year, considering weekends, holidays, and absent days. It then adds this earned leave to the current year's balance. It implements a carry-forward system with a maximum limit of 40 earned leave days.

## Key Concept
- **Calculates**: Earned leave based on previous year's attendance (e.g., 2024 attendance)
- **Applies to**: Current year's balance (e.g., 2025 balance)
- **Example**: If it's 2025, the command calculates earned leave from 2024 attendance and adds it to 2025 balance

## Command Usage

### Basic Usage
```bash
# Calculate earned leave from 2024 attendance and add to 2025 balance
php artisan leave:calculate-earned
```

### With Options
```bash
# Calculate for specific year (e.g., calculate from 2023 attendance, add to 2024 balance)
php artisan leave:calculate-earned --year=2023

# Calculate for specific user only
php artisan leave:calculate-earned --user=123

# Dry run (show what would be calculated without making changes)
php artisan leave:calculate-earned --dry-run

# Combine options
php artisan leave:calculate-earned --year=2023 --user=123 --dry-run
```

## Calculation Logic

### 1. Days Worked Calculation (from previous year)
- **Includes**: All working days where user was present, late, or had half-day
- **Excludes**: 
  - Weekends (Saturday and Sunday)
  - Public holidays (from holidays table)
  - Absent days
  - Days with no attendance record (treated as absent)

### 2. Earned Leave Calculation
- **Formula**: 1 earned leave day for every 15 days worked in previous year
- **Example**: 300 days worked in 2024 = 20 earned leave days for 2025

### 3. Carry Forward System
- **Source**: Previous year's earned leave balance (from year before calculation year)
- **Limit**: Maximum 40 days total (carry forward + new earned)
- **Example**: 
  - 2023 balance: 25 days
  - 2024 attendance earned: 20 days
  - 2025 total: min(25 + 20, 40) = 40 days

### 4. Maximum Limit
- **Total Balance**: Cannot exceed 40 earned leave days
- **Carry Forward**: Automatically limited to ensure total doesn't exceed 40

### 5. Year Logic
- **Calculation Year**: Year to analyze attendance (default: previous year)
- **Balance Year**: Year to update balance (default: current year)
- **Example**: In 2025, calculates from 2024 attendance and updates 2025 balance

## Prerequisites

### 1. Leave Type Setup
Ensure you have a leave type for earned leave:
- **Code**: "earned" OR
- **Name**: Contains "earned" (case-insensitive)

### 2. Required Data
- **Users**: Active users in the system
- **Daily Attendance**: Attendance records for the calculation year
- **Holidays**: Holiday records marked as active
- **Leave Balances**: Previous year's leave balance records (optional)

## Database Changes

The command will:
1. **Create** new leave balance records for users who don't have them
2. **Update** existing records if calculations have changed
3. **Preserve** consumed leave amounts (only updates balance, accrued, and carry_forward)

## Example Output

```
Calculating earned leave for year: 2024
Using leave type: Earned Leave (ID: 3)
Processing 50 users...

Processing user: John Doe (ID: 1)
  - Days worked: 280
  - Previous year balance: 15.0
  - Carry forward: 15.0
  - New earned leave: 18
  - Total balance: 33.0
  - Status: Updated

Processing user: Jane Smith (ID: 2)
  - Days worked: 300
  - Previous year balance: 30.0
  - Carry forward: 10.0
  - New earned leave: 20
  - Total balance: 40.0
  - Status: Updated

=== CALCULATION SUMMARY ===
Total users processed: 50
Total records updated: 45
```

## Scheduling

### Daily Calculation
Add to your `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Run earned leave calculation daily at 2 AM
    $schedule->command('leave:calculate-earned')
             ->dailyAt('02:00');
}
```

### Monthly Calculation
```php
protected function schedule(Schedule $schedule)
{
    // Run earned leave calculation on the 1st of each month
    $schedule->command('leave:calculate-earned')
             ->monthlyOn(1, '02:00');
}
```

### Yearly Calculation
```php
protected function schedule(Schedule $schedule)
{
    // Run earned leave calculation at the end of each year
    $schedule->command('leave:calculate-earned --year=' . (date('Y') - 1))
             ->yearlyOn(12, 31, '23:59');
}
```

## Error Handling

The command includes comprehensive error handling:
- **Missing Leave Type**: Command will exit with error if earned leave type not found
- **User Processing Errors**: Individual user errors are logged but don't stop the entire process
- **Database Errors**: All database operations are wrapped in try-catch blocks
- **Logging**: All errors are logged to Laravel's log system

## Customization

### Adjusting Earned Leave Rate
To change the earned leave rate (currently 1 day per 15 days worked), modify this line in the command:
```php
$newEarned = floor($daysWorked / 15); // Change 15 to your desired rate
```

### Adjusting Maximum Limit
To change the maximum earned leave limit (currently 40 days), modify these lines:
```php
$carryForward = min($previousYearBalance, 40); // Change 40 to your desired limit
$totalBalance = min($carryForward + $newEarned, 40); // Change 40 to your desired limit
```

### Customizing Attendance Logic
Modify the `calculateDaysWorked` method to adjust how attendance is calculated:
- Change which attendance statuses count as "worked"
- Modify how missing attendance records are handled
- Add custom business rules for specific scenarios

## Monitoring

### Log Files
Check Laravel logs for any errors:
```bash
tail -f storage/logs/laravel.log
```

### Database Verification
Verify calculations by checking the leave_balances table:
```sql
SELECT 
    u.name,
    lb.year,
    lb.balance,
    lb.accrued,
    lb.carry_forward,
    lb.consumed
FROM leave_balances lb
JOIN users u ON lb.user_id = u.id
JOIN leave_types lt ON lb.leave_type_id = lt.id
WHERE lt.code = 'earned'
ORDER BY lb.year DESC, u.name;
```

## Troubleshooting

### Common Issues

1. **"Earned leave type not found"**
   - Solution: Create a leave type with code "earned" or name containing "earned"

2. **"No users found to process"**
   - Solution: Ensure you have active users in the database

3. **Incorrect calculations**
   - Check attendance data quality
   - Verify holiday records are marked as active
   - Review the calculation logic in the command

4. **Performance issues with large datasets**
   - Consider running for specific users with `--user` option
   - Process in batches by year
   - Optimize database queries if needed
