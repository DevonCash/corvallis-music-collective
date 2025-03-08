<?php

namespace CorvMC\PracticeSpace\Traits;

use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

trait LogsNotifications
{
    /**
     * Log that a notification was sent.
     *
     * @param string $notificationType The class name of the notification
     * @param array $metadata Additional metadata to log
     * @return Activity
     */
    public function logNotificationSent(string $notificationType, array $metadata = []): Activity
    {
        $notificationName = class_basename($notificationType);
        
        return activity('notification')
            ->performedOn($this)
            ->withProperties([
                'notification' => $notificationName,
                'metadata' => $metadata,
                'sent_at' => now(),
            ])
            ->log("Sent {$notificationName}");
    }
    
    /**
     * Check if a notification has been sent.
     *
     * @param string $notificationType The class name of the notification
     * @param array $conditions Additional conditions to check in metadata
     * @return bool
     */
    public function hasNotificationBeenSent(string $notificationType, array $conditions = []): bool
    {
        $notificationName = class_basename($notificationType);
        
        $query = Activity::where('log_name', 'notification')
            ->where('subject_type', get_class($this))
            ->where('subject_id', $this->id)
            ->whereJsonContains('properties->notification', $notificationName);
        
        // Add additional conditions if provided
        foreach ($conditions as $key => $value) {
            $query->whereJsonContains("properties->metadata->{$key}", $value);
        }
        
        return $query->exists();
    }
    
    /**
     * Get all notifications sent for this model.
     *
     * @param string|null $notificationType Filter by notification type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNotificationsSent(string $notificationType = null)
    {
        $query = Activity::where('log_name', 'notification')
            ->where('subject_type', get_class($this))
            ->where('subject_id', $this->id);
        
        if ($notificationType) {
            $notificationName = class_basename($notificationType);
            $query->whereJsonContains('properties->notification', $notificationName);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }
} 