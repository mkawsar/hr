<?php

namespace App\Filament\Pages;

use App\Models\EarnedLeaveConfig;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Actions\Action as PageAction;
use Filament\Notifications\Notification;

class EarnedLeaveConfiguration extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.earned-leave-configuration';
    protected static ?string $navigationLabel = 'Earned Leave Configuration';
    protected static ?string $title = 'Earned Leave Configuration';
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
                Section::make('Create New Configuration')
                    ->schema([
                        TextInput::make('name')
                            ->label('Configuration Name')
                            ->required()
                            ->placeholder('e.g., 2025 Configuration'),
                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Describe this configuration...'),
                        TextInput::make('working_days_per_earned_leave')
                            ->label('Working Days per Earned Leave')
                            ->numeric()
                            ->required()
                            ->default(15)
                            ->helperText('How many working days are needed to earn 1 leave day'),
                        TextInput::make('max_earned_leave_days')
                            ->label('Maximum Earned Leave Days')
                            ->numeric()
                            ->required()
                            ->default(40)
                            ->helperText('Maximum total earned leave days allowed'),
                        Select::make('year')
                            ->label('Apply to Year')
                            ->options(function () {
                                $years = [];
                                for ($i = date('Y') - 2; $i <= date('Y') + 2; $i++) {
                                    $years[$i] = $i;
                                }
                                $years[''] = 'All Years (Default)';
                                return $years;
                            })
                            ->default(null)
                            ->helperText('Leave empty for default configuration'),
                        Toggle::make('include_weekends')
                            ->label('Include Weekends')
                            ->default(false)
                            ->helperText('Whether to include weekends in working days calculation'),
                        Toggle::make('include_holidays')
                            ->label('Include Holidays')
                            ->default(false)
                            ->helperText('Whether to include holidays in working days calculation'),
                        Toggle::make('include_absent_days')
                            ->label('Include Absent Days')
                            ->default(false)
                            ->helperText('Whether to include absent days in working days calculation'),
                        Toggle::make('active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Whether this configuration is currently active'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(EarnedLeaveConfig::query())
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                TextColumn::make('working_days_per_earned_leave')
                    ->label('Days per Leave')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('max_earned_leave_days')
                    ->label('Max Days')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                TextColumn::make('year')
                    ->label('Year')
                    ->formatStateUsing(fn ($state) => $state ?: 'All Years')
                    ->badge()
                    ->color('gray'),
                BooleanColumn::make('active')
                    ->label('Active'),
                TextColumn::make('include_weekends')
                    ->label('Weekends')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                TextColumn::make('include_holidays')
                    ->label('Holidays')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                TextColumn::make('include_absent_days')
                    ->label('Absent Days')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        TextInput::make('name')
                            ->label('Configuration Name')
                            ->required(),
                        Textarea::make('description')
                            ->label('Description'),
                        TextInput::make('working_days_per_earned_leave')
                            ->label('Working Days per Earned Leave')
                            ->numeric()
                            ->required(),
                        TextInput::make('max_earned_leave_days')
                            ->label('Maximum Earned Leave Days')
                            ->numeric()
                            ->required(),
                        Select::make('year')
                            ->label('Apply to Year')
                            ->options(function () {
                                $years = [];
                                for ($i = date('Y') - 2; $i <= date('Y') + 2; $i++) {
                                    $years[$i] = $i;
                                }
                                $years[''] = 'All Years (Default)';
                                return $years;
                            }),
                        Toggle::make('include_weekends')
                            ->label('Include Weekends'),
                        Toggle::make('include_holidays')
                            ->label('Include Holidays'),
                        Toggle::make('include_absent_days')
                            ->label('Include Absent Days'),
                        Toggle::make('active')
                            ->label('Active'),
                    ]),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('create_config')
                ->label('Create Configuration')
                ->icon('heroicon-m-plus')
                ->color('success')
                ->action('createConfiguration'),
            PageAction::make('create_default')
                ->label('Create Default Config')
                ->icon('heroicon-m-cog-6-tooth')
                ->color('primary')
                ->action('createDefaultConfiguration'),
        ];
    }

    public function createConfiguration(): void
    {
        $data = $this->form->getState();
        
        EarnedLeaveConfig::create($data);

        $this->form->fill();
        $this->resetTable();

        Notification::make()
            ->title('Configuration Created')
            ->success()
            ->send();
    }

    public function createDefaultConfiguration(): void
    {
        EarnedLeaveConfig::createDefaultIfNotExists();

        $this->resetTable();

        Notification::make()
            ->title('Default Configuration Created')
            ->body('Default configuration has been created if it didn\'t exist.')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->isAdmin();
    }
}
