<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    Day Details - {{ \Carbon\Carbon::parse($this->date)->format('M d, Y') }}
                </h2>
                <a href="/admin/my-attendance" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Attendance
                </a>
            </div>
            
            @if($this->dailyAttendance)
                <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h3 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Day Summary</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-blue-700 dark:text-blue-300">Total Entries:</span>
                            <span class="text-blue-900 dark:text-blue-100 ml-2">{{ $this->dailyAttendance->total_entries }}</span>
                        </div>
                        @if($this->dailyAttendance->first_clock_in)
                        <div>
                            <span class="font-medium text-blue-700 dark:text-blue-300">First Clock In:</span>
                            <span class="text-blue-900 dark:text-blue-100 ml-2">{{ \Carbon\Carbon::parse($this->dailyAttendance->first_clock_in)->format('H:i:s') }}</span>
                        </div>
                        @endif
                        @if($this->dailyAttendance->last_clock_out)
                        <div>
                            <span class="font-medium text-blue-700 dark:text-blue-300">Last Clock Out:</span>
                            <span class="text-blue-900 dark:text-blue-100 ml-2">{{ \Carbon\Carbon::parse($this->dailyAttendance->last_clock_out)->format('H:i:s') }}</span>
                        </div>
                        @endif
                        @if($this->dailyAttendance->total_working_hours > 0)
                        <div>
                            <span class="font-medium text-blue-700 dark:text-blue-300">Total Hours:</span>
                            <span class="text-blue-900 dark:text-blue-100 ml-2">{{ number_format($this->dailyAttendance->total_working_hours, 2) }}h</span>
                        </div>
                        @endif
                    </div>
                </div>
                
                {{ $this->table }}
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No attendance record</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No attendance record found for this date.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
