<?php

namespace CorvMC\StateManagement\Filament\Actions;

use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class TransitionTableActions
{

  public static function make(string $stateClass, string $stateColumn = 'state'): array
  {
    $actions = [];
    foreach ($stateClass::getStates() as $state) {
      $actions[] = Action::make('transition_to_' . $state::getName())
        ->visible(fn (Model $record) => $record->{$stateColumn}->canTransitionTo($state))
        ->label($state::getVerb())
        ->icon($state::getIcon())
        ->color($state::getColor())
        ->form($state::getForm())
        ->action(fn (Model $record, array $data) => $record->{$stateColumn}->transitionTo($state, $data));
    }

    return $actions;
  }


  /**
   * Create a an ActionGroup actions for transitioning from a state.
   */
  public static function group(string $stateClass, string $stateColumn = 'state'): ActionGroup
  {
    return ActionGroup::make(self::make($stateClass, $stateColumn));
  }
}
