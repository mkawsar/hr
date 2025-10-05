<?php

namespace App\Filament\Pages;

use App\Models\LeaveBalance;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Models\LeaveType;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AdminLeaveBalance extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string $view = 'filament.pages.admin-leave-balance';
    protected static ?string $navigationLabel = 'Admin Leave Management';
    protected static ?string $title = 'Admin Leave Management';
    protected static ?string $navigationGroup = 'Leave Management';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Quick Create Leave Balance')
                    ->schema([
                        Select::make('user_id')
                            ->label('Employee')
                            ->options(User::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('leave_type_id')
                            ->label('Leave Type')
                            ->options(LeaveType::where('active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('year')
                            ->label('Year')
                            ->numeric()
                            ->default(date('Y'))
                            ->required(),
                        TextInput::make('balance')
                            ->label('Balance Days')
                            ->numeric()
                            ->default(0)
                            ->step(0.1)
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(LeaveBalance::query()->with(['user', 'leaveType']))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->badge()
                    ->color('info'),
                TextColumn::make('year')
                    ->label('Year')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->numeric(decimalPlaces: 1)
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state < 5 ? 'warning' : 'success')),
                TextColumn::make('consumed')
                    ->label('Used')
                    ->numeric(decimalPlaces: 1),
                TextColumn::make('accrued')
                    ->label('Earned')
                    ->numeric(decimalPlaces: 1),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                TableAction::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil')
                    ->url(fn (LeaveBalance $record): string => route('filament.admin.resources.leave-balances.edit', $record)),
            ])
            ->bulkActions([
                BulkAction::make('delete')
                    ->label('Delete Selected')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each->delete()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_balance')
                ->label('Create Leave Balance')
                ->icon('heroicon-m-plus')
                ->color('success')
                ->action('createLeaveBalance'),
            Action::make('approve_all_pending')
                ->label('Approve All Pending')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve All Pending Leave Applications')
                ->modalDescription('This will approve all pending leave applications. Are you sure?')
                ->action('approveAllPending'),
        ];
    }

    public function createLeaveBalance(): void
    {
        $data = $this->form->getState();
        
        LeaveBalance::create([
            'user_id' => $data['user_id'],
            'leave_type_id' => $data['leave_type_id'],
            'year' => $data['year'],
            'balance' => $data['balance'],
            'consumed' => 0,
            'accrued' => $data['balance'],
            'carry_forward' => 0,
        ]);

        $this->form->fill();
        $this->resetTable();

        Notification::make()
            ->title('Leave Balance Created')
            ->success()
            ->send();
    }

    public function approveAllPending(): void
    {
        $pendingApplications = LeaveApplication::where('status', 'pending')->get();
        $approvedCount = 0;

        foreach ($pendingApplications as $application) {
            $application->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            $approvedCount++;
        }

        Notification::make()
            ->title('All Pending Applications Approved')
            ->body("Approved {$approvedCount} leave applications")
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->isAdmin();
    }
}
