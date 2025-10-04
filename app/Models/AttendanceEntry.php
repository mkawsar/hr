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
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_in_location_id',
        'clock_out_location_id',
        'clock_in_address',
        'clock_out_address',
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
        'clock_in_latitude' => 'decimal:8',
        'clock_in_longitude' => 'decimal:8',
        'clock_out_latitude' => 'decimal:8',
        'clock_out_longitude' => 'decimal:8',
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