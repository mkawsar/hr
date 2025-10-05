<?php

namespace App\Filament\Pages;

use App\Services\YearlyReportService;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Exports\YearlyReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class YearlyReports extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.yearly-reports';
    protected static ?string $navigationLabel = 'Yearly Reports';
    protected static ?string $title = 'Yearly Attendance & Leave Reports';
    protected static ?string $navigationGroup = 'Reports';

    public ?array $data = [];
    public ?int $selectedYear = null;
    public ?string $reportType = 'individual';
    public ?Collection $reportData = null;

    public function mount(): void
    {
        $this->selectedYear = date('Y');
        $this->form->fill([
            'year' => $this->selectedYear,
            'report_type' => $this->reportType,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Report Configuration')
                    ->schema([
                        Select::make('year')
                            ->label('Select Year')
                            ->options(function () {
                                $years = [];
                                for ($i = date('Y') - 5; $i <= date('Y'); $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            })
                            ->default(date('Y'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->selectedYear = $state;
                                $this->generateReport();
                            }),
                        Select::make('report_type')
                            ->label('Report Type')
                            ->options([
                                'individual' => 'Individual Employee Reports',
                                'department' => 'Department Summary',
                                'summary' => 'Overall Summary',
                            ])
                            ->default('individual')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->reportType = $state;
                                $this->generateReport();
                            }),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        if ($this->reportType === 'department') {
            return $this->getDepartmentTable($table);
        } elseif ($this->reportType === 'summary') {
            return $this->getSummaryTable($table);
        } else {
            return $this->getIndividualTable($table);
        }
    }

    private function getIndividualTable(Table $table): Table
    {
        return $table
            ->query($this->getIndividualQuery())
            ->columns([
                TextColumn::make('user.employee_id')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Employee Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.department')
                    ->label('Department')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attendance.present')
                    ->label('Present Days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('attendance.absent')
                    ->label('Absent Days')
                    ->numeric()
                    ->sortable()
                    ->color('danger'),
                TextColumn::make('attendance.late')
                    ->label('Late Days')
                    ->numeric()
                    ->sortable()
                    ->color('warning'),
                TextColumn::make('leave.total_leave_days')
                    ->label('Leave Days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('early_late.total_late_minutes')
                    ->label('Total Late (min)')
                    ->numeric()
                    ->sortable()
                    ->color('warning'),
                TextColumn::make('early_late.total_early_minutes')
                    ->label('Total Early (min)')
                    ->numeric()
                    ->sortable()
                    ->color('info'),
                TextColumn::make('summary.attendance_rate')
                    ->label('Attendance Rate')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable()
                    ->color(fn ($state) => $state >= 90 ? 'success' : ($state >= 80 ? 'warning' : 'danger')),
                TextColumn::make('summary.punctuality_score')
                    ->label('Punctuality Score')
                    ->formatStateUsing(fn ($state) => $state . '/100')
                    ->sortable()
                    ->color(fn ($state) => $state >= 90 ? 'success' : ($state >= 80 ? 'warning' : 'danger')),
                TextColumn::make('summary.overall_performance')
                    ->label('Performance')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Excellent' => 'success',
                        'Good' => 'info',
                        'Average' => 'warning',
                        'Below Average' => 'danger',
                        'Poor' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('summary.attendance_rate', 'desc');
    }

    private function getDepartmentTable(Table $table): Table
    {
        return $table
            ->query($this->getDepartmentQuery())
            ->columns([
                TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_users')
                    ->label('Total Employees')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('average_attendance_rate')
                    ->label('Avg Attendance Rate')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable()
                    ->color(fn ($state) => $state >= 90 ? 'success' : ($state >= 80 ? 'warning' : 'danger')),
                TextColumn::make('average_punctuality_score')
                    ->label('Avg Punctuality Score')
                    ->formatStateUsing(fn ($state) => $state . '/100')
                    ->sortable()
                    ->color(fn ($state) => $state >= 90 ? 'success' : ($state >= 80 ? 'warning' : 'danger')),
                TextColumn::make('total_absent_days')
                    ->label('Total Absent Days')
                    ->numeric()
                    ->sortable()
                    ->color('danger'),
                TextColumn::make('total_leave_days')
                    ->label('Total Leave Days')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('average_attendance_rate', 'desc');
    }

    private function getSummaryTable(Table $table): Table
    {
        return $table
            ->query($this->getSummaryQuery())
            ->columns([
                TextColumn::make('metric')
                    ->label('Metric')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Value')
                    ->formatStateUsing(fn ($state, $record) => $record['format'] === 'percentage' ? $state . '%' : $state)
                    ->color(fn ($state, $record) => $record['color'] ?? 'gray'),
                TextColumn::make('description')
                    ->label('Description')
                    ->wrap(),
            ]);
    }

    private function getIndividualQuery(): Builder
    {
        // Return a dummy query since we're using custom data
        return \App\Models\User::query()->whereRaw('1 = 0');
    }

    private function getDepartmentQuery(): Builder
    {
        // Return a dummy query since we're using custom data
        return \App\Models\User::query()->whereRaw('1 = 0');
    }

    private function getSummaryQuery(): Builder
    {
        // Return a dummy query since we're using custom data
        return \App\Models\User::query()->whereRaw('1 = 0');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_report')
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
        
        $service = new YearlyReportService();
        
        if ($this->reportType === 'department') {
            $this->reportData = $service->generateDepartmentSummary($this->selectedYear);
        } else {
            $this->reportData = $service->generateYearlyAttendanceReport($this->selectedYear);
        }
        
        $this->resetTable();
        
        Notification::make()
            ->title('Report Generated')
            ->body("Yearly report for {$this->selectedYear} has been generated successfully.")
            ->success()
            ->send();
    }

    public function exportToExcel()
    {
        if (!$this->reportData) {
            Notification::make()
                ->title('No Data')
                ->body('Please generate a report first.')
                ->warning()
                ->send();
            return;
        }

        try {
            $filename = "yearly_report_{$this->selectedYear}_{$this->reportType}_" . date('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download(
                new YearlyReportExport($this->reportData, $this->reportType, $this->selectedYear),
                $filename
            );
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export Failed')
                ->body('Failed to export report: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function exportToPDF()
    {
        if (!$this->reportData) {
            Notification::make()
                ->title('No Data')
                ->body('Please generate a report first.')
                ->warning()
                ->send();
            return;
        }

        try {
            $filename = "yearly_report_{$this->selectedYear}_{$this->reportType}_" . date('Y-m-d_H-i-s') . '.pdf';
            
            $pdf = Pdf::loadView('reports.yearly-report-pdf', [
                'data' => $this->reportData,
                'reportType' => $this->reportType,
                'year' => $this->selectedYear,
                'generatedAt' => now(),
            ]);
            
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export Failed')
                ->body('Failed to export PDF: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->isAdmin();
    }
}
