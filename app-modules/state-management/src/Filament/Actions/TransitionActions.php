<?php

namespace CorvMC\StateManagement\Filament\Actions;

use Filament\Actions\ActionGroup;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
class TransitionActions
{
  public static function make(string $stateClass, string $stateColumn = 'state'): ActionGroup
  {
    $actions = [];

    foreach ($stateClass::getStates() as $state) {
      $actions[] = Action::make('transition_to_' . $state::getName())
        ->visible(fn (Model $record) => $record->{$stateColumn}->canTransitionTo($state))
        ->label($state::getLabel())
        ->icon($state::getIcon())
        ->color($state::getColor())
        ->action(fn (Model $record) => $record->{$stateColumn}->transitionTo($state));
    }

    return ActionGroup::make($actions);
  }
}