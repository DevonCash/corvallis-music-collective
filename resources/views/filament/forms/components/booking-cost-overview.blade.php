@php
    // Get form state from the parent form using the Livewire get() helper
    $roomId = $get('room_id');
    $date = $get('date');
    $startTime = $get('start_time');
    $duration = $get('duration');
    
    if (!$roomId || !$duration || !$date || !$startTime) {
        $shouldRender = false;
    } else {
        $shouldRender = true;
        
        try {
            // Create a booking instance for calculations
            $booking = new \App\Modules\PracticeSpace\Models\Booking([
                'room_id' => $roomId,
                'start_time' => \Carbon\Carbon::parse($date . ' ' . $startTime),
                'end_time' => \Carbon\Carbon::parse($date . ' ' . $startTime)->addHours(intval($duration)),
            ]);
            
            // Calculate values
            $hourlyRate = $booking->getPrice();
            $discount = match($booking->user->membership->product_id ?? null) {
                'prod_REM87jLzJ9XUOE' => 0.25,
                'prod_REM8RImHF7j7GY' => 0.5,
                default => 0,
            };
            $totalAmount = $booking->calculateAmount() * (1 - $discount);
        } catch (\Exception $e) {
            $shouldRender = false;
        }
    }
@endphp

    @if (!$shouldRender)
        <div class="text-gray-500">Please complete booking details first</div>
    @else
        <div>
            <p class="text-sm text-gray-500 mb-4">Payment is due when checking in to the practice space.</p>
            <table class="w-full">
                <tbody>
                    <tr class="border-b">
                        <td class="py-2">Hourly Rate</td>
                        <td class="py-2 text-right">${{ $hourlyRate }}</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2">Duration</td>
                        <td class="py-2 text-right">{{ $booking->start_time->longAbsoluteDiffForHumans($booking->end_time) }}</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2">Membership Discount</td>
                        @if ($discount > 0)
                        <td class="py-2 text-right">{{ $discount * 100 }}%</td>
                        @else
                        <td class="py-2 text-right"><a href="#" class="text-blue-500 hover:underline">Upgrade your membership</a></td>
                        @endif
                    </tr>
                    <tr class="font-bold">
                        <td class="py-2">Total Amount</td>
                        <td class="py-2 text-right">${{ $totalAmount }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif