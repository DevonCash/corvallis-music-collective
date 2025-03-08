<?php

namespace CorvMC\StateManagement\Filament\Table\Columns;

use CorvMC\StateManagement\AbstractState;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;

class StateColumn
{
  public static function make(string $stateColumn = 'state'): TextColumn
  {
    return TextColumn::make($stateColumn)
      ->formatStateUsing(fn (AbstractState $state): string => $state->getLabel())
      ->badge()
      ->color(fn (AbstractState $state): string => $state->getColor());
  }
}
