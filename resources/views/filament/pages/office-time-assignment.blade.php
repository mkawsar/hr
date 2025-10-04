<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Assignment Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            @if($this->form)
                {{ $this->form }}
            @else
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">Form is loading...</p>
                </div>
            @endif
            
            <div class="mt-6 flex justify-end space-x-3">
                <x-filament::button
                    wire:click="assignOfficeTime"
                    color="primary"
                    icon="heroicon-o-clock"
                    x-on:click="
                        if (!confirm('Are you sure you want to assign office time and create daily attendance records for the selected employees?')) {
                            $event.preventDefault();
                        }
                    "
                >
                    Assign Office Time & Create Records
                </x-filament::button>
                
                <x-filament::button
                    tag="a"
                    href="{{ route('filament.admin.resources.users.index', ['tableFilters' => ['has_office_time' => ['value' => '']]]) }}"
                    color="info"
                    icon="heroicon-o-chart-bar"
                >
                    View Assignment Report
                </x-filament::button>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                            <x-heroicon-o-users class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Employees</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->getAssignmentStats()['total_employees'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">With Office Time</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->getAssignmentStats()['employees_with_office_time'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            @php
                                $stats = $this->getAssignmentStats();
                                $total = $stats['total_employees'] ?? 0;
                                $assigned = $stats['employees_with_office_time'] ?? 0;
                                $percentage = $total > 0 ? round(($assigned / $total) * 100, 1) : 0;
                            @endphp
                            {{ $percentage }}% assigned
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Without Office Time</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->getAssignmentStats()['employees_without_office_time'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            @php
                                $stats = $this->getAssignmentStats();
                                $total = $stats['total_employees'] ?? 0;
                                $unassigned = $stats['employees_without_office_time'] ?? 0;
                                $percentage = $total > 0 ? round(($unassigned / $total) * 100, 1) : 0;
                            @endphp
                            {{ $percentage }}% unassigned
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                            <x-heroicon-o-clock class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Schedules</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->getAssignmentStats()['office_time_stats']->count() ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Office Time Assignment Statistics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Office Time Usage -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Office Time Schedule Usage</h3>
                <div class="space-y-3">
                    @forelse(($this->getAssignmentStats()['office_time_stats'] ?? collect()) as $stat)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $stat->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stat->code }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $stat->employee_count }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">employees</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No office time schedules found</p>
                    @endforelse
                </div>
            </div>

            <!-- Department Assignment Status -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Department Assignment Status</h3>
                <div class="space-y-3">
                    @forelse(($this->getAssignmentStats()['department_stats'] ?? collect()) as $stat)
                        @php
                            $assignmentPercentage = $stat->total_employees > 0 ? round(($stat->assigned_employees / $stat->total_employees) * 100, 1) : 0;
                        @endphp
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $stat->department_name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $assignmentPercentage }}% assigned</p>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $assignmentPercentage }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span>{{ $stat->assigned_employees }} assigned</span>
                                <span>{{ $stat->total_employees }} total</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No department data found</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('filament.admin.resources.users.index') }}" 
                   class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                    <x-heroicon-o-users class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3" />
                    <div>
                        <p class="font-medium text-blue-900 dark:text-blue-100">Manage Employees</p>
                        <p class="text-sm text-blue-700 dark:text-blue-300">View and edit employee details</p>
                    </div>
                </a>

                <a href="{{ route('filament.admin.resources.office-times.index') }}" 
                   class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                    <x-heroicon-o-clock class="w-6 h-6 text-green-600 dark:text-green-400 mr-3" />
                    <div>
                        <p class="font-medium text-green-900 dark:text-green-100">Manage Office Times</p>
                        <p class="text-sm text-green-700 dark:text-green-300">Create and edit schedules</p>
                    </div>
                </a>

                <a href="{{ route('filament.admin.resources.users.index', ['tableFilters' => ['has_office_time' => ['value' => 'false']]]) }}" 
                   class="flex items-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition-colors">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-yellow-600 dark:text-yellow-400 mr-3" />
                    <div>
                        <p class="font-medium text-yellow-900 dark:text-yellow-100">Unassigned Employees</p>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">View employees without office time</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>
