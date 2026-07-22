<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BookingSeeder extends Seeder
{
    /**
     * How many upcoming weekdays to spread bookings across.
     */
    private const WEEKDAYS_AHEAD = 10;

    /**
     * Fraction of available business-hour slots to fill, so the seeded
     * calendar looks like a realistic, partially booked schedule instead
     * of either an empty or a fully saturated one.
     */
    private const FILL_RATIO = 0.4;

    /**
     * Seed a predictable set of bookings.
     *
     * Slots are built explicitly from the same business rules the
     * application enforces (weekdays only, 08:00-17:00), so re-running
     * this seeder can never violate the unique `scheduled_at` index -
     * it always starts from a clean slate and picks from a de-duplicated
     * list of valid slots.
     */
    public function run(): void
    {
        Booking::query()->delete();

        $slots = $this->businessSlots(startingInDays: 1, weekdays: self::WEEKDAYS_AHEAD);

        $slotsToFill = $slots->shuffle()->take((int) round($slots->count() * self::FILL_RATIO));

        $slotsToFill->each(
            fn (Carbon $slot) => Booking::factory()->create(['scheduled_at' => $slot])
        );

        $this->command?->info(sprintf(
            'Seeded %d booking(s) across %d available business-hour slots.',
            $slotsToFill->count(),
            $slots->count()
        ));
    }

    /**
     * Build every valid (weekday, business-hour) slot for the given
     * number of upcoming weekdays, starting a few days from now.
     *
     * @return Collection<int, Carbon>
     */
    private function businessSlots(int $startingInDays, int $weekdays): Collection
    {
        $slots = collect();
        $date = Carbon::now()->addDays($startingInDays)->startOfDay();
        $weekdaysCollected = 0;

        while ($weekdaysCollected < $weekdays) {
            if (! $date->isWeekend()) {
                for ($hour = 8; $hour < 17; $hour++) {
                    $slots->push($date->copy()->setTime($hour, 0, 0));
                }
                $weekdaysCollected++;
            }

            $date = $date->copy()->addDay();
        }

        return $slots;
    }
}
