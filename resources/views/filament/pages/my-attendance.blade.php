<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    My Attendance - {{ \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->format('F Y') }}
                </h3>
                <div class="flex space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $this->getTableQuery()->count() }} Records
                    </span>
                </div>
            </div>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                View your attendance records, working hours, and apply for leave. You can have multiple entries per day.
            </p>
            
            {{ $this->table }}
        </div>

        <!-- Monthly Summary -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Monthly Summary
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                @php
                    $monthlyData = $this->getTableQuery()->get();
                    $totalWorkingHours = 0;
                    $presentDays = 0;
                    $lateDays = 0;
                    $totalLateMinutes = 0;
                    $totalEarlyMinutes = 0;
                    
                    foreach ($monthlyData as $record) {
                        if ($record->clock_in && $record->clock_out) {
                            $totalMinutes = \Carbon\Carbon::parse($record->clock_in)->diffInMinutes(\Carbon\Carbon::parse($record->clock_out));
                            $totalWorkingHours += $totalMinutes / 60; // Convert to decimal hours
                        }
                        
                        // Count as present only if they have actual check in and check out
                        // AND it's not a leave day, weekend, or holiday
                        $date = \Carbon\Carbon::parse($record->date);
                        $user = auth()->user();
                        
                        // Check if it's a holiday
                        $isHoliday = \App\Models\Holiday::isHoliday($date);
                        
                        // Check if it's a working day based on office time
                        $isWorkingDay = true;
                        if ($user->officeTime) {
                            $isWorkingDay = $user->officeTime->isWorkingDate($date);
                        } else {
                            $isWorkingDay = !$date->isWeekend();
                        }
                        
                        // Check if on leave
                        $leaveApplication = \App\Models\LeaveApplication::where('user_id', $user->id)
                            ->where('status', 'approved')
                            ->where(function ($query) use ($date) {
                                $query->whereDate('start_date', '<=', $date)
                                    ->whereDate('end_date', '>=', $date);
                            })
                            ->first();
                        
                        // Only count as present if:
                        // 1. Has both clock in and clock out
                        // 2. Is a working day (not weekend)
                        // 3. Is not a holiday
                        // 4. Is not on leave
                        if ($record->clock_in && $record->clock_out && $isWorkingDay && !$isHoliday && !$leaveApplication) {
                            $presentDays++;
                        }
                        
                        // Count as late days for late in, early out, or late + early
                        if (in_array($record->status, ['late_in', 'early_out', 'late_in_early_out'])) {
                            $lateDays++;
                        }
                        
                        $totalLateMinutes += $record->late_minutes ?? 0;
                        $totalEarlyMinutes += $record->early_minutes ?? 0;
                    }
                    
                    // Round to 2 decimal places
                    $totalWorkingHours = round($totalWorkingHours, 2);
                @endphp
                
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $totalWorkingHours }}h</div>
                    <div class="text-sm text-blue-600 dark:text-blue-400">Total Working Hours</div>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $presentDays }}</div>
                    <div class="text-sm text-green-600 dark:text-green-400">Present Days</div>
                </div>
                
                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $lateDays }}</div>
                    <div class="text-sm text-yellow-600 dark:text-yellow-400">Late Days</div>
                </div>
                
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $totalLateMinutes }}m</div>
                    <div class="text-sm text-red-600 dark:text-red-400">Total Late Minutes</div>
                </div>
                
                <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $totalEarlyMinutes }}m</div>
                    <div class="text-sm text-orange-600 dark:text-orange-400">Total Early Minutes</div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>