<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Filament\Notifications\Notification;

class ChangePassword extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static string $view = 'filament.pages.change-password';
    protected static ?string $navigationLabel = 'Change Password';
    protected static ?string $title = 'Change Password';
    protected static ?string $navigationGroup = 'Account';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Change Password')
                    ->description('Update your account password to keep your account secure.')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->required()
                            ->currentPassword()
                            ->validationAttribute('current password'),
                        
                        Forms\Components\TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->required()
                            ->rule(PasswordRule::min(8)->mixedCase()->numbers()->symbols())
                            ->validationAttribute('new password'),
                        
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->required()
                            ->same('password')
                            ->validationAttribute('password confirmation'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Change Password')
                ->submit('save')
                ->color('primary'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        // Verify current password
        if (!Hash::check($data['current_password'], $user->password)) {
            Notification::make()
                ->title('Error')
                ->body('The current password is incorrect.')
                ->danger()
                ->send();
            return;
        }

        // Update password
        $user->update([
            'password' => Hash::make($data['password'])
        ]);

        // Clear form
        $this->form->fill();

        Notification::make()
            ->title('Success')
            ->body('Your password has been changed successfully.')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return Auth::check();
    }
}
