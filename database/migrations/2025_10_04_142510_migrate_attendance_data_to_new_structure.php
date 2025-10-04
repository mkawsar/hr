<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing attendance data to new structure
        $existingAttendance = DB::table('attendance')->get();
        
        foreach ($existingAttendance as $record) {
            // Create or update daily attendance record
            $dailyAttendance = DB::table('daily_attendance')
                ->where('user_id', $record->user_id)
                ->where('date', $record->date)
                ->first();
            
            if (!$dailyAttendance) {
                // Create new daily attendance record
                $dailyAttendanceId = DB::table('daily_attendance')->insertGetId([
                    'user_id' => $record->user_id,
                    'date' => $record->date,
                    'first_clock_in' => $record->clock_in,
                    'last_clock_out' => $record->clock_out,
                    'total_entries' => 1,
                    'total_working_hours' => $record->clock_in && $record->clock_out ? 
                        DB::raw("TIMESTAMPDIFF(MINUTE, '$record->clock_in', '$record->clock_out') / 60.0") : 0,
                    'total_late_minutes' => $record->late_minutes ?? 0,
                    'total_early_minutes' => $record->early_minutes ?? 0,
                    'total_deduction_amount' => $record->deduction_amount ?? 0,
                    'status' => $record->status ?? 'absent',
                    'source' => $record->source ?? 'office',
                    'adjusted_by' => $record->adjusted_by,
                    'adjustment_reason' => $record->adjustment_reason,
                    'adjusted_at' => $record->adjusted_at,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);
            } else {
                // Update existing daily attendance record
                $dailyAttendanceId = $dailyAttendance->id;
                
                // Update with aggregated data
                DB::table('daily_attendance')
                    ->where('id', $dailyAttendanceId)
                    ->update([
                        'total_entries' => DB::raw('total_entries + 1'),
                        'total_working_hours' => DB::raw("total_working_hours + " . 
                            ($record->clock_in && $record->clock_out ? 
                                "TIMESTAMPDIFF(MINUTE, '$record->clock_in', '$record->clock_out') / 60.0" : 0)),
                        'total_late_minutes' => DB::raw('total_late_minutes + ' . ($record->late_minutes ?? 0)),
                        'total_early_minutes' => DB::raw('total_early_minutes + ' . ($record->early_minutes ?? 0)),
                        'total_deduction_amount' => DB::raw('total_deduction_amount + ' . ($record->deduction_amount ?? 0)),
                        'updated_at' => now(),
                    ]);
            }
            
            // Create attendance entry record
            DB::table('attendance_entries')->insert([
                'daily_attendance_id' => $dailyAttendanceId,
                'user_id' => $record->user_id,
                'date' => $record->date,
                'clock_in' => $record->clock_in ? $record->date . ' ' . $record->clock_in : null,
                'clock_out' => $record->clock_out ? $record->date . ' ' . $record->clock_out : null,
                'clock_in_latitude' => $record->clock_in_latitude,
                'clock_in_longitude' => $record->clock_in_longitude,
                'clock_out_latitude' => $record->clock_out_latitude,
                'clock_out_longitude' => $record->clock_out_longitude,
                'clock_in_location_id' => $record->clock_in_location_id,
                'clock_out_location_id' => $record->clock_out_location_id,
                'working_hours' => $record->clock_in && $record->clock_out ? 
                    DB::raw("TIMESTAMPDIFF(MINUTE, '$record->clock_in', '$record->clock_out') / 60.0") : null,
                'late_minutes' => $record->late_minutes ?? 0,
                'early_minutes' => $record->early_minutes ?? 0,
                'deduction_amount' => $record->deduction_amount ?? 0,
                'entry_status' => $this->determineEntryStatus($record->clock_in, $record->clock_out),
                'source' => $record->source ?? 'office',
                'notes' => null,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as it transforms data
        // You would need to implement reverse logic if needed
    }
    
    /**
     * Determine entry status based on clock in/out
     */
    private function determineEntryStatus($clockIn, $clockOut)
    {
        if ($clockIn && $clockOut) {
            return 'complete';
        } elseif ($clockIn && !$clockOut) {
            return 'clock_in_only';
        } elseif (!$clockIn && $clockOut) {
            return 'clock_out_only';
        } else {
            return 'incomplete';
        }
    }
};