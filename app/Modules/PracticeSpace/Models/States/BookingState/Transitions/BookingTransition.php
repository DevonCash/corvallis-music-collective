<?php

namespace App\Modules\PracticeSpace\Models\States\BookingState\Transitions;

use AlpineIO\Filament\ModelStates\Concerns\ProvidesSpatieTransitionToFilament;
use AlpineIO\Filament\ModelStates\Contracts\FilamentSpatieTransition;
use Spatie\ModelStates\Transition as SpatieTransition;
use App\Modules\PracticeSpace\Models\Booking;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\ModelStates\Transition;
use Override;


abstract class BookingTransition extends Transition implements FilamentSpatieTransition, HasLabel, HasColor
{
    use ProvidesSpatieTransitionToFilament;
    public static string $label;
    public static string $color;
    public static string $to_state;

    public function __construct(
        protected readonly Booking $booking
    ) {}


    #[Override]
    public static function fill(Model $model, array $formData): SpatieTransition
    {
        $formData = Arr::mapWithKeys(
            $formData,
            static fn(mixed $value, string $key): array => [Str::camel($key) => $value],
        );

        return new static($model, $formData);
    }

    public function handle(): Booking
    {
        $stateClass = static::$to_state;
        $this->booking->state = new $stateClass($this->booking);
        $this->booking->save();
        return $this->booking;
    }

    function getLabel(): string
    {
        return __(static::$label);
    }

    function getColor(): string
    {
        return static::$color;
    }
}
