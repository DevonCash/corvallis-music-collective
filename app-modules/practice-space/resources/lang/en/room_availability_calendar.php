<?php

return [
    // Navigation and date-related labels
    'previous_week' => 'Previous Week',
    'next_week' => 'Next Week',
    'today' => 'Today',
    'select_room' => 'Select Room',
    'time' => 'Time',
    
    // Time format strings
    'time_format' => 'g:ia', // Format for displaying times (e.g., 8:00am)
    'time_range_format' => ':start_time - :end_time', // Format for time range display
    'policy_time_format' => 'g:i a', // Format for policy hours display (e.g., 8:00 am)
    
    // Calendar labels
    'calendar_title' => ':room Calendar',
    'calendar_description' => 'This calendar shows when rooms are booked. Your bookings are highlighted in blue and show your name, while other bookings are marked as "Booked".',
    'no_room_selected' => 'Please select a room to view the calendar.',
    'no_bookings_available' => 'No bookings available',
    
    // Booking-related strings
    'create_booking' => 'Book a Room',
    'booking_details' => 'Booking Details',
    'current_user_booking' => 'Your booking',
    'book_this_slot' => 'Book this slot',
    
    // Status messages
    'past_time_slot' => 'Past time slot',
    'unavailable_time_slot' => 'Unavailable',
    'available_time_slot' => 'Available',
    
    // Invalid reason tooltips
    'invalid_reason_past' => 'This time is in the past',
    'invalid_reason_advance_notice' => 'Too close to current time - advance notice required',
    'invalid_reason_closing_time' => 'Too close to closing time',
    'invalid_reason_adjacent_booking' => 'Too close to an existing booking',
    
    // Natural language booking policy descriptions
    'policy_open_hours' => 'Open from :opening_time to :closing_time for bookings up to :max_duration hours.',
    'policy_booking_window' => 'Bookings must be made at least :min_hours hours in advance and no more than :max_days days ahead.',
    'policy_cancellation' => 'You can cancel with a refund up to :hours hours before your booking.',
    'policy_weekly_limit' => 'Maximum :limit bookings per week per person.',
    
    // Booking policy summary (old, structured format)
    'hours' => 'Hours',
    'duration' => 'Duration',
    'advance_booking' => 'Advance Booking',
    'cancellation' => 'Cancellation',
    'weekly_limit' => 'Weekly Limit',
    'hours_short' => 'hrs',
    'days_short' => 'days',
    'bookings' => 'bookings',
]; 