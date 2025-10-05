<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EarnedLeaveConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'working_days_per_earned_leave',
        'max_earned_leave_days',
        'include_weekends',
        'include_holidays',
        'include_absent_days',
        'active',
        'year',
    ];

    protected function casts(): array
    {
        return [
            'include_weekends' => 'boolean',
            'include_holidays' => 'boolean',
            'include_absent_days' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the active configuration for a specific year
     */
    public static function getActiveConfigForYear(int $year): ?self
    {
        // First try to get year-specific config
        $yearConfig = self::where('active', true)
            ->where('year', $year)
            ->first();

        if ($yearConfig) {
            return $yearConfig;
        }

        // Fall back to default config (year is null)
        return self::where('active', true)
            ->whereNull('year')
            ->first();
    }

    /**
     * Get the default active configuration
     */
    public static function getDefaultConfig(): ?self
    {
        return self::where('active', true)
            ->whereNull('year')
            ->first();
    }

    /**
     * Create default configuration if none exists
     */
    public static function createDefaultIfNotExists(): self
    {
        $default = self::getDefaultConfig();
        
        if (!$default) {
            $default = self::create([
                'name' => 'Default Configuration',
                'description' => 'Default earned leave calculation configuration',
                'working_days_per_earned_leave' => 15,
                'max_earned_leave_days' => 40,
                'include_weekends' => false,
                'include_holidays' => false,
                'include_absent_days' => false,
                'active' => true,
                'year' => null,
            ]);
        }

        return $default;
    }
}