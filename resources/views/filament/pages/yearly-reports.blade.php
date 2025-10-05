<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Report Configuration Form -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Report Configuration
                </h3>
                {{ $this->form }}
            </div>
        </div>

        <!-- Summary Cards -->
        @if($reportData && $reportType === 'individual')
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-users class="h-6 w-6 text-blue-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Total Employees
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ $reportData->count() }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-check-circle class="h-6 w-6 text-green-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Avg Attendance Rate
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ number_format($reportData->avg('summary.attendance_rate'), 1) }}%
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-clock class="h-6 w-6 text-yellow-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Avg Punctuality Score
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ number_format($reportData->avg('summary.punctuality_score'), 1) }}/100
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-x-circle class="h-6 w-6 text-red-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Total Absent Days
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ $reportData->sum('attendance.absent') }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($reportData && $reportType === 'department')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-building-office class="h-6 w-6 text-blue-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Total Departments
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ $reportData->count() }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-users class="h-6 w-6 text-green-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Total Employees
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ $reportData->sum('total_users') }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-chart-bar class="h-6 w-6 text-purple-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Avg Department Performance
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ number_format($reportData->avg('average_attendance_rate'), 1) }}%
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Report Table -->
        @if($reportData)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    @if($reportType === 'individual')
                        Individual Employee Reports - {{ $selectedYear }}
                    @elseif($reportType === 'department')
                        Department Summary - {{ $selectedYear }}
                    @else
                        Overall Summary - {{ $selectedYear }}
                    @endif
                </h3>
            </div>
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>
        @else
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="p-6 text-center">
                <x-heroicon-o-document-chart-bar class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No Report Data</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Click "Generate Report" to create a yearly report for {{ $selectedYear }}.
                </p>
            </div>
        </div>
        @endif

        <!-- Help Section -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-o-information-circle class="h-5 w-5 text-blue-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Yearly Report Features
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Individual Reports:</strong> Detailed attendance, leave, and punctuality data for each employee</li>
                            <li><strong>Department Summary:</strong> Aggregated performance metrics by department</li>
                            <li><strong>Overall Summary:</strong> Company-wide statistics and trends</li>
                            <li><strong>Export Options:</strong> Download reports in Excel or PDF format</li>
                            <li><strong>Performance Scoring:</strong> Automated calculation of attendance rates and punctuality scores</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
