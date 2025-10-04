<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    My Leave Balance - {{ $selectedYear }}
                </h3>
                <div class="flex space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        Year {{ $selectedYear }}
                    </span>
                </div>
            </div>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                View your leave balance for the selected year. Shows opening balance, availed days, deducted amount, and remaining balance.
            </p>
            
            {{ $this->table }}
        </div>

        <!-- Leave Balance Summary -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Leave Balance Summary - {{ $selectedYear }}
            </h3>
            
            @php
                $leaveBalances = $this->getTableQuery()->get();
                $totalAccruedDays = $leaveBalances->sum('accrued');
                $totalCarryForwardDays = $leaveBalances->sum('carry_forward');
                $totalAvailedDays = $leaveBalances->sum('consumed');
                $totalRemainingBalance = $leaveBalances->sum('balance');
                $totalDeductedDays = 0; // You can implement deduction logic here
                
                $encashableLeaves = $leaveBalances->where('leaveType.encashable', true);
                $carryForwardLeaves = $leaveBalances->where('leaveType.carry_forward_allowed', true);
            @endphp
            
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalAccruedDays, 2) }}</div>
                    <div class="text-sm text-blue-600 dark:text-blue-400">Total Accrued Days</div>
                </div>
                
                <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($totalCarryForwardDays, 2) }}</div>
                    <div class="text-sm text-indigo-600 dark:text-indigo-400">Total Carry Forward</div>
                </div>
                
                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($totalAvailedDays, 2) }}</div>
                    <div class="text-sm text-yellow-600 dark:text-yellow-400">Total Availed Days</div>
                </div>
                
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($totalDeductedDays, 2) }}</div>
                    <div class="text-sm text-red-600 dark:text-red-400">Total Deducted Amount</div>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($totalRemainingBalance, 2) }}</div>
                    <div class="text-sm text-green-600 dark:text-green-400">Total Remaining Balance</div>
                </div>
            </div>

            <!-- Leave Type Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Encashable Leaves</h4>
                    <div class="space-y-2">
                        @forelse($encashableLeaves as $balance)
                            <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ $balance->leaveType->name }}</span>
                                <span class="text-sm text-green-600 dark:text-green-400">{{ number_format($balance->remaining_days, 2) }} days</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No encashable leaves available</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Carry Forward Leaves (Max 40 days)</h4>
                    <div class="space-y-2">
                        @forelse($carryForwardLeaves as $balance)
                            @php
                                $maxCarryForward = min($balance->balance, $balance->leaveType->max_carry_forward_days ?? 40);
                            @endphp
                            <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <span class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ $balance->leaveType->name }}</span>
                                <span class="text-sm text-blue-600 dark:text-blue-400">{{ number_format($maxCarryForward, 2) }} days</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No carry forward leaves available</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Application History -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Recent Leave Applications - {{ $selectedYear }}
            </h3>
            
            @php
                $recentApplications = \App\Models\LeaveApplication::where('user_id', auth()->id())
                    ->whereYear('applied_at', $selectedYear)
                    ->with('leaveType')
                    ->orderBy('applied_at', 'desc')
                    ->limit(5)
                    ->get();
            @endphp
            
            <div class="space-y-3">
                @forelse($recentApplications as $application)
                    <div class="flex justify-between items-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @if($application->status === 'approved') bg-green-100 text-green-800
                                    @elseif($application->status === 'rejected') bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ ucfirst($application->status) }}
                                </span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $application->leaveType->name }}</span>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ \Carbon\Carbon::parse($application->start_date)->format('M d') }} - 
                                {{ \Carbon\Carbon::parse($application->end_date)->format('M d, Y') }} 
                                ({{ $application->days_count }} days)
                            </div>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($application->applied_at)->format('M d, Y') }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No leave applications found for this year</p>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>