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
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @php
                    $monthlyData = $this->getTableQuery()->get();
                    $totalWorkingHours = 0;
                    $presentDays = 0;
                    $lateDays = 0;
                    $totalLateMinutes = 0;
                    
                    foreach ($monthlyData as $record) {
                        if ($record->clock_in && $record->clock_out) {
                            $hours = \Carbon\Carbon::parse($record->clock_in)->diffInHours(\Carbon\Carbon::parse($record->clock_out));
                            $totalWorkingHours += $hours;
                        }
                        
                        if ($record->status === 'present') {
                            $presentDays++;
                        } elseif ($record->status === 'late') {
                            $lateDays++;
                        }
                        
                        $totalLateMinutes += $record->late_minutes ?? 0;
                    }
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
            </div>
        </div>
    </div>
</x-filament-panels::page>