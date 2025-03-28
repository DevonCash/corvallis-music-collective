
@props(['tier'])

<x-filament::section class='flex-1'>
    <x-slot name="heading">
        <h3 class="text-lg font-medium text-gray-900">{{ $tier['name'] }}</h3>
    </x-slot>
    <x-slot name="headerEnd">
        {{ $slot }}
    </x-slot>

    @if($tier['description'])
        <p class="text-gray-600 mb-4">{{ $tier['description'] }}</p>
    @endif
    
    <div class="text-center flex flex-col items-center gap-2 mb-4">
        @if($tier['current_price']['unit_amount'] === 0)
            <span class="text-xl font-bold">FREE</span>
        @else
            <div>
                @php $formattedPrice = number_format($tier['current_price']['unit_amount'] / 100, 2); @endphp
                <span class="text-xl font-bold">${{ $formattedPrice }}</span>
                <span class="text-sm font-normal">/ {{ $tier['current_price']['recurring']['interval'] }}</span>
            </div>
        @endif
    </div>

        <div class="flex-1">
            @if(!empty($tier['features']))
                <ul class="space-y-2">
                    @foreach($tier['features'] as $feature)
                        <li class="flex items-start">
                            <x-filament::icon
                                name="heroicon-o-check-"
                                class="h-5 w-5 text-success-500 mr-2 flex-shrink-0"
                            />
                            <span>{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
    </div>
</x-filament::section>