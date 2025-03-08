<?php

namespace CorvMC\PracticeSpace\Tests\Traits;

use Filament\Facades\Filament;
use Illuminate\Testing\TestResponse;
use Livewire\Features\SupportTesting\Testable;

trait TestsFilamentResources
{
    /**
     * Get the panel to use for testing.
     */
    protected function getPanel(): string
    {
        return 'member';
    }

    /**
     * Visit a resource index page.
     */
    protected function visitResourceIndex(string $resource): TestResponse
    {
        return $this->get(
            Filament::getPanel($this->getPanel())
                ->getUrl($resource::getSlug())
        );
    }

    /**
     * Visit a resource create page.
     */
    protected function visitResourceCreate(string $resource): TestResponse
    {
        return $this->get(
            Filament::getPanel($this->getPanel())
                ->getUrl($resource::getSlug() . '/create')
        );
    }

    /**
     * Visit a resource edit page.
     */
    protected function visitResourceEdit(string $resource, $record): TestResponse
    {
        return $this->get(
            Filament::getPanel($this->getPanel())
                ->getUrl($resource::getSlug() . '/' . $record->getKey() . '/edit')
        );
    }

    /**
     * Visit a resource view page.
     */
    protected function visitResourceView(string $resource, $record): TestResponse
    {
        return $this->get(
            Filament::getPanel($this->getPanel())
                ->getUrl($resource::getSlug() . '/' . $record->getKey())
        );
    }

    /**
     * Get a Livewire component for a resource index page.
     */
    protected function getResourceIndex(string $resource): Testable
    {
        return $this->livewire($resource::getPages()['index']::class);
    }

    /**
     * Get a Livewire component for a resource create page.
     */
    protected function getResourceCreate(string $resource): Testable
    {
        return $this->livewire($resource::getPages()['create']::class);
    }

    /**
     * Get a Livewire component for a resource edit page.
     */
    protected function getResourceEdit(string $resource, $record): Testable
    {
        return $this->livewire($resource::getPages()['edit']::class, [
            'record' => $record->getKey(),
        ]);
    }

    /**
     * Get a Livewire component for a resource view page.
     */
    protected function getResourceView(string $resource, $record): Testable
    {
        return $this->livewire($resource::getPages()['view']::class, [
            'record' => $record->getKey(),
        ]);
    }
} 