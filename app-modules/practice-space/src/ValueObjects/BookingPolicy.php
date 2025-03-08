<?php

namespace CorvMC\PracticeSpace\ValueObjects;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class BookingPolicy implements Arrayable, JsonSerializable, CastsAttributes
{
    /**
     * @param string $openingTime Opening time in 24-hour format (HH:MM)
     * @param string $closingTime Closing time in 24-hour format (HH:MM)
     * @param float $maxBookingDurationHours Maximum booking duration in hours
     * @param float $minBookingDurationHours Minimum booking duration in hours
     * @param int $maxAdvanceBookingDays Maximum days in advance a booking can be made
     * @param float $minAdvanceBookingHours Minimum hours in advance a booking must be made
     * @param int $cancellationHours Hours before start time when cancellation with refund is allowed
     * @param int $maxBookingsPerWeek Maximum number of bookings a user can make per week
     */
    public function __construct(
        public string $openingTime = '08:00',
        public string $closingTime = '22:00',
        public float $maxBookingDurationHours = 8.0,
        public float $minBookingDurationHours = 0.5,
        public int $maxAdvanceBookingDays = 90,
        public float $minAdvanceBookingHours = 1.0,
        public int $cancellationHours = 24,
        public int $maxBookingsPerWeek = 5
    ) {
        $this->validate();
    }

    /**
     * Validate the booking policy values
     *
     * @throws \InvalidArgumentException
     */
    protected function validate(): void
    {
        // Validate time formats
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $this->openingTime)) {
            throw new \InvalidArgumentException("Invalid opening time format: {$this->openingTime}. Use HH:MM format.");
        }

        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $this->closingTime)) {
            throw new \InvalidArgumentException("Invalid closing time format: {$this->closingTime}. Use HH:MM format.");
        }

        // Validate duration values
        if ($this->minBookingDurationHours <= 0) {
            throw new \InvalidArgumentException("Minimum booking duration must be greater than 0.");
        }

        if ($this->maxBookingDurationHours < $this->minBookingDurationHours) {
            throw new \InvalidArgumentException("Maximum booking duration must be greater than or equal to minimum booking duration.");
        }

        // Validate advance booking values
        if ($this->minAdvanceBookingHours < 0) {
            throw new \InvalidArgumentException("Minimum advance booking hours must be greater than or equal to 0.");
        }

        if ($this->maxAdvanceBookingDays < 0) {
            throw new \InvalidArgumentException("Maximum advance booking days must be greater than or equal to 0.");
        }

        // Validate other values
        if ($this->cancellationHours < 0) {
            throw new \InvalidArgumentException("Cancellation hours must be greater than or equal to 0.");
        }

        if ($this->maxBookingsPerWeek < 0) {
            throw new \InvalidArgumentException("Maximum bookings per week must be greater than or equal to 0.");
        }
    }

    /**
     * Create a BookingPolicy instance from an array
     *
     * @param array|null $data
     * @return static
     */
    public static function fromArray(?array $data): self
    {
        if (!$data) {
            return new self();
        }

        return new self(
            openingTime: $data['opening_time'] ?? '08:00',
            closingTime: $data['closing_time'] ?? '22:00',
            maxBookingDurationHours: (float)($data['max_booking_duration_hours'] ?? 8.0),
            minBookingDurationHours: (float)($data['min_booking_duration_hours'] ?? 0.5),
            maxAdvanceBookingDays: (int)($data['max_advance_booking_days'] ?? 90),
            minAdvanceBookingHours: (float)($data['min_advance_booking_hours'] ?? 1.0),
            cancellationHours: (int)($data['cancellation_hours'] ?? 24),
            maxBookingsPerWeek: (int)($data['max_bookings_per_week'] ?? 5)
        );
    }

    /**
     * Get the opening time as a Carbon instance for a specific date
     *
     * @param string $date Date in Y-m-d format
     * @return Carbon
     */
    public function getOpeningTime(string $date): Carbon
    {
        return Carbon::parse($date . ' ' . $this->openingTime);
    }

    /**
     * Get the closing time as a Carbon instance for a specific date
     *
     * @param string $date Date in Y-m-d format
     * @return Carbon
     */
    public function getClosingTime(string $date): Carbon
    {
        return Carbon::parse($date . ' ' . $this->closingTime);
    }

    /**
     * Get the operating hours for a specific date
     *
     * @param string $date Date in Y-m-d format
     * @return array ['opening' => Carbon, 'closing' => Carbon]
     */
    public function getOperatingHours(string $date): array
    {
        return [
            'opening' => $this->getOpeningTime($date),
            'closing' => $this->getClosingTime($date)
        ];
    }

    /**
     * Convert the object to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'opening_time' => $this->openingTime,
            'closing_time' => $this->closingTime,
            'max_booking_duration_hours' => $this->maxBookingDurationHours,
            'min_booking_duration_hours' => $this->minBookingDurationHours,
            'max_advance_booking_days' => $this->maxAdvanceBookingDays,
            'min_advance_booking_hours' => $this->minAdvanceBookingHours,
            'cancellation_hours' => $this->cancellationHours,
            'max_bookings_per_week' => $this->maxBookingsPerWeek,
        ];
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get the policy override for a specific user
     * 
     * @param int $userId
     * @return array|null
     */
    public function getOverrideForUser(int $userId): ?array
    {
        // Since we're using a value object now, we don't have database-backed overrides
        // Return null to indicate no override
        return null;
    }
    
    /**
     * Create a policy override for a specific user
     * 
     * @param int $userId
     * @param array $overrideData
     * @return void
     */
    public function createOverrideForUser(int $userId, array $overrideData): void
    {
        // Since we're using a value object now, we don't have database-backed overrides
        // This is a no-op method for compatibility with tests
    }

    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return BookingPolicy
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return new self();
        }
        
        $data = is_array($value) ? $value : json_decode($value, true);
        
        return self::fromArray($data);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return string|null
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }
        
        if (is_array($value)) {
            $value = self::fromArray($value);
        }
        
        if (!$value instanceof self) {
            throw new \InvalidArgumentException('The given value is not a BookingPolicy instance.');
        }
        
        return json_encode($value);
    }
} 