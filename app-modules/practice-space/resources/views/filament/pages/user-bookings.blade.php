<x-filament-panels::page>
    <x-filament::section>
        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="col-span-1 md:col-span-2">
                    <h2 class="text-xl font-bold tracking-tight">My Practice Room Bookings</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        View and manage your practice room reservations
                    </p>
                </div>
            </div>
            
            {{ $this->table }}
        </div>
    </x-filament::section>
</x-filament-panels::page> 