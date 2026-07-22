<?php

namespace Database\Factories;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'scheduled_at' => $this->randomBusinessSlot(),
        ];
    }

    /**
     * Pick a random weekday, business-hours (08:00-16:00) datetime within
     * the next 30 days. Callers that need guaranteed-unique slots (e.g. a
     * seeder creating many bookings) should override 'scheduled_at' when
     * calling create()/make() instead of relying on this default.
     */
    protected function randomBusinessSlot(): Carbon
    {
        do {
            $date = Carbon::now()->addDays(fake()->numberBetween(1, 30));
        } while ($date->isWeekend());

        return $date->setTime(fake()->numberBetween(8, 16), 0, 0);
    }
}
