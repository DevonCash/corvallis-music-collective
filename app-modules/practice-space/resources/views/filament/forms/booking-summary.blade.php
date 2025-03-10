@props(['booking'])
<div class="overflow-hidden rounded-lg w-full">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Room Details Section -->
            <tr class="bg-gray-50 dark:bg-gray-700">
                <th colspan="3" class="px-4 py-3 text-left text-sm font-medium text-gray-900 dark:text-white">
                    Room Details
                </th>
            </tr>
            <tr class="bg-white dark:bg-gray-800">
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Room
                </th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Capacity
                </th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Hourly Rate
                </th>
            </tr>
            <tr class="bg-white dark:bg-gray-800">
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                    {{ $booking->room->name }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                    {{ $booking->room->capacity }} people
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 text-right">
                    ${{ number_format($booking->room->hourly_rate, 2) }}
                </td>
            </tr>
            
            <!-- Booking Details Section -->
            <tr class="bg-gray-50 dark:bg-gray-700">
                <th colspan="3" class="px-4 py-3 text-left text-sm font-medium text-gray-900 dark:text-white">
                    Booking Details
                </th>
            </tr>
            <tr class="bg-white dark:bg-gray-800">
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Date
                </th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Time
                </th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Duration
                </th>
            </tr>
            <tr class="bg-white dark:bg-gray-800">
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                    {{ $booking->start_time->format('M d, Y') }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                    {{ $booking->start_time->format('g:i A') }} - 
                    {{ $booking->end_time->format('g:i A') }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 text-right">
                    {{ $booking->duration }} {{ (int)$booking->duration === 1 ? 'hour' : 'hours' }}
                </td>
            </tr>
            
            <tr class="bg-gray-50 dark:bg-gray-700 font-medium border-t border-gray-400 dark:border-gray-600">
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                    Total
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                    ${{ number_format($booking->room->hourly_rate, 2) }} × {{ $booking->duration }} {{ $booking->duration == 1 ? 'hour' : 'hours' }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">
                    ${{ number_format($booking->room->hourly_rate * $booking->duration, 2) }}
                </td>
            </tr>
        </tbody>
    </table>
</div>