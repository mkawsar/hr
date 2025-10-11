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
                    <div class="space-y-4">
                        @if (isset($this->reportData['data']) && count($this->reportData['data']) > 0)
                            <!-- Debug Information (remove in production) -->
                            @if(config('app.debug'))
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                    <h4 class="font-semibold text-yellow-800 mb-2">Debug Information:</h4>
                                    <p class="text-sm text-yellow-700">Report Type: {{ $this->reportType }}</p>
                                    <p class="text-sm text-yellow-700">Data Count: {{ count($this->reportData['data'] ?? []) }}</p>
                                    <p class="text-sm text-yellow-700">First employee data structure:</p>
                                    <pre class="text-xs bg-white p-2 rounded border mt-2 overflow-auto">{{ json_encode($this->reportData['data'][0] ?? [], JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @endif
                            @foreach ($this->reportData['data'] as $employee)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex justify-between items-center mb-3">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ $employee['name'] }} ({{ $employee['employee_id'] }})
                                        </h3>
                                        <div class="text-sm text-gray-600">
                                            <strong>Department:</strong> <span class="text-blue-600 font-semibold">{{ $employee['department'] ?? 'Not Available' }}</span> - 
                                            <strong>Designation:</strong> <span class="text-green-600 font-semibold">{{ $employee['designation'] ?? 'Not Available' }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                                        <div class="bg-white p-3 rounded border">
                                            <div class="text-sm text-gray-600">Total Balance</div>
                                            <div class="text-xl font-bold text-green-600">{{ $employee['total_balance'] }}</div>
                                        </div>
                                        <div class="bg-white p-3 rounded border">
                                            <div class="text-sm text-gray-600">Total Consumed</div>
                                            <div class="text-xl font-bold text-red-600">{{ $employee['total_consumed'] }}</div>
                                        </div>
                                    </div>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consumed</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Accrued</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Carry Forward</th>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Available</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach ($employee['leave_balances'] as $balance)
                                                    <tr>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $balance['leave_type'] }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">{{ $balance['leave_code'] }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $balance['balance'] }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $balance['consumed'] }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $balance['accrued'] }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $balance['carry_forward'] }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $balance['total_available'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-8 text-gray-500">
                                No leave balance data found for the selected criteria.
                            </div>
                        @endif
                    </div>

                @elseif ($this->reportType === 'summary')
                    <!-- Leave Summary Report -->
                    <div class="space-y-4">
                        @if (isset($this->reportData['statistics']))
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <div class="text-sm text-blue-600">Total Applications</div>
                                    <div class="text-2xl font-bold text-blue-900">{{ $this->reportData['statistics']['total_applications'] }}</div>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                    <div class="text-sm text-green-600">Approved</div>
                                    <div class="text-2xl font-bold text-green-900">{{ $this->reportData['statistics']['approved_applications'] }}</div>
                                </div>
                                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                    <div class="text-sm text-yellow-600">Pending</div>
                                    <div class="text-2xl font-bold text-yellow-900">{{ $this->reportData['statistics']['pending_applications'] }}</div>
                                </div>
                                <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                                    <div class="text-sm text-red-600">Rejected</div>
                                    <div class="text-2xl font-bold text-red-900">{{ $this->reportData['statistics']['rejected_applications'] }}</div>
                                </div>
                            </div>
                        @endif

                        @if (isset($this->reportData['data']) && count($this->reportData['data']) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied At</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($this->reportData['data'] as $application)
                                            <tr>
                                                <td class="px-3 py-2 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">{{ $application['employee_name'] }}</div>
                                                    <div class="text-sm text-gray-500">{{ $application['employee_id'] }}</div>
                                                </td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $application['department'] }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $application['leave_type'] }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $application['start_date'] }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $application['end_date'] }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $application['days_count'] }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                        @if($application['status'] === 'Approved') bg-green-100 text-green-800
                                                        @elseif($application['status'] === 'Pending') bg-yellow-100 text-yellow-800
                                                        @elseif($application['status'] === 'Rejected') bg-red-100 text-red-800
                                                        @else bg-gray-100 text-gray-800
                                                        @endif">
                                                        {{ $application['status'] }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $application['applied_at'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                No leave applications found for the selected criteria.
                            </div>
                        @endif
                    </div>

                @elseif ($this->reportType === 'analysis')
                    <!-- Leave Analysis Report -->
                    <div class="space-y-6">
                        @if (isset($this->reportData['data']['summary']))
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <div class="text-sm text-blue-600">Total Employees</div>
                                    <div class="text-2xl font-bold text-blue-900">{{ $this->reportData['data']['total_employees'] }}</div>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                    <div class="text-sm text-green-600">Overall Utilization</div>
                                    <div class="text-2xl font-bold text-green-900">{{ $this->reportData['data']['summary']['overall_utilization_rate'] }}%</div>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                    <div class="text-sm text-purple-600">Approval Rate</div>
                                    <div class="text-2xl font-bold text-purple-900">{{ $this->reportData['data']['summary']['approval_rate'] }}%</div>
                                </div>
                            </div>
                        @endif

                        <div class="text-center py-8 text-gray-500">
                            <p>Detailed analysis data is available in the Excel and PDF exports.</p>
                            <p class="text-sm mt-2">Use the export buttons above to download the complete analysis report.</p>
                        </div>
                    </div>

                @elseif ($this->reportType === 'approval-history')
                    <!-- Leave Approval History Report -->
                    <div class="space-y-4">
                        @if (isset($this->reportData['statistics']))
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <div class="text-sm text-blue-600">Total Processed</div>
                                    <div class="text-2xl font-bold text-blue-900">{{ $this->reportData['statistics']['total_processed'] }}</div>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                    <div class="text-sm text-green-600">Approved</div>
                                    <div class="text-2xl font-bold text-green-900">{{ $this->reportData['statistics']['approved_count'] }}</div>
                                </div>
                                <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                                    <div class="text-sm text-red-600">Rejected</div>
                                    <div class="text-2xl font-bold text-red-900">{{ $this->reportData['statistics']['rejected_count'] }}</div>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                    <div class="text-sm text-purple-600">Avg Processing Time</div>
                                    <div class="text-2xl font-bold text-purple-900">{{ round($this->reportData['statistics']['average_processing_time_hours'], 1) }}h</div>
                                </div>
                            </div>
                        @endif

                        @if (isset($this->reportData['data']) && count($this->reportData['data']) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processing Time</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($this->reportData['data'] as $application)
                                            <tr>
                                                <td class="px-3 py-2 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">{{ $application['employee_name'] }}</div>
                                                    <div class="text-sm text-gray-500">{{ $application['employee_id'] }}</div>
                                                </td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $application['department'] }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $application['leave_type'] }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $application['start_date'] }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $application['end_date'] }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $application['days_count'] }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                        @if($application['status'] === 'Approved') bg-green-100 text-green-800
                                                        @elseif($application['status'] === 'Rejected') bg-red-100 text-red-800
                                                        @else bg-gray-100 text-gray-800
                                                        @endif">
                                                        {{ $application['status'] }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $application['approved_by'] }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $application['processing_time_hours'] }}h</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                No approval history found for the selected criteria.
                            </div>
                        @endif
                    </div>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
