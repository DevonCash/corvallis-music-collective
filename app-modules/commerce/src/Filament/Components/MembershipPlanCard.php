<?php

namespace CorvMC\Commerce\Filament\Components;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Concerns\HasHeaderActions;
use Illuminate\Support\Facades\Log;
use Closure;

class MembershipPlanCard extends Component
{
    use HasHeaderActions;
    
    public array $tier = [];
    public bool $isCurrentPlan = false;
    public bool $isFreePlan = false;
    public bool $isPopular = false;
    protected ?Closure $subscribeActionCallback = null;
    
    /**
     * @var array<FormAction> | null
     */
    protected ?array $cachedHeaderActions = null;

    protected string $view = 'commerce::components.membership-plan-card';
    
    /**
     * Create a new instance of the component.
     *
     * @return static
     */
    public static function make(): static
    {
        $static = app(static::class);
        $static->configure();

        return $static;
    }

    public function getTier(): array
    {
        return $this->tier;
    }
    
    /**
     * Set the tier data for the card.
     *
     * @param array $tier The tier data
     * @return $this
     */
    public function membershipTier(array $tier): static
    {
        $this->tier = $tier;
        
        return $this;
    }
    
    /**
     * Mark this card as the current plan.
     *
     * @param bool $isCurrentPlan
     * @return $this
     */
    public function isCurrentPlan(bool $isCurrentPlan = true): static
    {
        $this->isCurrentPlan = $isCurrentPlan;
        
        return $this;
    }
    
    /**
     * Mark this card as a free plan.
     *
     * @param bool $isFreePlan
     * @return $this
     */
    public function isFreePlan(bool $isFreePlan = true): static
    {
        $this->isFreePlan = $isFreePlan;
        
        return $this;
    }
    
    /**
     * Mark this card as a popular plan.
     *
     * @param bool $isPopular
     * @return $this
     */
    public function isPopular(bool $isPopular = true): static
    {
        $this->isPopular = $isPopular;
        
        return $this;
    }
} 