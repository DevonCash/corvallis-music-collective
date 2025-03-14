@php
    $name = $tier['name'] ?? 'Unknown';
    $description = $tier['description'] ?? '';
    $features = $tier['features'] ?? [];
    $price = $tier['current_price']['unit_amount'] ?? 0;
    $interval = $tier['current_price']['recurring']['interval'] ?? 'month';
    $formattedPrice = '$' . number_format($price / 100, 2);
    $intervalDisplay = $interval === 'month' ? 'month' : 'year';
@endphp

<div class="p-4 flex flex-col">
    @if($description)
    <p class="text-gray-600 mb-4">{{ $description }}</p>
    @endif
    
    <div class="text-center flex flex-col items-center gap-2 mb-4">
        @if($price === 0)
            <span class="text-xl font-bold">FREE</span>
            @if($isCurrentPlan)
                <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                    <x-filament::icon
                        name="heroicon-o-check-circle"
                        class="w-3 h-3 mr-1"
                    />
                    Current Plan
                </div>
            @endif
        @else
            <div>
                <span class="text-xl font-bold">{{ $formattedPrice }}</span>
                <span class="text-sm font-normal">/ {{ $intervalDisplay }}</span>
            </div>
            
            @if($isCurrentPlan)
                <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                    <x-filament::icon
                        name="heroicon-o-check-circle"
                        class="w-3 h-3 mr-1"
                    />
                    Current Plan
                </div>
            @endif
            
            @if($interval === 'year')
                @php
                    // Calculate yearly savings if we have monthly price data
                    $monthlySavings = null;
                    $monthlyPrice = $tier['prices']['month']['unit_amount'] ?? null;
                    
                    if ($monthlyPrice) {
                        $yearlyEquivalent = $monthlyPrice * 12;
                        $savings = $yearlyEquivalent - $price;
                        $savingsPercent = round(($savings / $yearlyEquivalent) * 100);
                        
                        if ($savings > 0) {
                            $monthlySavings = $savingsPercent;
                        }
                    }
                @endphp
                
                @if($monthlySavings)
                    <div class="badge badge-success">
                        <x-filament::icon
                            name="heroicon-o-arrow-down"
                            class="w-3 h-3 mr-1"
                        />
                        Save {{ $monthlySavings }}%
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Compared to monthly billing</p>
                @endif
            @endif
        @endif
    </div>

    <div class="flex-1">
        @if(!empty($features))
            <ul class="space-y-2">
                @foreach($features as $feature)
                    <li class="flex items-start">
                        <x-filament::icon
                            name="heroicon-o-check-circle"
                            class="h-5 w-5 text-success-500 mr-2 flex-shrink-0"
                        />
                        <span>{{ $feature }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div> 