<?php

namespace App\Providers;

use App\Models\User;

use App\Policies\UserPolicy;
use App\Policies\BookingPolicy;
use App\Policies\RoomPolicy;
use App\Policies\RoomCategoryPolicy;
use App\Policies\EquipmentRequestPolicy;
use App\Policies\WaitlistEntryPolicy;

use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\RoomCategory;
use CorvMC\PracticeSpace\Models\EquipmentRequest;
use CorvMC\PracticeSpace\Models\WaitlistEntry;
use Illuminate\Support\Facades\Gate;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
      User::class => UserPolicy::class,
      Booking::class => BookingPolicy::class,
      Room::class => RoomPolicy::class,
      RoomCategory::class => RoomCategoryPolicy::class,
      EquipmentRequest::class => EquipmentRequestPolicy::class,
      WaitlistEntry::class => WaitlistEntryPolicy::class,
  ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
      foreach ($this->policies as $model => $policy) {
        Gate::policy($model, $policy);
      }
    }


    public function register()
    {
     
    }
} 