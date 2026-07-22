<?php

namespace Tests\Feature;

use App\Models\Booking;
use Database\Seeders\BookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_bookings_without_scheduling_conflicts(): void
    {
        (new BookingSeeder())->run();

        $bookings = Booking::all();

        $this->assertGreaterThan(0, $bookings->count());

        $uniqueSlots = $bookings->pluck('scheduled_at')->unique();
        $this->assertCount(
            $bookings->count(),
            $uniqueSlots,
            'Seeded bookings must not collide on the same scheduled_at slot.'
        );
    }

    public function test_it_only_seeds_weekday_business_hours(): void
    {
        (new BookingSeeder())->run();

        foreach (Booking::all() as $booking) {
            $this->assertFalse(
                $booking->scheduled_at->isWeekend(),
                'Booking must not fall on a weekend.'
            );
            $this->assertGreaterThanOrEqual(8, $booking->scheduled_at->hour);
            $this->assertLessThan(17, $booking->scheduled_at->hour);
        }
    }

    public function test_it_is_safe_to_reseed_without_unique_constraint_violations(): void
    {
        (new BookingSeeder())->run();
        (new BookingSeeder())->run();

        $this->assertGreaterThan(0, Booking::count());
    }
}
