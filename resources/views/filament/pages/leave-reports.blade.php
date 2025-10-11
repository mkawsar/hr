<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Report Configuration Form -->
        <x-filament::section>
            <x-slot name="heading">
                Report Configuration
            </x-slot>
            
            <x-slot name="description">
                Configure the parameters for your leave report
            </x-slot>

            {{ $this->form }}
        </x-filament::section>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3">
            @foreach ($this->getFormActions() as $action)
                @if ($action->isVisible())
                    {{ $action }}
                @endif
            @endforeach
        </div>

        <!-- Loading Indicator -->
        @if ($this->loading)
            <div class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                <span class="ml-2 text-gray-600">Generating report...</span>
            </div>
        @endif

        <!-- Report Results -->
        @if ($this->reportData && !$this->loading)
            <x-filament::section>
                <x-slot name="heading">
                    Report Results
                </x-slot>
                
                <x-slot name="description">
                    {{ ucfirst(str_replace('-', ' ', $this->reportType)) }} Report Data
                    @if($this->reportType === 'balance' && isset($this->reportData['data']))
                        - {{ count($this->reportData['data']) }} employees found
                    @endif
                </x-slot>

                @if ($this->reportType === 'balance')
                    <!-- Leave Balance Report -->
                    <div class="space-y-6">
                        @if (isset($this->reportData['data']) && count($this->reportData['data']) > 0)

                            <!-- Summary Cards -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-6 rounded-xl border-2 border-blue-200 dark:border-blue-700 shadow-lg">
                                    <div class="text-sm font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wide">Total Employees</div>
                                    <div class="text-3xl font-bold text-blue-900 dark:text-blue-100 mt-2">{{ count($this->reportData['data']) }}</div>
                                    <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">Active employees</div>
                                </div>
                                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-6 rounded-xl border-2 border-green-200 dark:border-green-700 shadow-lg">
                                    <div class="text-sm font-semibold text-green-700 dark:text-green-300 uppercase tracking-wide">Total Balance</div>
                                    <div class="text-3xl font-bold text-green-900 dark:text-green-100 mt-2">{{ collect($this->reportData['data'])->sum('total_balance') }}</div>
                                    <div class="text-xs text-green-600 dark:text-green-400 mt-1">Available days</div>
                                </div>
                                <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 p-6 rounded-xl border-2 border-red-200 dark:border-red-700 shadow-lg">
                                    <div class="text-sm font-semibold text-red-700 dark:text-red-300 uppercase tracking-wide">Total Consumed</div>
                                    <div class="text-3xl font-bold text-red-900 dark:text-red-100 mt-2">{{ collect($this->reportData['data'])->sum('total_consumed') }}</div>
                                    <div class="text-xs text-red-600 dark:text-red-400 mt-1">Used days</div>
                                </div>
                                <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-6 rounded-xl border-2 border-purple-200 dark:border-purple-700 shadow-lg">
                                    <div class="text-sm font-semibold text-purple-700 dark:text-purple-300 uppercase tracking-wide">Leave Types</div>
                                    <div class="text-3xl font-bold text-purple-900 dark:text-purple-100 mt-2">{{ collect($this->reportData['data'])->pluck('leave_balances')->flatten(1)->groupBy('leave_type')->count() }}</div>
                                    <div class="text-xs text-purple-600 dark:text-purple-400 mt-1">Different types</div>
                                </div>
                            </div>

                            <!-- Employee Leave Balances -->
                            @foreach ($this->reportData['data'] as $employee)
                                <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300">
                                    <!-- Employee Header -->
                                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 px-6 py-5 border-b-2 border-gray-200 dark:border-gray-600 rounded-t-xl">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                                    {{ $employee['name'] }} 
                                                    <span class="text-lg font-semibold text-blue-600 dark:text-blue-400">({{ $employee['employee_id'] }})</span>
                                                </h3>
                                                <div class="flex items-center space-x-4">
                                                    <div class="flex items-center">
                                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Department:</span>
                                                        <span class="ml-2 px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-sm font-bold rounded-full">
                                                            {{ $employee['department'] ?? 'Not Available' }}
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Designation:</span>
                                                        <span class="ml-2 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-sm font-bold rounded-full">
                                                            {{ $employee['designation'] ?? 'Not Available' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border-2 border-green-200 dark:border-green-700">
                                                    <div class="text-sm font-semibold text-green-700 dark:text-green-300">Total Balance</div>
                                                    <div class="text-3xl font-bold text-green-900 dark:text-green-100">{{ $employee['total_balance'] }}</div>
                                                </div>
                                                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border-2 border-red-200 dark:border-red-700 mt-3">
                                                    <div class="text-sm font-semibold text-red-700 dark:text-red-300">Total Consumed</div>
                                                    <div class="text-2xl font-bold text-red-900 dark:text-red-100">{{ $employee['total_consumed'] }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Leave Balance Table -->
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                                            <thead class="bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600">
                                                <tr>
                                                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">Leave Type</th>
                                                    <th class="px-6 py-4 text-center text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">Code</th>
                                                    <th class="px-6 py-4 text-center text-sm font-bold text-green-800 dark:text-green-300 uppercase tracking-wider">Balance</th>
                                                    <th class="px-6 py-4 text-center text-sm font-bold text-red-800 dark:text-red-300 uppercase tracking-wider">Consumed</th>
                                                    <th class="px-6 py-4 text-center text-sm font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wider">Accrued</th>
                                                    <th class="px-6 py-4 text-center text-sm font-bold text-yellow-800 dark:text-yellow-300 uppercase tracking-wider">Carry Forward</th>
                                                    <th class="px-6 py-4 text-center text-sm font-bold text-purple-800 dark:text-purple-300 uppercase tracking-wider">Total Available</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                                @foreach ($employee['leave_balances'] as $index => $balance)
                                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $balance['leave_type'] }}</div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border-2 border-gray-300 dark:border-gray-600">
                                                                {{ $balance['leave_code'] }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-lg font-bold bg-green-100 dark:bg-green-900/30 text-green-900 dark:text-green-300 border-2 border-green-300 dark:border-green-700 shadow-sm">
                                                                {{ $balance['balance'] }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-lg font-bold bg-red-100 dark:bg-red-900/30 text-red-900 dark:text-red-300 border-2 border-red-300 dark:border-red-700 shadow-sm">
                                                                {{ $balance['consumed'] }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-lg font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-900 dark:text-blue-300 border-2 border-blue-300 dark:border-blue-700 shadow-sm">
                                                                {{ $balance['accrued'] }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-lg font-bold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-900 dark:text-yellow-300 border-2 border-yellow-300 dark:border-yellow-700 shadow-sm">
                                                                {{ $balance['carry_forward'] }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-lg font-bold bg-purple-100 dark:bg-purple-900/30 text-purple-900 dark:text-purple-300 border-2 border-purple-300 dark:border-purple-700 shadow-sm">
                                                                {{ $balance['total_available'] }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-16">
                                <div class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500 mb-4">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">No Leave Balance Data</h3>
                                <p class="text-lg text-gray-600 dark:text-gray-400">No leave balance data found for the selected criteria.</p>
                            </div>
                        @endif
                    </div>

                @elseif ($this->reportType === 'summary')
                    <!-- Leave Summary Report -->
                    <div class="space-y-6">
                        @if (isset($this->reportData['statistics']))
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-6 rounded-xl border-2 border-blue-200 dark:border-blue-700 shadow-lg">
                                    <div class="text-sm font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wide">Total Applications</div>
                                    <div class="text-3xl font-bold text-blue-900 dark:text-blue-100 mt-2">{{ $this->reportData['statistics']['total_applications'] }}</div>
                                    <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">All applications</div>
                                </div>
                                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-6 rounded-xl border-2 border-green-200 dark:border-green-700 shadow-lg">
                                    <div class="text-sm font-semibold text-green-700 dark:text-green-300 uppercase tracking-wide">Approved</div>
                                    <div class="text-3xl font-bold text-green-900 dark:text-green-100 mt-2">{{ $this->reportData['statistics']['approved_applications'] }}</div>
                                    <div class="text-xs text-green-600 dark:text-green-400 mt-1">Approved requests</div>
                                </div>
                                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 p-6 rounded-xl border-2 border-yellow-200 dark:border-yellow-700 shadow-lg">
                                    <div class="text-sm font-semibold text-yellow-700 dark:text-yellow-300 uppercase tracking-wide">Pending</div>
                                    <div class="text-3xl font-bold text-yellow-900 dark:text-yellow-100 mt-2">{{ $this->reportData['statistics']['pending_applications'] }}</div>
                                    <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">Awaiting approval</div>
                                </div>
                                <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 p-6 rounded-xl border-2 border-red-200 dark:border-red-700 shadow-lg">
                                    <div class="text-sm font-semibold text-red-700 dark:text-red-300 uppercase tracking-wide">Rejected</div>
                                    <div class="text-3xl font-bold text-red-900 dark:text-red-100 mt-2">{{ $this->reportData['statistics']['rejected_applications'] }}</div>
                                    <div class="text-xs text-red-600 dark:text-red-400 mt-1">Rejected requests</div>
                                </div>
                            </div>
                        @endif

                        @if (isset($this->reportData['data']) && count($this->reportData['data']) > 0)
                            <!-- Leave Applications Table -->
                            <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl shadow-lg">
                                <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 px-6 py-4 border-b-2 border-gray-200 dark:border-gray-600 rounded-t-xl">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Leave Applications</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ count($this->reportData['data']) }} applications found</p>
                                </div>
                                
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gradient-to-r from-gray-100 to-gray-200">
                                            <tr>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-800 uppercase tracking-wider">Employee</th>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-blue-800 uppercase tracking-wider">Department</th>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-800 uppercase tracking-wider">Leave Type</th>
                                                <th class="px-6 py-4 text-center text-sm font-bold text-gray-800 uppercase tracking-wider">Duration</th>
                                                <th class="px-6 py-4 text-center text-sm font-bold text-blue-800 uppercase tracking-wider">Days</th>
                                                <th class="px-6 py-4 text-center text-sm font-bold text-gray-800 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-800 uppercase tracking-wider">Applied At</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach ($this->reportData['data'] as $application)
                                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-bold text-gray-900">{{ $application['employee_name'] }}</div>
                                                        <div class="text-sm text-gray-600">{{ $application['employee_id'] }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-bold rounded-full border border-blue-300">
                                                            {{ $application['department'] }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-gray-200 text-gray-800 border border-gray-300">
                                                            {{ $application['leave_type'] }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                                        <div class="text-sm font-semibold text-gray-900">{{ $application['start_date'] }}</div>
                                                        <div class="text-xs text-gray-500">to</div>
                                                        <div class="text-sm font-semibold text-gray-900">{{ $application['end_date'] }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-blue-100 text-blue-900 border border-blue-300">
                                                            {{ $application['days_count'] }} days
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                                        <span class="inline-flex px-3 py-1 text-sm font-bold rounded-lg border-2
                                                            @if($application['status'] === 'Approved') bg-green-100 text-green-900 border-green-300
                                                            @elseif($application['status'] === 'Pending') bg-yellow-100 text-yellow-900 border-yellow-300
                                                            @elseif($application['status'] === 'Rejected') bg-red-100 text-red-900 border-red-300
                                                            @else bg-gray-100 text-gray-800 border-gray-300
                                                            @endif">
                                                            {{ $application['status'] }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                        {{ $application['applied_at'] }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-16">
                                <div class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500 mb-4">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">No Leave Applications</h3>
                                <p class="text-lg text-gray-600 dark:text-gray-400">No leave applications found for the selected criteria.</p>
                            </div>
                        @endif
                    </div>

                @elseif ($this->reportType === 'analysis')
                    <!-- Leave Analysis Report -->
                    <div class="space-y-6">
                        @if (isset($this->reportData['data']['summary']))
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-6 rounded-xl border-2 border-blue-200 dark:border-blue-700 shadow-lg">
                                    <div class="text-sm font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wide">Total Employees</div>
                                    <div class="text-3xl font-bold text-blue-900 dark:text-blue-100 mt-2">{{ $this->reportData['data']['total_employees'] }}</div>
                                    <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">Active employees</div>
                                </div>
                                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-6 rounded-xl border-2 border-green-200 dark:border-green-700 shadow-lg">
                                    <div class="text-sm font-semibold text-green-700 dark:text-green-300 uppercase tracking-wide">Overall Utilization</div>
                                    <div class="text-3xl font-bold text-green-900 dark:text-green-100 mt-2">{{ $this->reportData['data']['summary']['overall_utilization_rate'] }}%</div>
                                    <div class="text-xs text-green-600 dark:text-green-400 mt-1">Leave utilization rate</div>
                                </div>
                                <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-6 rounded-xl border-2 border-purple-200 dark:border-purple-700 shadow-lg">
                                    <div class="text-sm font-semibold text-purple-700 dark:text-purple-300 uppercase tracking-wide">Approval Rate</div>
                                    <div class="text-3xl font-bold text-purple-900 dark:text-purple-100 mt-2">{{ $this->reportData['data']['summary']['approval_rate'] }}%</div>
                                    <div class="text-xs text-purple-600 dark:text-purple-400 mt-1">Application approval rate</div>
                                </div>
                            </div>
                        @endif

                        <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-8">
                            <div class="text-center">
                                <div class="mx-auto h-16 w-16 text-blue-400 dark:text-blue-500 mb-4">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">Detailed Analysis Available</h3>
                                <p class="text-lg text-gray-600 dark:text-gray-400 mb-4">Comprehensive analysis data is available in the Excel and PDF exports.</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500">Use the export buttons above to download the complete analysis report with detailed insights and charts.</p>
                            </div>
                        </div>
                    </div>

                @elseif ($this->reportType === 'approval-history')
                    <!-- Leave Approval History Report -->
                    <div class="space-y-6">
                        @if (isset($this->reportData['statistics']))
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-6 rounded-xl border-2 border-blue-200 dark:border-blue-700 shadow-lg">
                                    <div class="text-sm font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wide">Total Processed</div>
                                    <div class="text-3xl font-bold text-blue-900 dark:text-blue-100 mt-2">{{ $this->reportData['statistics']['total_processed'] }}</div>
                                    <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">Applications processed</div>
                                </div>
                                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-6 rounded-xl border-2 border-green-200 dark:border-green-700 shadow-lg">
                                    <div class="text-sm font-semibold text-green-700 dark:text-green-300 uppercase tracking-wide">Approved</div>
                                    <div class="text-3xl font-bold text-green-900 dark:text-green-100 mt-2">{{ $this->reportData['statistics']['approved_count'] }}</div>
                                    <div class="text-xs text-green-600 dark:text-green-400 mt-1">Approved applications</div>
                                </div>
                                <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 p-6 rounded-xl border-2 border-red-200 dark:border-red-700 shadow-lg">
                                    <div class="text-sm font-semibold text-red-700 dark:text-red-300 uppercase tracking-wide">Rejected</div>
                                    <div class="text-3xl font-bold text-red-900 dark:text-red-100 mt-2">{{ $this->reportData['statistics']['rejected_count'] }}</div>
                                    <div class="text-xs text-red-600 dark:text-red-400 mt-1">Rejected applications</div>
                                </div>
                                <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 p-6 rounded-xl border-2 border-purple-200 dark:border-purple-700 shadow-lg">
                                    <div class="text-sm font-semibold text-purple-700 dark:text-purple-300 uppercase tracking-wide">Avg Processing Time</div>
                                    <div class="text-3xl font-bold text-purple-900 dark:text-purple-100 mt-2">{{ round($this->reportData['statistics']['average_processing_time_hours'], 1) }}h</div>
                                    <div class="text-xs text-purple-600 dark:text-purple-400 mt-1">Average processing time</div>
                                </div>
                            </div>
                        @endif

                        @if (isset($this->reportData['data']) && count($this->reportData['data']) > 0)
                            <!-- Approval History Table -->
                            <div class="bg-white border-2 border-gray-200 rounded-xl shadow-lg">
                                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b-2 border-gray-200 rounded-t-xl">
                                    <h3 class="text-xl font-bold text-gray-900">Approval History</h3>
                                    <p class="text-sm text-gray-600 mt-1">{{ count($this->reportData['data']) }} applications found</p>
                                </div>
                                
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gradient-to-r from-gray-100 to-gray-200">
                                            <tr>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-800 uppercase tracking-wider">Employee</th>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-blue-800 uppercase tracking-wider">Department</th>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-800 uppercase tracking-wider">Leave Type</th>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-800 uppercase tracking-wider">Start Date</th>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-800 uppercase tracking-wider">End Date</th>
                                                <th class="px-6 py-4 text-center text-sm font-bold text-blue-800 uppercase tracking-wider">Days</th>
                                                <th class="px-6 py-4 text-center text-sm font-bold text-gray-800 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-800 uppercase tracking-wider">Approved By</th>
                                                <th class="px-6 py-4 text-center text-sm font-bold text-purple-800 uppercase tracking-wider">Processing Time</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach ($this->reportData['data'] as $application)
                                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-bold text-gray-900">{{ $application['employee_name'] }}</div>
                                                        <div class="text-sm text-gray-600">{{ $application['employee_id'] }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-bold rounded-full border border-blue-300">
                                                            {{ $application['department'] }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-gray-200 text-gray-800 border border-gray-300">
                                                            {{ $application['leave_type'] }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $application['start_date'] }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $application['end_date'] }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-blue-100 text-blue-900 border border-blue-300">
                                                            {{ $application['days_count'] }} days
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                                        <span class="inline-flex px-3 py-1 text-sm font-bold rounded-lg border-2
                                                            @if($application['status'] === 'Approved') bg-green-100 text-green-900 border-green-300
                                                            @elseif($application['status'] === 'Rejected') bg-red-100 text-red-900 border-red-300
                                                            @else bg-gray-100 text-gray-800 border-gray-300
                                                            @endif">
                                                            {{ $application['status'] }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $application['approved_by'] }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-purple-100 text-purple-900 border border-purple-300">
                                                            {{ $application['processing_time_hours'] }}h
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-16">
                                <div class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500 mb-4">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">No Approval History</h3>
                                <p class="text-lg text-gray-600 dark:text-gray-400">No approval history found for the selected criteria.</p>
                            </div>
                        @endif
                    </div>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
