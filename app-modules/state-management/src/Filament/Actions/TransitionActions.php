<?php

namespace CorvMC\StateManagement\Filament\Actions;

use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
class TransitionActions
{

  public static function actions(string $stateClass, string $stateColumn = 'state'): array
  {
    $actions = [];

    foreach ($stateClass::getStates() as $state) {
      $actions[] = Action::make('transition_to_' . $state::getName())
        ->visible(fn (Model $record) => $record->{$stateColumn}->canTransitionTo($state))
        ->label($state::getVerb())
        ->icon($state::getIcon())
        ->color($state::getColor())
        ->action(fn (Model $record) => $record->{$stateColumn}->transitionTo($state));
    }

    return $actions;
  }

  public static function make(string $stateClass, string $stateColumn = 'state'): ActionGroup
  {
    $actions = static::actions($stateClass, $stateColumn);
    return ActionGroup::make($actions);
  }
}
