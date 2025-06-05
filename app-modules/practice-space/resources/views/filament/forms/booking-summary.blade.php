@props(['booking'])
<div class="overflow-hidden rounded-lg w-full">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Booking Details Section -->
            <tr class="bg-gray-50 dark:bg-gray-700">
                <th colspan="2" class="px-4 py-3 text-left text-sm font-medium text-gray-900 dark:text-white">
                    Booking Details
                </th>
            </tr>
            <tr class="bg-white dark:bg-gray-800">
                <td class="px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                    Room
                </td>
                <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100 text-right">
                    {{ $booking->room->name }}
                </td>
            </tr>
            <tr class="bg-white dark:bg-gray-800">
                <td class="px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                    Date
                </td>
                <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100 text-right">
                    {{ $booking->start_time->format('M d, Y') }}
                </td>
            </tr>
            <tr class="bg-white dark:bg-gray-800">
                <td class="px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                    Time
                </td>
                <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100 text-right">
                    {{ $booking->start_time->format('g:i A') }} - {{ $booking->end_time->format('g:i A') }} ({{ $booking->duration }} {{ (int)$booking->duration === 1 ? 'hour' : 'hours' }})
                </td>
            </tr>
            
            <tr class="bg-gray-50 dark:bg-gray-700 font-medium border-t border-gray-400 dark:border-gray-600">
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                    Total
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">
                    ${{ number_format($booking->room->hourly_rate * $booking->duration, 2) }}
                </td>
            </tr>
            <tr class="bg-white dark:bg-gray-800">
                <td colspan="2" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 text-center italic">
                    Payment is due at start of reservation
                </td>
            </tr>
        </tbody>
    </table>
</div>