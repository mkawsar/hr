<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class LeaveReports extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.leave-reports';
    protected static ?string $navigationLabel = 'Leave Reports';
    protected static ?string $title = 'Leave Reports';
    protected static ?string $navigationGroup = 'Reports';
    
    // Disable widgets on this page
    public function getWidgets(): array
    {
        return [];
    }

    public ?array $data = [];
    public $reportType = 'balance';
    public $reportData = null;
    public $loading = false;

    public function mount(): void
    {
        $this->form->fill([
            'report_type' => 'balance',
            'year' => 2025, // Use 2025 as default since that's where the data is
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->endOfMonth(),
            'department_id' => null,
            'status' => null,
            'approver_id' => null,
        ]);
        
        // Set the data property for form submission
        $this->data = [
            'report_type' => 'balance',
            'year' => 2025,
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->endOfMonth(),
            'department_id' => null,
            'status' => null,
            'approver_id' => null,
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Report Configuration')
                    ->schema([
                        Select::make('report_type')
                            ->label('Report Type')
                            ->options([
                                'balance' => 'Employee Leave Balance Report',
                                'summary' => 'Leave Summary Report',
                                'analysis' => 'Leave Analysis Report',
                                'approval-history' => 'Leave Approval History Report',
                            ])
                            ->default('balance')
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                $this->reportType = $state;
                                $this->reportData = null;
                            })
                            ->live(),
                    ])
                    ->columns(1),

                Section::make('Filters')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('year')
                                    ->label('Year')
                                    ->options(function () {
                                        $years = [];
                                        for ($i = Carbon::now()->year - 5; $i <= Carbon::now()->year + 1; $i++) {
                                            $years[$i] = $i;
                                        }
                                        return $years;
                                    })
                                    ->default(2025)
                                    ->visible(fn () => in_array($this->reportType, ['balance', 'analysis'])),

                                Select::make('department_id')
                                    ->label('Department')
                                    ->options(function () {
                                        try {
                                            // Call controller method directly instead of HTTP request
                                            $controller = new \App\Http\Controllers\LeaveReportsController();
                                            $request = new \Illuminate\Http\Request();
                                            $response = $controller->getFilterOptions();
                                            $data = $response->getData(true);
                                            
                                            if (isset($data['departments'])) {
                                                return collect($data['departments'])->pluck('name', 'id')->toArray();
                                            }
                                        } catch (\Exception $e) {
                                            // Handle error silently
                                        }
                                        return [];
                                    })
                                    ->searchable()
                                    ->placeholder('All Departments'),

                                DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->default(Carbon::now()->startOfMonth())
                                    ->visible(fn () => in_array($this->reportType, ['summary', 'approval-history'])),

                                DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->default(Carbon::now()->endOfMonth())
                                    ->visible(fn () => in_array($this->reportType, ['summary', 'approval-history'])),

                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->placeholder('All Statuses')
                                    ->visible(fn () => in_array($this->reportType, ['summary', 'approval-history'])),

                                Select::make('approver_id')
                                    ->label('Approver')
                                    ->options(function () {
                                        try {
                                            // Call controller method directly instead of HTTP request
                                            $controller = new \App\Http\Controllers\LeaveReportsController();
                                            $request = new \Illuminate\Http\Request();
                                            $response = $controller->getFilterOptions();
                                            $data = $response->getData(true);
                                            
                                            if (isset($data['approvers'])) {
                                                return collect($data['approvers'])->pluck('name', 'id')->toArray();
                                            }
                                        } catch (\Exception $e) {
                                            // Handle error silently
                                        }
                                        return [];
                                    })
                                    ->searchable()
                                    ->placeholder('All Approvers')
                                    ->visible(fn () => $this->reportType === 'approval-history'),
                            ]),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Report')
                ->icon('heroicon-m-arrow-path')
                ->color('primary')
                ->action('generateReport'),
            Action::make('export_excel')
                ->label('Export to Excel')
                ->icon('heroicon-m-document-arrow-down')
                ->color('success')
                ->action('exportToExcel')
                ->visible(fn () => $this->reportData !== null),
            Action::make('export_pdf')
                ->label('Export to PDF')
                ->icon('heroicon-m-document-text')
                ->color('danger')
                ->action('exportToPDF')
                ->visible(fn () => $this->reportData !== null),
        ];
    }

    public function generateReport(): void
    {
        $this->validate();
        $this->loading = true;

        try {
            $params = $this->data;
            $params['format'] = 'json';
            
            // Update reportType from form data
            $this->reportType = $params['report_type'] ?? 'balance';

            // Call controller method directly instead of HTTP request
            $controller = new \App\Http\Controllers\LeaveReportsController();
            $request = new \Illuminate\Http\Request();
            $request->merge($params);
            
            $response = match ($this->reportType) {
                'balance' => $controller->leaveBalanceReport($request),
                'summary' => $controller->leaveSummaryReport($request),
                'analysis' => $controller->leaveAnalysisReport($request),
                'approval-history' => $controller->leaveApprovalHistoryReport($request),
                default => $controller->leaveBalanceReport($request),
            };

            $this->reportData = $response->getData(true);
            $employeeCount = isset($this->reportData['data']) ? count($this->reportData['data']) : 0;
            Notification::make()
                ->title('Report Generated')
                ->body("Report has been generated successfully. Found {$employeeCount} employees.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to generate report: ' . $e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->loading = false;
        }
    }

    public function exportToExcel(): void
    {
        try {
            $params = $this->data;
            $params['format'] = 'excel';
            
            // Update reportType from form data
            $reportType = $params['report_type'] ?? 'balance';

            // Generate the download URL using the current domain
            $baseUrl = request()->getSchemeAndHttpHost();
            $routeName = match ($reportType) {
                'balance' => 'reports.leave.balance',
                'summary' => 'reports.leave.summary',
                'analysis' => 'reports.leave.analysis',
                'approval-history' => 'reports.leave.approval-history',
                default => 'reports.leave.balance',
            };

            $downloadUrl = $baseUrl . route($routeName, [], false) . '?' . http_build_query($params);
            $this->redirect($downloadUrl);

            Notification::make()
                ->title('Export Started')
                ->body('Excel export will start downloading shortly.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to export to Excel: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function exportToPDF(): void
    {
        try {
            $params = $this->data;
            $params['format'] = 'pdf';
            
            // Update reportType from form data
            $reportType = $params['report_type'] ?? 'balance';

            // Generate the download URL using the current domain
            $baseUrl = request()->getSchemeAndHttpHost();
            $routeName = match ($reportType) {
                'balance' => 'reports.leave.balance',
                'summary' => 'reports.leave.summary',
                'analysis' => 'reports.leave.analysis',
                'approval-history' => 'reports.leave.approval-history',
                default => 'reports.leave.balance',
            };

            $downloadUrl = $baseUrl . route($routeName, [], false) . '?' . http_build_query($params);
            $this->redirect($downloadUrl);

            Notification::make()
                ->title('Export Started')
                ->body('PDF export will start downloading shortly.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to export to PDF: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }


    public function getReportColumns(): array
    {
        return match ($this->reportType) {
            'balance' => [
                'employee_id' => 'Employee ID',
                'name' => 'Employee Name',
                'department' => 'Department',
                'designation' => 'Designation',
                'total_balance' => 'Total Balance',
                'total_consumed' => 'Total Consumed',
            ],
            'summary' => [
                'employee_id' => 'Employee ID',
                'employee_name' => 'Employee Name',
                'department' => 'Department',
                'leave_type' => 'Leave Type',
                'start_date' => 'Start Date',
                'end_date' => 'End Date',
                'days_count' => 'Days',
                'status' => 'Status',
                'applied_at' => 'Applied At',
            ],
            'analysis' => [
                'year' => 'Year',
                'total_employees' => 'Total Employees',
                'total_leave_types' => 'Total Leave Types',
                'total_departments' => 'Total Departments',
            ],
            'approval-history' => [
                'employee_id' => 'Employee ID',
                'employee_name' => 'Employee Name',
                'department' => 'Department',
                'leave_type' => 'Leave Type',
                'start_date' => 'Start Date',
                'end_date' => 'End Date',
                'days_count' => 'Days',
                'status' => 'Status',
                'approved_by' => 'Approved By',
                'approved_at' => 'Approved At',
                'processing_time_hours' => 'Processing Time (Hours)',
            ],
            default => [],
        };
    }
}
