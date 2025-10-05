<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyAttendance extends Model
{
    use HasFactory;

    protected $table = 'daily_attendance';

    protected $fillable = [
        'user_id',
        'date',
        'first_clock_in',
        'last_clock_out',
        'total_entries',
        'total_working_hours',
        'total_late_minutes',
        'total_early_minutes',
        'status',
        'source',
        'adjusted_by',
        'adjustment_reason',
        'office_time_id',
        'office_time_snapshot',
    ];

    protected $casts = [
        'date' => 'date',
        'first_clock_in' => 'datetime:H:i:s',
        'last_clock_out' => 'datetime:H:i:s',
        'total_working_hours' => 'decimal:2',
        'office_time_snapshot' => 'array',
    ];

    /**
     * Get the user that owns the daily attendance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who adjusted this attendance.
     */
    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /**
     * Get the office time that was active when this attendance was recorded.
     */
    public function officeTime(): BelongsTo
    {
        return $this->belongsTo(OfficeTime::class);
    }

    /**
     * Get all attendance entries for this day.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(AttendanceEntry::class);
    }


    /**
     * Scope to get attendance for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get attendance for a specific date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to get attendance for a specific month.
     */
    public function scopeForMonth($query, $month, $year)
    {
        return $query->whereMonth('date', $month)->whereYear('date', $year);
    }

    /**
     * Get first clock in from entries
     */
    public function getFirstClockIn()
    {
        return $this->entries->where('clock_in', '!=', null)->first()?->clock_in;
    }

    /**
     * Get last clock out from entries
     */
    public function getLastClockOut()
    {
        return $this->entries->where('clock_out', '!=', null)->last()?->clock_out;
    }

    /**
     * Check if attendance is complete for the day
     */
    public function isComplete(): bool
    {
        return $this->getFirstClockIn() && $this->getLastClockOut();
    }

    /**
     * Check if this day was a working day based on the stored office time snapshot
     */
    public function wasWorkingDay(): bool
    {
        if (!$this->office_time_snapshot) {
            return false;
        }

        $dayName = strtolower($this->date->format('l'));
        return in_array($dayName, $this->office_time_snapshot['working_days'] ?? []);
    }

    /**
     * Get the working days from the stored office time snapshot
     */
    public function getWorkingDaysFromSnapshot(): array
    {
        return $this->office_time_snapshot['working_days'] ?? [];
    }
}