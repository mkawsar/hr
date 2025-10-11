<x-filament-panels::page>
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full mb-4">
                    <x-heroicon-o-key class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Change Password</h2>
                <p class="text-gray-600 dark:text-gray-400">Update your account password to keep your account secure</p>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3 flex-shrink-0" />
                    <div>
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Password Requirements</h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <ul class="list-disc list-inside space-y-1">
                                <li>At least 8 characters long</li>
                                <li>Contains uppercase and lowercase letters</li>
                                <li>Contains at least one number</li>
                                <li>Contains at least one special character</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{ $this->form }}

            <div class="mt-6 flex justify-end">
                {{ $this->saveAction }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
