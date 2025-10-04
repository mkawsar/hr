<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'password',
        'phone_1',
        'phone_2',
        'address',
        'designation',
        'date_of_joining',
        'status',
        'role_id',
        'department_id',
        'manager_id',
        'profile_photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_joining' => 'date',
        ];
    }

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function approvedLeaveApplications()
    {
        return $this->hasMany(LeaveApplication::class, 'approved_by');
    }

    public function adjustedAttendance()
    {
        return $this->hasMany(Attendance::class, 'adjusted_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role && $this->role->slug === 'admin';
    }

    public function isSupervisor()
    {
        return $this->role && $this->role->slug === 'supervisor';
    }

    public function isEmployee()
    {
        return $this->role && $this->role->slug === 'employee';
    }

    // Get team members for supervisors
    public function getTeamMembers()
    {
        if ($this->isSupervisor()) {
            return $this->subordinates;
        }
        return collect();
    }

    // Get pending leave applications for team members
    public function getTeamPendingLeaves()
    {
        if ($this->isSupervisor()) {
            return LeaveApplication::whereIn('user_id', $this->subordinates->pluck('id'))
                ->where('status', 'pending')
                ->with(['user', 'leaveType'])
                ->get();
        }
        return collect();
    }

    // Check if user can approve a specific leave application
    public function canApproveLeave(LeaveApplication $leaveApplication)
    {
        if ($this->isAdmin()) {
            return true;
        }
        
        if ($this->isSupervisor()) {
            return $this->subordinates->contains('id', $leaveApplication->user_id);
        }
        
        return false;
    }
}
