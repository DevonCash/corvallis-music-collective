<?php

namespace CorvMC\PracticeSpace\Filament\Actions;

use Carbon\CarbonInterface;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard;

class ManageBookingAction
{
    public static function make(): Action
    {
        return Action::make('manageBooking')
            ->label('Manage Bookings')
            ->record(fn($arguments) => Booking::find($arguments['booking_id']))
            ->url(fn($record) => route('practice-space.bookings.index', ['room' => $record->id]))
            ->color('primary')
            ->modalIcon(null)
            ->modalHeading('Manage Booking')
            ->form([
                Placeholder::make('room')
                    ->label('Practice Room')
                    ->content(fn($record) => $record->room->name),
                Placeholder::make('date')
                    ->label('Date')
                    ->content(fn($record) => $record->start_time->calendar() . ' until ' . $record->end_time->format('g:i A')),
                Placeholder::make('start_time')
                    ->label('Starts at')
                    ->content(fn($record) =>
                    $record->start_time->format('g:i A') . ' - ' .
                        $record->end_time->format('g:i A') . ' (' . $record->start_time->diffForHumans($record->end_time, CarbonInterface::DIFF_ABSOLUTE) . ')'),
                Placeholder::make('cost')
                    ->label('Total')
                    ->content(fn($record) => '$' . number_format($record->total_cost, 2)),
            ])
            ->actions(
                array_map(fn($action) => $action->getName() === 'transition_to_cancelled' ?
                    $action->cancelParentActions() : $action, BookingState::makeTransitionActions())
            )
            ->modalCancelAction(false)
            ->modalSubmitAction(false);
    }
}
