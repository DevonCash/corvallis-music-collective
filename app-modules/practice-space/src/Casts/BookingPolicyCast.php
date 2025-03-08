<?php

namespace CorvMC\PracticeSpace\Casts;

use CorvMC\PracticeSpace\ValueObjects\BookingPolicy;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class BookingPolicyCast implements CastsAttributes
{
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
            return new BookingPolicy();
        }
        
        $data = is_array($value) ? $value : json_decode($value, true);
        
        return BookingPolicy::fromArray($data);
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
            $value = BookingPolicy::fromArray($value);
        }
        
        if (!$value instanceof BookingPolicy) {
            throw new InvalidArgumentException('The given value is not a BookingPolicy instance.');
        }
        
        return json_encode($value);
    }
} 