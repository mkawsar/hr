<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class OfficeTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'start_time',
        'end_time',
        'break_start_time',
        'break_end_time',
        'break_duration_minutes',
        'working_hours_per_day',
        'working_days',
        'late_grace_minutes',
        'early_grace_minutes',
        'active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'break_start_time' => 'datetime:H:i',
        'break_end_time' => 'datetime:H:i',
        'working_days' => 'array',
        'active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Check if a given day is a working day
     */
    public function isWorkingDay(string $dayName): bool
    {
        return in_array(strtolower($dayName), $this->working_days);
    }

    /**
     * Check if a given date is a working day
     */
    public function isWorkingDate(Carbon $date): bool
    {
        // Check if it's a holiday first
        if (\App\Models\Holiday::isHoliday($date)) {
            return false;
        }
        
        // Check if it's a working day according to office time (this overrides standard weekend logic)
        return $this->isWorkingDay($date->format('l'));
    }

    /**
     * Get working days as formatted string
     */
    public function getWorkingDaysFormattedAttribute(): string
    {
        $dayNames = [
            'monday' => 'Mon',
            'tuesday' => 'Tue',
            'wednesday' => 'Wed',
            'thursday' => 'Thu',
            'friday' => 'Fri',
            'saturday' => 'Sat',
            'sunday' => 'Sun',
        ];

        $formattedDays = array_map(function ($day) use ($dayNames) {
            return $dayNames[strtolower($day)] ?? ucfirst($day);
        }, $this->working_days);

        return implode(', ', $formattedDays);
    }

    /**
     * Get total working hours per day
     */
    public function getTotalWorkingHoursAttribute(): float
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        $totalMinutes = $end->diffInMinutes($start);
        
        // Subtract break duration
        $workingMinutes = $totalMinutes - $this->break_duration_minutes;
        
        return round($workingMinutes / 60, 2);
    }

    /**
     * Check if employee is late based on clock-in time
     */
    public function isLate(Carbon $clockInTime): bool
    {
        $expectedStart = Carbon::parse($this->start_time);
        $graceTime = $expectedStart->copy()->addMinutes($this->late_grace_minutes);
        
        return $clockInTime->gt($graceTime);
    }

    /**
     * Check if employee left early based on clock-out time
     */
    public function isEarly(Carbon $clockOutTime): bool
    {
        $expectedEnd = Carbon::parse($this->end_time);
        $graceTime = $expectedEnd->copy()->subMinutes($this->early_grace_minutes);
        
        return $clockOutTime->lt($graceTime);
    }

    /**
     * Calculate late minutes
     */
    public function getLateMinutes(Carbon $clockInTime): int
    {
        if (!$this->isLate($clockInTime)) {
            return 0;
        }

        $expectedStart = Carbon::parse($this->start_time);
        $graceTime = $expectedStart->copy()->addMinutes($this->late_grace_minutes);
        return $graceTime->diffInMinutes($clockInTime);
    }

    /**
     * Calculate early minutes
     */
    public function getEarlyMinutes(Carbon $clockOutTime): int
    {
        if (!$this->isEarly($clockOutTime)) {
            return 0;
        }

        $expectedEnd = Carbon::parse($this->end_time);
        $graceTime = $expectedEnd->copy()->subMinutes($this->early_grace_minutes);
        return $clockOutTime->diffInMinutes($graceTime);
    }
}