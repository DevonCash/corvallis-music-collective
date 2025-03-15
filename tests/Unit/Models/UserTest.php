<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use CorvMC\BandProfiles\Models\Band;
use CorvMC\Sponsorship\Models\Sponsor;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Panel $memberPanel;
    private Panel $adminPanel;
    private Panel $bandPanel;
    private Panel $sponsorPanel;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com'
        ]);

        // Set up test panels
        $this->memberPanel = Panel::make()->id('member');
        $this->adminPanel = Panel::make()->id('admin');
        $this->bandPanel = Panel::make()->id('band');
        $this->sponsorPanel = Panel::make()->id('sponsor');

        // Create admin role
        Role::create(['name' => 'admin']);

        // Create sponsor tiers
        DB::table('sponsor_tiers')->insert([
            [
                'id' => 1,
                'name' => 'Bronze',
                'amount' => 500.00,
                'benefits' => 'Basic sponsorship benefits',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Silver',
                'amount' => 1000.00,
                'benefits' => 'Enhanced sponsorship benefits',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Gold',
                'amount' => 2500.00,
                'benefits' => 'Premium sponsorship benefits',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Verify sponsor tiers were created
        $this->assertDatabaseCount('sponsor_tiers', 3);
        $this->assertDatabaseHas('sponsor_tiers', ['id' => 1, 'name' => 'Bronze']);
        $this->assertDatabaseHas('sponsor_tiers', ['id' => 2, 'name' => 'Silver']);
        $this->assertDatabaseHas('sponsor_tiers', ['id' => 3, 'name' => 'Gold']);
    }

    /** @test */
    public function it_can_access_member_panel()
    {
        $this->assertTrue($this->user->canAccessPanel($this->memberPanel));
    }

    /** @test */
    public function it_cannot_access_admin_panel_without_role()
    {
        $this->assertFalse($this->user->canAccessPanel($this->adminPanel));
    }

    /** @test */
    public function it_can_access_admin_panel_with_role()
    {
        $this->user->assignRole('admin');
        $this->assertTrue($this->user->canAccessPanel($this->adminPanel));
    }

    /** @test */
    public function it_cannot_access_band_panel_without_bands()
    {
        $this->assertFalse($this->user->canAccessPanel($this->bandPanel));
    }

    /** @test */
    public function it_can_access_band_panel_with_band()
    {
        $band = Band::factory()->create();
        $this->user->bands()->attach($band, ['role' => 'member']);
        
        $this->assertTrue($this->user->canAccessPanel($this->bandPanel));
    }

    /** @test */
    public function it_cannot_access_sponsor_panel_without_sponsors()
    {
        $this->assertFalse($this->user->canAccessPanel($this->sponsorPanel));
    }

    /** @test */
    public function it_can_access_sponsor_panel_with_sponsor()
    {
        $sponsor = Sponsor::factory()->create();
        $this->user->sponsors()->attach($sponsor, ['role' => 'representative']);
        
        $this->assertTrue($this->user->canAccessPanel($this->sponsorPanel));
    }

    /** @test */
    public function it_can_get_band_tenants()
    {
        $band1 = Band::factory()->create();
        $band2 = Band::factory()->create();
        
        $this->user->bands()->attach([
            $band1->id => ['role' => 'member'],
            $band2->id => ['role' => 'admin'],
        ]);

        $tenants = $this->user->getTenants($this->bandPanel);
        
        $this->assertCount(2, $tenants);
        $this->assertTrue($tenants->contains($band1));
        $this->assertTrue($tenants->contains($band2));
    }

    /** @test */
    public function it_can_get_sponsor_tenants()
    {
        $sponsor1 = Sponsor::factory()->create();
        $sponsor2 = Sponsor::factory()->create();
        
        $this->user->sponsors()->attach([
            $sponsor1->id => ['role' => 'representative'],
            $sponsor2->id => ['role' => 'admin'],
        ]);

        $tenants = $this->user->getTenants($this->sponsorPanel);
        
        $this->assertCount(2, $tenants);
        $this->assertTrue($tenants->contains($sponsor1));
        $this->assertTrue($tenants->contains($sponsor2));
    }

    /** @test */
    public function it_can_access_specific_band_tenant()
    {
        $band1 = Band::factory()->create();
        $band2 = Band::factory()->create();
        
        $this->user->bands()->attach($band1, ['role' => 'member']);
        
        $this->assertTrue($this->user->canAccessTenant($band1));
        $this->assertFalse($this->user->canAccessTenant($band2));
    }

    /** @test */
    public function it_can_access_specific_sponsor_tenant()
    {
        $sponsor1 = Sponsor::factory()->create();
        $sponsor2 = Sponsor::factory()->create();
        
        $this->user->sponsors()->attach($sponsor1, ['role' => 'representative']);
        
        $this->assertTrue($this->user->canAccessTenant($sponsor1));
        $this->assertFalse($this->user->canAccessTenant($sponsor2));
    }

    /** @test */
    public function it_returns_empty_collection_for_unknown_panel()
    {
        $unknownPanel = Panel::make()->id('unknown');
        $tenants = $this->user->getTenants($unknownPanel);
        
        $this->assertCount(0, $tenants);
    }

    /** @test */
    public function it_cannot_access_unknown_tenant_type()
    {
        $unknownTenant = new \stdClass();
        
        $this->assertFalse($this->user->canAccessTenant($unknownTenant));
    }
} 