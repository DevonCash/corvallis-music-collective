@props(['booking'])

<div class="space-y-6">
    <div class="flex items-start justify-between">
        <div class="space-y-4">
            {{-- Customer Information --}}
            <div class="flex items-start space-x-3">
                <x-heroicon-m-user class="w-6 h-6 text-gray-400 mt-0.5" />
                <div>
                    <a href="{{ \Filament\Pages\Dashboard::getUrl() }}/users/{{ $booking->user->id }}" class="hover:underline">
                        <div class="font-semibold text-gray-900">{{ $booking->user->name }}</div>
                    </a>
                    <a href="mailto:{{ $booking->user->email }}" class="text-sm text-gray-400 hover:text-gray-500">
                        {{ $booking->user->email }}
                    </a>
                </div>
            </div>

            {{-- Booking Details --}}
            <div class="space-y-3">
                <div class="flex items-center space-x-3">
                    <x-heroicon-m-building-office class="w-6 h-6 text-gray-400" />
                    <div>
                        <a href="{{ \Filament\Pages\Dashboard::getUrl() }}/rooms/{{ $booking->room->id }}" class="hover:underline">
                            <span class="font-medium text-gray-900">{{ $booking->room->name }}</span>
                        </a>
                        <span class="text-sm text-gray-400 ml-1">${{ number_format($booking->getPrice(), 2) }}/hour</span>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <x-heroicon-m-calendar class="w-6 h-6 text-gray-400" />
                    <div>
                        <span class="font-medium text-gray-900">{{ $booking->start_time->format('F j, Y') }}</span>
                        <span class="text-gray-500">{{ $booking->start_time->format('g:i A') }} - {{ $booking->end_time->format('g:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-right space-y-6">
            <div>
                <div @class([
                    'badge badge-lg',
                    'badge-info' => $booking->state instanceof \App\Modules\PracticeSpace\Models\States\BookingState\Scheduled,
                    'badge-primary' => $booking->state instanceof \App\Modules\PracticeSpace\Models\States\BookingState\Confirmed,
                    'badge-success' => $booking->state instanceof \App\Modules\PracticeSpace\Models\States\BookingState\CheckedIn || $booking->state instanceof \App\Modules\PracticeSpace\Models\States\BookingState\Completed,
                    'badge-error' => $booking->state instanceof \App\Modules\PracticeSpace\Models\States\BookingState\Cancelled,
                    'badge-warning' => $booking->state instanceof \App\Modules\PracticeSpace\Models\States\BookingState\NoShow,
                ])>
                    {{ class_basename($booking->state::class) }}
                </div>
            </div>

            @if($booking->getAmountOwed() > 0)
                <div class="flex flex-col items-end">
                    <div class="text-sm text-gray-400">Payment Required</div>
                    <div class="text-xl font-semibold text-red-600">${{ number_format($booking->getAmountOwed(), 2) }}</div>
                </div>
            @else
                <div class="text-sm text-green-600 font-medium">Fully Paid</div>
            @endif
        </div>
    </div>
</div>