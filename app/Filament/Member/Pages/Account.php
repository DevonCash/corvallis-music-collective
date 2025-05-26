<?php

namespace App\Filament\Member\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;

class Account extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static string $view = 'filament.member.pages.account';

    public function logoutAction(): Action
    {
        return Action::make('logout')
            ->label('Logout')
            ->action(function () {
                // Logic to log out the user
                // For example, you might want to call Auth::logout();
                $this->notify('success', 'Logged out successfully.');
            })
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Logout')
            ->modalDescription('Are you sure you want to log out?')
            ->modalSubmitActionLabel('Logout')
            ->modalCancelActionLabel('Cancel')
        ;
    }
    public function deleteAccountAction(): Action
    {
        return Action::make('deleteAccount')
            ->label('Delete Account')
            ->action(function () {
                // Logic to delete the account
                // For example, you might want to call a method on the user model
                // Auth::user()->delete();
                $this->notify('success', 'Account deleted successfully.');
            })
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete Account')
            ->modalDescription('Are you sure you want to delete your account? This action cannot be undone.')
            ->modalSubmitActionLabel('Delete Account')
            ->modalCancelActionLabel('Cancel')
        ;
    }
}
