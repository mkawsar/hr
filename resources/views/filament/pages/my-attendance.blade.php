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
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    📊 Monthly Attendance Summary
                </h3>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ \Carbon\Carbon::now()->format('F Y') }}
                </div>
            </div>
            
            @php
                $monthlyData = $this->getTableQuery()->get();
                $totalWorkingHours = 0;
                $presentDays = 0;
                $lateDays = 0;
                $absentDays = 0;
                $weekendDays = 0;
                $holidayDays = 0;
                $leaveDays = 0;
                $totalLateMinutes = 0;
                $totalEarlyMinutes = 0;
                $totalWorkingDays = 0;
                
                foreach ($monthlyData as $record) {
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
                    
                    // Count different day types
                    if ($isHoliday) {
                        $holidayDays++;
                    } elseif (!$isWorkingDay) {
                        $weekendDays++;
                    } elseif ($leaveApplication) {
                        $leaveDays++;
                    } else {
                        $totalWorkingDays++;
                        
                        // Count as present only if they have actual check in and check out
                        if ($record->clock_in && $record->clock_out) {
                            $presentDays++;
                            $totalMinutes = \Carbon\Carbon::parse($record->clock_in)->diffInMinutes(\Carbon\Carbon::parse($record->clock_out));
                            $totalWorkingHours += $totalMinutes / 60;
                        } else {
                            $absentDays++;
                        }
                        
                        // Count as late days for late in, early out, or late + early
                        if (in_array($record->status, ['late_in', 'early_out', 'late_in_early_out'])) {
                            $lateDays++;
                        }
                        
                        $totalLateMinutes += $record->late_minutes ?? 0;
                        $totalEarlyMinutes += $record->early_minutes ?? 0;
                    }
                }
                
                // Calculate additional metrics
                $totalWorkingHours = round($totalWorkingHours, 2);
                $attendanceRate = $totalWorkingDays > 0 ? round(($presentDays / $totalWorkingDays) * 100, 1) : 0;
                $averageWorkingHours = $presentDays > 0 ? round($totalWorkingHours / $presentDays, 2) : 0;
            @endphp
            
            <!-- Primary Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-6 rounded-xl border border-blue-200 dark:border-blue-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $totalWorkingHours }}h</div>
                            <div class="text-sm font-medium text-blue-700 dark:text-blue-300">Total Working Hours</div>
                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">Avg: {{ $averageWorkingHours }}h/day</div>
                        </div>
                        <div class="text-2xl">⏰</div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-6 rounded-xl border border-green-200 dark:border-green-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $presentDays }}</div>
                            <div class="text-sm font-medium text-green-700 dark:text-green-300">Present Days</div>
                            <div class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $attendanceRate }}% attendance rate</div>
                        </div>
                        <div class="text-2xl">✅</div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 p-6 rounded-xl border border-yellow-200 dark:border-yellow-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $lateDays }}</div>
                            <div class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Late/Early Days</div>
                            <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">{{ $totalLateMinutes }}m late, {{ $totalEarlyMinutes }}m early</div>
                        </div>
                        <div class="text-2xl">⚠️</div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 p-6 rounded-xl border border-red-200 dark:border-red-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $absentDays }}</div>
                            <div class="text-sm font-medium text-red-700 dark:text-red-300">Absent Days</div>
                            <div class="text-xs text-red-600 dark:text-red-400 mt-1">Incomplete attendance</div>
                        </div>
                        <div class="text-2xl">❌</div>
                    </div>
                </div>
            </div>
            
            <!-- Secondary Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-4 rounded-lg border border-purple-200 dark:border-purple-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ $weekendDays }}</div>
                            <div class="text-sm text-purple-700 dark:text-purple-300">Weekend Days</div>
                        </div>
                        <div class="text-lg">🏖️</div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 p-4 rounded-lg border border-indigo-200 dark:border-indigo-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ $holidayDays }}</div>
                            <div class="text-sm text-indigo-700 dark:text-indigo-300">Holiday Days</div>
                        </div>
                        <div class="text-lg">🎉</div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-teal-50 to-teal-100 dark:from-teal-900/20 dark:to-teal-800/20 p-4 rounded-lg border border-teal-200 dark:border-teal-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xl font-bold text-teal-600 dark:text-teal-400">{{ $leaveDays }}</div>
                            <div class="text-sm text-teal-700 dark:text-teal-300">Leave Days</div>
                        </div>
                        <div class="text-lg">🏥</div>
                    </div>
                </div>
            </div>
            
            <!-- Summary Stats -->
            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center justify-between text-sm">
                    <div class="text-gray-600 dark:text-gray-300">
                        <span class="font-medium">Total Working Days:</span> {{ $totalWorkingDays }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-300">
                        <span class="font-medium">Attendance Rate:</span> {{ $attendanceRate }}%
                    </div>
                    <div class="text-gray-600 dark:text-gray-300">
                        <span class="font-medium">Average Hours/Day:</span> {{ $averageWorkingHours }}h
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>