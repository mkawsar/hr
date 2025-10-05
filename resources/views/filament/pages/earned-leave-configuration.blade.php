<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Configuration Form -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Create New Earned Leave Configuration
                </h3>
                {{ $this->form }}
            </div>
        </div>

        <!-- Information Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-calculator class="h-6 w-6 text-blue-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Working Days per Leave
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Configurable
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
                            <x-heroicon-o-shield-check class="h-6 w-6 text-green-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Maximum Leave Days
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Configurable
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
                            <x-heroicon-o-calendar-days class="h-6 w-6 text-purple-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Year-Specific Rules
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Supported
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration Table -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Earned Leave Configurations
                </h3>
            </div>
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>

        <!-- Help Section -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-o-information-circle class="h-5 w-5 text-blue-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        How Earned Leave Calculation Works
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Working Days per Leave:</strong> Set how many working days are needed to earn 1 leave day</li>
                            <li><strong>Maximum Leave Days:</strong> Set the maximum total earned leave days allowed</li>
                            <li><strong>Include Weekends:</strong> Whether to count weekends as working days</li>
                            <li><strong>Include Holidays:</strong> Whether to count holidays as working days</li>
                            <li><strong>Include Absent Days:</strong> Whether to count absent days as working days</li>
                            <li><strong>Year-Specific:</strong> Create different rules for different years</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
