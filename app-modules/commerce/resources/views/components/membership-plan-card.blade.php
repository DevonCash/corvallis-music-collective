@php
    $name = $this->tier['name'] ?? 'Unknown';
    $description = $this->tier['description'] ?? '';
    $features = $this->tier['features'] ?? [];
    $price = $this->tier['current_price']['unit_amount'] ?? 0;
    $interval = $this->tier['current_price']['recurring']['interval'] ?? 'month';
    $formattedPrice = '$' . number_format($price / 100, 2);
    $intervalDisplay = $interval === 'month' ? 'month' : 'year';
    $component = $component ?? null;
    
    // Define card styling based on plan type
    $cardClasses = 'h-full flex flex-col border-4 rounded-xl shadow-sm';
    
    if ($isCurrentPlan ?? false) {
        $cardClasses .= ' border-primary-500 bg-primary-50';
    } elseif ($isFreePlan ?? false) {
        $cardClasses .= ' border-gray-300 bg-gray-50';
    } elseif ($isPopular ?? false) {
        $cardClasses .= ' border-warning-500 bg-white shadow-md';
    } else {
        $cardClasses .= ' border-gray-200';
    }
@endphp

@dump($this->getTier())

<div {!! $component ? $component->getExtraAttributeBag()->toHtml() : '' !!} class="{{ $cardClasses }}" style="display: flex; flex-direction: column; position: relative;">
    @if(($isPopular ?? false) && !($isCurrentPlan ?? false))
        <div class="absolute top-0 right-0 transform translate-x-1/4 -translate-y-1/4">
            <div class="bg-warning-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-sm">
                Popular
            </div>
        </div>
    @endif
    
    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900">{{ $heading ?? $name }}</h3>
        
        @if(is_object($component) && method_exists($component, 'getHeaderActions'))
            <div class="flex items-center gap-3">
                @foreach($component->getHeaderActions() as $action)
                    {{ $action }}
                @endforeach
            </div>
        @elseif(isset($headerActions) && is_array($headerActions) && !empty($headerActions))
            <div class="flex items-center gap-3">
                @foreach($headerActions as $headerAction)
                    {{ $headerAction }}
                @endforeach
            </div>
        @endif
    </div>

    <div class="p-4 flex-1 flex flex-col">
        @if($description)
        <p class="text-gray-600 mb-4">{{ $description }}</p>
        @endif
        
        <div class="text-center flex flex-col items-center gap-2 mb-4">
            @if($price === 0)
                <span class="text-xl font-bold">FREE</span>
                @if($isCurrentPlan ?? false)
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
                
                @if($isCurrentPlan ?? false)
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
</div> 