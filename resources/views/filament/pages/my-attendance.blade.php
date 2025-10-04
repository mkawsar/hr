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
                View your attendance records grouped by date. Each row shows a summary for one day. Click "View" to see all time entries for that day.
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
                    $date = $record->date;
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
                        $firstEntry = $record->entries->where('clock_in', '!=', null)->first();
                        $lastEntry = $record->entries->where('clock_out', '!=', null)->last();
                        
                        if ($firstEntry && $lastEntry) {
                            $presentDays++;
                            $totalWorkingHours += $record->total_working_hours ?? 0;
                        } else {
                            $absentDays++;
                        }
                        
                        // Count as late days based on status
                        if (in_array($record->status, ['late_in', 'early_out', 'late_in_early_out', 'late', 'early_leave'])) {
                            $lateDays++;
                        }
                        
                        $totalLateMinutes += $record->total_late_minutes ?? 0;
                        $totalEarlyMinutes += $record->total_early_minutes ?? 0;
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
        
        <!-- Modal for showing day details -->
        <div id="dayDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="modalTitle">
                            Day Details
                        </h3>
                        <button onclick="closeDayDetailsModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="dayDetailsContent" class="space-y-4">
                        <!-- Day details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

<script>
function showDayDetails(date) {
    // Show modal
    document.getElementById('dayDetailsModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Day Details - ' + date;
    
    // Load day details via AJAX
    fetch(`/attendance/day-details/${date}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const content = document.getElementById('dayDetailsContent');
            content.innerHTML = '';
            
            if (data.entries && data.entries.length > 0) {
                // Create day details display with all time entries
                let entriesHtml = '';
                
                // Summary section
                const summaryHtml = `
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-4">
                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-3">Day Summary</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-lg font-semibold text-blue-900 dark:text-blue-100">${data.summary.total_entries}</div>
                                <div class="text-xs text-blue-600 dark:text-blue-300">Total Entries</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-blue-900 dark:text-blue-100">${data.summary.first_clock_in || '-'}</div>
                                <div class="text-xs text-blue-600 dark:text-blue-300">First In</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-blue-900 dark:text-blue-100">${data.summary.last_clock_out || '-'}</div>
                                <div class="text-xs text-blue-600 dark:text-blue-300">Last Out</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-blue-900 dark:text-blue-100">${data.summary.total_working_hours || '-'}</div>
                                <div class="text-xs text-blue-600 dark:text-blue-300">Total Hours</div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Individual entries section
                entriesHtml += `
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">All Time Entries</h4>
                        <div class="space-y-3">
                `;
                
                data.entries.forEach((entry, index) => {
                    const entryColor = getEntryTypeColor(entry.entry_type);
                    entriesHtml += `
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${entryColor}">Entry ${index + 1}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">${entry.entry_type}</span>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">${entry.created_at}</span>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Clock In:</span>
                                    <div class="font-medium text-gray-900 dark:text-white">${entry.clock_in || '-'}</div>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Clock Out:</span>
                                    <div class="font-medium text-gray-900 dark:text-white">${entry.clock_out || '-'}</div>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Hours:</span>
                                    <div class="font-medium text-gray-900 dark:text-white">${entry.working_hours || '-'}</div>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Status:</span>
                                    <div class="font-medium text-gray-900 dark:text-white">${entry.status || '-'}</div>
                                </div>
                            </div>
                            ${entry.late_minutes > 0 || entry.early_minutes > 0 ? `
                            <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                                <div class="flex space-x-4 text-xs">
                                    ${entry.late_minutes > 0 ? `<span class="text-orange-600 dark:text-orange-400">Late: ${entry.late_minutes} min</span>` : ''}
                                    ${entry.early_minutes > 0 ? `<span class="text-red-600 dark:text-red-400">Early: ${entry.early_minutes} min</span>` : ''}
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    `;
                });
                
                entriesHtml += `
                        </div>
                    </div>
                `;
                
                // Leave information if applicable
                let leaveInfoHtml = '';
                if (data.summary.leave_info) {
                    leaveInfoHtml = `
                        <div class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-yellow-900 dark:text-yellow-100 mb-2">Leave Information</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-yellow-700 dark:text-yellow-300">Leave Type:</span>
                                    <span class="text-sm font-medium text-yellow-900 dark:text-yellow-100">${data.summary.leave_info.type}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-yellow-700 dark:text-yellow-300">Reason:</span>
                                    <span class="text-sm font-medium text-yellow-900 dark:text-yellow-100">${data.summary.leave_info.reason}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-yellow-700 dark:text-yellow-300">Status:</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">${data.summary.leave_info.status}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                content.innerHTML = summaryHtml + entriesHtml + leaveInfoHtml;
            } else {
                content.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-4">No attendance records found for this date.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading day details:', error);
            document.getElementById('dayDetailsContent').innerHTML = `
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-red-900 dark:text-red-100 mb-2">Error Loading Day Details</h4>
                    <p class="text-sm text-red-700 dark:text-red-300">${error.message}</p>
                    <p class="text-xs text-red-600 dark:text-red-400 mt-2">Please try refreshing the page and try again.</p>
                </div>
            `;
        });
}

function closeDayDetailsModal() {
    document.getElementById('dayDetailsModal').classList.add('hidden');
}

function getStatusBadgeColor(status) {
    switch(status) {
        case 'full_present':
        case 'present':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'late_in':
        case 'early_out':
        case 'late_in_early_out':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'absent':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
}

function getEntryTypeColor(entryType) {
    switch(entryType) {
        case 'Complete Entry':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'Clock In Only':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        case 'Clock Out Only':
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
        case 'No Time Data':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
}

// Close modal when clicking outside
document.getElementById('dayDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDayDetailsModal();
    }
});
</script>