<?php

namespace CorvMC\StateManagement\Tests\Unit\Contracts;

use CorvMC\StateManagement\AbstractState;
use CorvMC\StateManagement\Contracts\StateInterface;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class StateInterfaceTest extends TestCase
{
    /** @test */
    public function abstract_state_implements_state_interface()
    {
        $this->assertTrue(is_subclass_of(AbstractState::class, StateInterface::class));
    }
    
    /** @test */
    public function concrete_state_implements_required_methods()
    {
        $requiredMethods = [
            'getName',
            'getLabel',
            'getColor',
            'getIcon',
            'getAllowedTransitions',
            'canTransitionTo',
            'transitionTo',
            'getActions',
            'getForm',
            'getInfolistSection',
        ];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(method_exists(ConcreteStateImplementation::class, $method));
        }
    }
}

class ConcreteStateImplementation extends AbstractState
{
    protected static array $states = [
        self::class,
    ];
    
    public static function getName(): string
    {
        return 'concrete';
    }
    
    public static function getLabel(): string
    {
        return 'Concrete State';
    }
    
    public static function getColor(): string
    {
        return 'primary';
    }
    
    public static function getIcon(): string
    {
        return 'heroicon-o-check';
    }
    
    public static function getAllowedTransitions(): array
    {
        return [];
    }
} 