<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = "heroicon-o-rectangle-stack";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make("name")
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make("email")
                ->email()
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")->searchable(),

                Tables\Columns\TextColumn::make("email")
                    ->searchable()
                    ->icon(
                        fn($record) => $record->email_verified_at
                            ? "heroicon-s-check-circle"
                            : "heroicon-s-x-circle"
                    )
                    ->iconColor(
                        fn($record) => $record->email_verified_at
                            ? "success"
                            : "gray"
                    )
                    ->iconPosition(IconPosition::After),
                Tables\Columns\TextColumn::make("created_at")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make("updated_at")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])

            ->actions([
                Tables\Actions\Action::make("reset-password")
                    ->icon("heroicon-o-lock-closed")
                    ->color("gray")
                    ->requiresConfirmation()
                    ->label("Reset Password")
                    ->action(function (User $record) {
                        $token = app("auth.password.broker")->createToken(
                            $record
                        );
                        $notification = new \Filament\Notifications\Auth\ResetPassword(
                            $token
                        );
                        $notification->url = \Filament\Facades\Filament::getResetPasswordUrl(
                            $token,
                            $record
                        );
                        $record->notify($notification);
                        ray("Sent", $notification);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ManageUsers::route("/"),
        ];
    }
}
