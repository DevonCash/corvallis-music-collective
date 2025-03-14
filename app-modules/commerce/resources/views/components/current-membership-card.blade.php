@php
    $tierName = $tier['name'] ?? 'Unknown';
    $price = $tier['current_price']['unit_amount'] ?? 0;
    $interval = $tier['current_price']['recurring']['interval'] ?? 'month';
    $formattedPrice = '$' . number_format($price / 100, 2);
    $renewalDate = date('F j, Y', $renewalDate);
    $component = $component ?? null;
@endphp


<div {!! $component ? $component->getExtraAttributeBag()->toHtml() : '' !!} class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900">{{ $heading }}</h3>
        
        @if($hasHeaderActions || ($component && !empty($component->getHeaderActions())))
        <div class="flex items-center space-x-2">
            @if(isset($headerActions))
                {{ $headerActions }}
            @elseif($component)
                @foreach($component->getHeaderActions() as $action)
                    {{ $action }}
                @endforeach
            @endif
        </div>
        @endif
    </div>

    <div class="p-4">
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
                        <span class="text-lg font-medium text-gray-900">{{ $tierName }}</span>
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
    </div>
</div> 