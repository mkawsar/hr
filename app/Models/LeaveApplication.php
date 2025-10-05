<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days_count',
        'status',
        'reason',
        'attachment',
        'approved_by',
        'approved_at',
        'approval_notes',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days_count' => 'decimal:1',
            'approved_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Deduct leave days from user's leave balance when approved
     */
    public function deductFromLeaveBalance(): bool
    {
        $user = $this->user;
        $leaveType = $this->leaveType;
        $currentYear = \Carbon\Carbon::parse($this->start_date)->year;
        $daysToDeduct = $this->days_count;

        // Get or create leave balance for this user, leave type, and year
        $leaveBalance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $currentYear)
            ->first();

        if (!$leaveBalance) {
            // Create leave balance if it doesn't exist
            $leaveBalance = LeaveBalance::create([
                'user_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'year' => $currentYear,
                'balance' => $leaveType->accrual_days_per_year ?? 0,
                'consumed' => 0,
                'accrued' => $leaveType->accrual_days_per_year ?? 0,
                'carry_forward' => 0,
            ]);
        }

        // Check if there's sufficient balance
        if ($leaveBalance->balance < $daysToDeduct) {
            // For some leave types, we might allow negative balance (like earned leave)
            // For now, we'll allow it but log a warning
            \Log::warning("Leave balance insufficient for user {$user->id}, leave type {$leaveType->name}. Requested: {$daysToDeduct}, Available: {$leaveBalance->balance}");
        }

        // Deduct the days from balance
        $leaveBalance->balance -= $daysToDeduct;
        $leaveBalance->consumed += $daysToDeduct;
        $leaveBalance->save();

        \Log::info("Deducted {$daysToDeduct} days of {$leaveType->name} from user {$user->id}. New balance: {$leaveBalance->balance}");

        return true;
    }
}