<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_attendance_id',
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'clock_in_location_id',
        'clock_out_location_id',
        'working_hours',
        'late_minutes',
        'early_minutes',
        'entry_status',
        'source',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'working_hours' => 'decimal:2',
    ];

    /**
     * Get the daily attendance that owns this entry.
     */
    public function dailyAttendance(): BelongsTo
    {
        return $this->belongsTo(DailyAttendance::class);
    }

    /**
     * Get the user that owns this entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the clock in location.
     */
    public function clockInLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'clock_in_location_id');
    }

    /**
     * Get the clock out location.
     */
    public function clockOutLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'clock_out_location_id');
    }

    /**
     * Check if entry is complete.
     */
    public function isComplete(): bool
    {
        return $this->entry_status === 'complete';
    }
}