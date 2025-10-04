<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'date',
        'type',
        'recurring',
        'active',
    ];

    protected $casts = [
        'date' => 'date',
        'recurring' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Check if a given date is a holiday
     */
    public static function isHoliday(Carbon $date): bool
    {
        return static::whereDate('date', $date->toDateString())
            ->where('active', true)
            ->exists();
    }

    /**
     * Get holiday for a given date
     */
    public static function getHoliday(Carbon $date): ?self
    {
        return static::whereDate('date', $date->toDateString())
            ->where('active', true)
            ->first();
    }

    /**
     * Get all holidays for a given year
     */
    public static function getHolidaysForYear(int $year): \Illuminate\Database\Eloquent\Collection
    {
        return static::whereYear('date', $year)
            ->where('active', true)
            ->orderBy('date')
            ->get();
    }

    /**
     * Get holidays for a given month
     */
    public static function getHolidaysForMonth(int $year, int $month): \Illuminate\Database\Eloquent\Collection
    {
        return static::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('active', true)
            ->orderBy('date')
            ->get();
    }

    /**
     * Check if a given date is a weekend
     */
    public static function isWeekend(Carbon $date): bool
    {
        return $date->isWeekend();
    }

    /**
     * Check if a given date is a working day (not weekend and not holiday)
     */
    public static function isWorkingDay(Carbon $date): bool
    {
        return !$date->isWeekend() && !static::isHoliday($date);
    }

    /**
     * Get the type badge color
     */
    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'national' => 'success',
            'regional' => 'info',
            'company' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('M d, Y');
    }

    /**
     * Get day name
     */
    public function getDayNameAttribute(): string
    {
        return $this->date->format('l');
    }
}