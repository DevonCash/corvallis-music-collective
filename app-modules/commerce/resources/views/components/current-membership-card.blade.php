@props(['subscription' => null, 'tier' => null])
@if($subscription)
<x-filament::section>
    @dump($subscription, $tier)    
    {{-- <div class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-500">Current Tier</label>
                <div class="flex items-center mt-1">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center mr-3">
                        <x-filament::icon
                            name="heroicon-o-badge-check"
                            class="h-6 w-6 text-primary-600"
                        />
                    </div>
                    <div>
                        <span class="text-lg font-medium text-gray-900">{{ $tier['name'] }}</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-500">Current Plan</label>
                <div class="flex items-center mt-1">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center mr-3">
                        <x-filament::icon
                            name="heroicon-o-currency-dollar"
                            class="h-6 w-6 text-primary-600"
                        />
                    </div>
                    <div>
                        <span class="text-lg font-medium text-gray-900">{{ $formattedPrice }} / {{ $interval }}</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-500">Renewal Date</label>
                <div class="flex items-center mt-1">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center mr-3">
                        <x-filament::icon
                            name="heroicon-o-calendar"
                            class="h-6 w-6 text-primary-600"
                        />
                    </div>
                    <div>
                        <span class="text-lg font-medium text-gray-900">{{ $renewalDate }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
</x-filament::section>

@endif