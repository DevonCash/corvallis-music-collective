<?php

namespace CorvMC\Productions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use CorvMC\StateManagement\Casts\State;
use CorvMC\StateManagement\Traits\HasStates;
use App\Models\User;
use CorvMC\Productions\Models\States\ProductionState;
use CorvMC\Productions\Models\States\PlanningState;
use CorvMC\Productions\Models\ProductionTag;
use CorvMC\Productions\Models\Act;
use CorvMC\Productions\Models\States\PublishedState;
use CorvMC\Productions\Models\States\ActiveState;
use CorvMC\Productions\Models\States\FinishedState;
use CorvMC\Productions\Models\States\ArchivedState;
use CorvMC\Productions\Models\States\RescheduledState;
use CorvMC\Productions\Models\States\CancelledState;
use Filament\Actions\Action;

class Production extends Model
{
    use HasFactory, HasStates;

    protected $fillable = [
        'title',
        'description',
        'venue_id',
        'start_date',
        'end_date',
        'status',
        'capacity',
        'poster',
        'ready_to_start',
        'wrap_up_complete',
        'ended_at',
        'ticket_link',
        'production_lead_id',
        'wrap_up_data',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'ended_at' => 'datetime',
        'ready_to_start' => 'boolean',
        'wrap_up_complete' => 'boolean',
        'status' => State::class.':'.ProductionState::class,
        'wrap_up_data' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('uncancelled', function ($builder) {
            $builder->whereNot('status', 'cancelled');
        });
    }

    /**
     * Get the state column name.
     */
    public function getStateColumn(): string
    {
        return 'status';
    }

    /**
     * Get the current state of the production.
     */
    public function getStateAttribute(): string
    {
        return $this->status;
    }

    /**
     * Set the state of the production.
     */
    public function setStateAttribute(string $state): void
    {
        $this->status = $state;
    }

    /**
     * Get all possible transitions from the current state.
     */
    public function getPossibleTransitions(): array
    {
        return $this->state::getAllowedTransitions();
    }

    /**
     * Get the state transition actions for Filament.
     */
    public function getStateTransitionActions(): array
    {
        $actions = [];
        foreach ($this->getPossibleTransitions() as $state => $label) {
            $actions[] = Action::make("transition_to_{$state}")
                ->label($label)
                ->icon($this->state::getIcon())
                ->color($this->state::getColor())
                ->form($this->state::getForm())
                ->action(function (array $data) use ($state) {
                    $this->state::transitionTo($this, $state, $data);
                });
        }
        return $actions;
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function productionLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'production_lead_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductionTag::class, 'production_tag');
    }

    public function acts(): BelongsToMany
    {
        return $this->belongsToMany(Act::class, 'production_act')
            ->withPivot(['order', 'set_length', 'notes'])
            ->orderBy('order')
            ->withTimestamps();
    }
} 