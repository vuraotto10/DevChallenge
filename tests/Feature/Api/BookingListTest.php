<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingListTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_bookings_for_a_given_email(): void
    {
        $target = Booking::factory()->create([
            'email' => 'client@example.com',
            'scheduled_at' => now()->next('Monday')->setTime(9, 0),
        ]);

        Booking::factory()->create([
            'email' => 'someone-else@example.com',
            'scheduled_at' => now()->next('Monday')->setTime(10, 0),
        ]);

        $response = $this->getJson('/api/bookings?email=client@example.com');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id)
            ->assertJsonPath('data.0.email', 'client@example.com');
    }

    public function test_it_returns_an_empty_list_when_the_client_has_no_bookings(): void
    {
        $response = $this->getJson('/api/bookings?email=nobody@example.com');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(0, 'data');
    }

    public function test_it_orders_bookings_chronologically(): void
    {
        $later = Booking::factory()->create([
            'email' => 'client@example.com',
            'scheduled_at' => now()->next('Tuesday')->setTime(14, 0),
        ]);

        $earlier = Booking::factory()->create([
            'email' => 'client@example.com',
            'scheduled_at' => now()->next('Monday')->setTime(9, 0),
        ]);

        $response = $this->getJson('/api/bookings?email=client@example.com');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $earlier->id)
            ->assertJsonPath('data.1.id', $later->id);
    }

    public function test_it_requires_the_email_parameter(): void
    {
        $response = $this->getJson('/api/bookings');

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('email');
    }

    public function test_it_requires_a_valid_email_format(): void
    {
        $response = $this->getJson('/api/bookings?email=not-an-email');

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }
}
