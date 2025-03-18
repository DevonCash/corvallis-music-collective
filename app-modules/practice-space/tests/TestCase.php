<?php

namespace CorvMC\PracticeSpace\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as BaseTestCase;
use Livewire\Livewire;
use Filament\FilamentServiceProvider;
use Filament\Support\SupportServiceProvider;
use Livewire\LivewireServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Filament\Facades\Filament;
use Illuminate\Testing\TestResponse;
use Illuminate\Database\Eloquent\Model;
use Exception;
use App\Providers\Filament\MemberPanelProvider;
use CorvMC\PracticeSpace\Filament\PracticeSpacePluginProvider;
use Filament\Panel;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    
    /**
     * The admin user instance.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected static $adminUser = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Additional setup for practice space tests
        $this->withoutExceptionHandling();
        
        // Register Filament providers for testing
        $this->app->register(FilamentServiceProvider::class);
        $this->app->register(SupportServiceProvider::class);
        $this->app->register(LivewireServiceProvider::class);
        
        // Register the member panel provider
        try {
            $this->app->register(MemberPanelProvider::class);
            
            // Register the Practice Space plugin with the member panel
            if (method_exists(Filament::class, 'hasPanel') && Filament::hasPanel('member')) {
                Filament::getPanel('member')->registerPlugin(new PracticeSpacePluginProvider());
            }
        } catch (Exception $e) {
            // Silently continue if the panel provider can't be registered
            // This allows unit tests to run without Filament
        }
        
        // Set up the admin user for testing
        $adminUser = $this->createAdminUser();
        
        // Set up Livewire for testing
        Livewire::actingAs($adminUser);
        
        // Register a test-specific panel for the practice space module
        $this->registerTestPanel();
    }
    
    /**
     * Register a test-specific Filament panel for the practice space module.
     */
    protected function registerTestPanel(): void
    {
        try {
            // Create a test panel specifically for practice space tests
            Filament::registerPanel(
                Panel::make()
                    ->id('practice-space-test')
                    ->path('practice-space-test')
                    ->plugin(new PracticeSpacePluginProvider())
                    ->discoverResources(in: __DIR__ . '/../src/Filament/Resources', for: 'CorvMC\\PracticeSpace\\Filament\\Resources')
                    ->discoverPages(in: __DIR__ . '/../src/Filament/Pages', for: 'CorvMC\\PracticeSpace\\Filament\\Pages')
                    ->discoverWidgets(in: __DIR__ . '/../src/Filament/Widgets', for: 'CorvMC\\PracticeSpace\\Filament\\Widgets')
                    ->authGuard('web')
                    ->login()
            );
            
            // Set the current panel to our test panel
            Filament::setCurrentPanel(Filament::getPanel('practice-space-test'));
        } catch (Exception $e) {
            // Silently continue if Filament is not properly set up
            // This allows unit tests to run without Filament
        }
    }
    
    /**
     * Create an admin user for testing
     *
     * @return Authenticatable
     */
    protected function createAdminUser(): Authenticatable
    {
        if (static::$adminUser === null) {
            // Only create the admin user once
            static::$adminUser = User::factory()->state([
                'email' => 'admin@corvmc.org',
            ])->create();
        }
        
        return static::$adminUser;
    }
    
    /**
     * Get the panel to use for testing.
     */
    protected function getPanel(): string
    {
        return 'member';
    }

    /**
     * Get the URL for a resource index page.
     * 
     * @param class-string $resource The resource class
     */
    protected function getResourceIndexUrl(string $resource): string
    {
        return Filament::getPanel($this->getPanel())
            ->getUrl(null, $resource::getSlug());
    }

    /**
     * Get the URL for a resource create page.
     * 
     * @param class-string $resource The resource class
     */
    protected function getResourceCreateUrl(string $resource): string
    {
        return Filament::getPanel($this->getPanel())
            ->getUrl(null, $resource::getSlug() . '/create');
    }

    /**
     * Get the URL for a resource edit page.
     * 
     * @param class-string $resource The resource class
     * @param Model $record The model instance
     */
    protected function getResourceEditUrl(string $resource, Model $record): string
    {
        return Filament::getPanel($this->getPanel())
            ->getUrl(null, $resource::getSlug() . '/' . $record->getKey() . '/edit');
    }

    /**
     * Get the URL for a resource view page.
     * 
     * @param class-string $resource The resource class
     * @param Model $record The model instance
     */
    protected function getResourceViewUrl(string $resource, Model $record): string
    {
        return Filament::getPanel($this->getPanel())
            ->getUrl(null, $resource::getSlug() . '/' . $record->getKey());
    }

    /**
     * Visit a resource index page.
     * 
     * @param class-string $resource The resource class
     */
    protected function visitResourceIndex(string $resource): TestResponse
    {
        return $this->get($this->getResourceIndexUrl($resource));
    }

    /**
     * Visit a resource create page.
     * 
     * @param class-string $resource The resource class
     */
    protected function visitResourceCreate(string $resource): TestResponse
    {
        return $this->get($this->getResourceCreateUrl($resource));
    }

    /**
     * Visit a resource edit page.
     * 
     * @param class-string $resource The resource class
     * @param Model $record The model instance
     */
    protected function visitResourceEdit(string $resource, Model $record): TestResponse
    {
        return $this->get($this->getResourceEditUrl($resource, $record));
    }

    /**
     * Visit a resource view page.
     * 
     * @param class-string $resource The resource class
     * @param Model $record The model instance
     */
    protected function visitResourceView(string $resource, Model $record): TestResponse
    {
        return $this->get($this->getResourceViewUrl($resource, $record));
    }
} 