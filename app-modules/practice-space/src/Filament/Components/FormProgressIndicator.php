<?php

namespace CorvMC\PracticeSpace\Filament\Components;

use Filament\Forms\Components\Component;
use Filament\Forms\Get;
use Illuminate\Support\Arr;

class FormProgressIndicator extends Component
{
    protected string $view = 'practice-space::components.form-progress-indicator';
    
    protected array $requiredFields = [];
    protected string $progressLabel = 'Form Completion';
    protected bool $showPercentage = true;
    protected bool $showLabel = true;
    
    public static function make(): static
    {
        return app(static::class);
    }
    
    public function requiredFields(array $fields): static
    {
        $this->requiredFields = $fields;
        
        return $this;
    }
    
    public function progressLabel(string $label): static
    {
        $this->progressLabel = $label;
        
        return $this;
    }
    
    public function showPercentage(bool $condition = true): static
    {
        $this->showPercentage = $condition;
        
        return $this;
    }
    
    public function showLabel(bool $condition = true): static
    {
        $this->showLabel = $condition;
        
        return $this;
    }
    
    /**
     * Calculate the progress based on filled required fields
     */
    public function getProgress(): int
    {
        if (empty($this->requiredFields)) {
            return 100;
        }
        
        $livewire = $this->getLivewire();
        
        if (!$livewire) {
            return 0;
        }
        
        $completedFields = 0;
        $totalFields = count($this->requiredFields);
        
        foreach ($this->requiredFields as $field) {
            // Try to get the value from the Livewire component
            $value = data_get($livewire, $field);
            
            if (is_array($value)) {
                if (!empty($value)) {
                    $completedFields++;
                }
            } elseif (!is_null($value) && $value !== '') {
                $completedFields++;
            }
        }
        
        return (int) round(($completedFields / $totalFields) * 100);
    }
    
    public function getProgressLabel(): string
    {
        return $this->progressLabel;
    }
    
    public function shouldShowPercentage(): bool
    {
        return $this->showPercentage;
    }
    
    public function shouldShowLabel(): bool
    {
        return $this->showLabel;
    }
    
    /**
     * Get the required fields
     */
    public function getRequiredFields(): array
    {
        return $this->requiredFields;
    }
} 