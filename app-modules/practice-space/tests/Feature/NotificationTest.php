<?php

namespace CorvMC\PracticeSpace\Tests\Feature;

use App\Models\User;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Notifications\BookingConfirmationNotification;
use CorvMC\PracticeSpace\Notifications\BookingConfirmationRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_confirmation_notification_can_be_sent()
    {
        // Arrange
        Notification::fake();
        
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);

        // Act
        $user->notify(new BookingConfirmationNotification($booking));

        // Assert
        Notification::assertSentTo(
            $user,
            BookingConfirmationNotification::class
        );
    }

    public function test_booking_confirmation_request_notification_can_be_sent()
    {
        // Arrange
        Notification::fake();
        
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);

        // Act
        $user->notify(new BookingConfirmationRequestNotification($booking, 24));

        // Assert
        Notification::assertSentTo(
            $user,
            BookingConfirmationRequestNotification::class
        );
    }

    /**
     * Test that notification content can be rendered.
     */
    public function test_notification_content_can_be_rendered()
    {
        // This test demonstrates how to test the actual content of the notification
        // without sending real emails
        
        // Arrange
        $user = User::factory()->create(['name' => 'Test User']);
        $room = Room::factory()->create(['name' => 'Test Room']);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => now()->addDays(3)->setHour(14)->setMinute(0),
            'end_time' => now()->addDays(3)->setHour(16)->setMinute(0),
        ]);
        
        // Act
        $notification = new BookingConfirmationNotification($booking);
        $mailMessage = $notification->toMail($user);
        
        // Assert
        $this->assertStringContainsString('Test Room', $mailMessage->subject);
        
        // Test that the view data contains the expected values
        $viewData = $mailMessage->viewData;
        $this->assertEquals('Test User', $viewData['userName']);
        $this->assertEquals('Test Room', $viewData['roomName']);
        $this->assertEquals($booking->id, $viewData['bookingId']);
        $this->assertStringContainsString('/practice-space/bookings/' . $booking->id, $viewData['viewUrl']);
    }

    /**
     * Example of how to manually test notifications using Tinker
     * 
     * This is not an actual test but a documentation of how to test manually
     */
    public function example_of_manual_testing_with_tinker()
    {
        // In Tinker, you would run:
        // 
        // $user = \App\Models\User::first();
        // $booking = \CorvMC\PracticeSpace\Models\Booking::first();
        // $user->notify(new \CorvMC\PracticeSpace\Notifications\BookingConfirmationNotification($booking));
        // 
        // With mail driver set to 'log', this will write the email to storage/logs/laravel.log
        
        $this->assertTrue(true); // Dummy assertion to make PHPUnit happy
    }

    /**
     * Test that notification can be rendered for Filament.
     */
    public function test_notification_can_be_rendered_for_filament()
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Test User']);
        $room = Room::factory()->create(['name' => 'Test Room']);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => now()->addDays(3)->setHour(14)->setMinute(0),
            'end_time' => now()->addDays(3)->setHour(16)->setMinute(0),
        ]);
        
        // Act
        $notification = new BookingConfirmationNotification($booking);
        $filamentData = $notification->toFilament($user);
        
        // Assert
        $this->assertEquals("Booking Confirmed: Test Room", $filamentData['title']);
        $this->assertEquals('heroicon-o-check-circle', $filamentData['icon']);
        $this->assertEquals('success', $filamentData['iconColor']);
        $this->assertStringContainsString('Test Room', $filamentData['body']);
        $this->assertCount(1, $filamentData['actions']);
        $this->assertEquals('View Booking', $filamentData['actions'][0]['label']);
    }

    /**
     * Test that notification can be rendered for database.
     */
    public function test_notification_can_be_rendered_for_database()
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Test User']);
        $room = Room::factory()->create(['name' => 'Test Room']);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => now()->addDays(3)->setHour(14)->setMinute(0),
            'end_time' => now()->addDays(3)->setHour(16)->setMinute(0),
        ]);
        
        // Act
        $notification = new BookingConfirmationNotification($booking);
        $databaseData = $notification->toDatabase($user);
        
        // Assert
        $this->assertEquals("Booking Confirmed: Test Room", $databaseData['title']);
        $this->assertEquals('heroicon-o-check-circle', $databaseData['icon']);
        $this->assertEquals('success', $databaseData['iconColor']);
        $this->assertStringContainsString('Test Room', $databaseData['body']);
        $this->assertCount(1, $databaseData['actions']);
        $this->assertEquals('View Booking', $databaseData['actions'][0]['label']);
    }
} 