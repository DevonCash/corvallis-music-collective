<?php

namespace CorvMC\StateManagement\Tests\Unit;

use CorvMC\StateManagement\AbstractState;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class AbstractStateTest extends TestCase
{
    /** @test */
    public function it_can_get_states()
    {
        // Create a concrete implementation of AbstractState for testing
        $states = ConcreteState::getStates();
        
        $this->assertIsArray($states);
        $this->assertArrayHasKey('concrete', $states);
        $this->assertEquals(ConcreteState::class, $states['concrete']);
    }
    
    /** @test */
    public function it_can_check_transition_permissions()
    {
        $this->assertTrue(ConcreteState::canTransitionTo(AnotherConcreteState::class));
        $this->assertFalse(ConcreteState::canTransitionTo(InvalidState::class));
    }
    
    /** @test */
    public function it_can_transition_to_allowed_state()
    {
        $model = new TestModel();
        $model->state = 'concrete';
        
        $result = ConcreteState::transitionTo($model, AnotherConcreteState::class);
        
        $this->assertEquals('another', $result->state);
    }
    
    /** @test */
    public function it_throws_exception_when_transitioning_to_invalid_state()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $model = new TestModel();
        $model->state = 'concrete';
        
        ConcreteState::transitionTo($model, InvalidState::class);
    }
    
    /** @test */
    public function it_can_get_actions()
    {
        $model = new TestModel();
        $actions = ConcreteState::getActions($model);
        
        $this->assertIsArray($actions);
        $this->assertCount(1, $actions);
    }
}

// Test classes for the tests

class TestModel extends Model
{
    protected $fillable = ['state'];
    
    // Disable timestamps for testing
    public $timestamps = false;
    
    // Mock the save method
    public function save(array $options = [])
    {
        return $this;
    }
}

class ConcreteState extends AbstractState
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
        return [
            AnotherConcreteState::class,
        ];
    }
}

class AnotherConcreteState extends AbstractState
{
    protected static array $states = [
        self::class,
    ];
    
    public static function getName(): string
    {
        return 'another';
    }
    
    public static function getLabel(): string
    {
        return 'Another State';
    }
    
    public static function getColor(): string
    {
        return 'success';
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

class InvalidState extends AbstractState
{
    protected static array $states = [
        self::class,
    ];
    
    public static function getName(): string
    {
        return 'invalid';
    }
    
    public static function getLabel(): string
    {
        return 'Invalid State';
    }
    
    public static function getColor(): string
    {
        return 'danger';
    }
    
    public static function getIcon(): string
    {
        return 'heroicon-o-x-mark';
    }
    
    public static function getAllowedTransitions(): array
    {
        return [];
    }
} 