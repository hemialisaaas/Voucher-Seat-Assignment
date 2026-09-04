<?php

namespace Tests\Feature;

use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_returns_false_when_no_voucher_exists(): void
    {
        $response = $this->postJson('/api/check', [
            'flightNumber' => 'GA102',
            'date' => '2025-07-12',
        ]);

        $response->assertOk()->assertJson(['exists' => false]);
    }

    public function test_check_returns_true_when_voucher_already_exists(): void
    {
        Voucher::factory()->create([
            'flight_number' => 'GA102',
            'flight_date' => '2025-07-12',
        ]);

        $response = $this->postJson('/api/check', [
            'flightNumber' => 'GA102',
            'date' => '2025-07-12',
        ]);

        $response->assertOk()->assertJson(['exists' => true]);
    }

    public function test_check_requires_flight_number_and_date(): void
    {
        $response = $this->postJson('/api/check', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['flightNumber', 'date']);
    }

    public function test_generate_creates_voucher_with_three_unique_valid_seats(): void
    {
        $response = $this->postJson('/api/generate', [
            'name' => 'Sarah',
            'id' => '98123',
            'flightNumber' => 'ID102',
            'date' => '2025-07-12',
            'aircraft' => 'Airbus 320',
        ]);

        $response->assertCreated()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'seats']);

        $seats = $response->json('seats');

        $this->assertCount(3, $seats);
        $this->assertCount(3, array_unique($seats), 'Seats must be unique.');

        foreach ($seats as $seat) {
            $this->assertMatchesRegularExpression('/^([1-9]|[12][0-9]|3[0-2])[A-F]$/', $seat);
        }

        $this->assertDatabaseHas('vouchers', [
            'crew_name' => 'Sarah',
            'crew_id' => '98123',
            'flight_number' => 'ID102',
            'flight_date' => '2025-07-12',
            'aircraft_type' => 'Airbus 320',
        ]);
    }

    public function test_generate_only_produces_valid_seats_for_atr(): void
    {
        $response = $this->postJson('/api/generate', [
            'name' => 'John',
            'id' => '11111',
            'flightNumber' => 'QG201',
            'date' => '2025-08-01',
            'aircraft' => 'ATR',
        ]);

        $response->assertCreated();

        $seats = $response->json('seats');

        // ATR only has rows 1-18 and letters A, C, D, F — seat "5B" (for example)
        // must never be produced for this aircraft type.
        foreach ($seats as $seat) {
            $this->assertMatchesRegularExpression('/^([1-9]|1[0-8])[ACDF]$/', $seat);
        }
    }

    public function test_generate_rejects_duplicate_flight_and_date(): void
    {
        $payload = [
            'name' => 'Sarah',
            'id' => '98123',
            'flightNumber' => 'ID102',
            'date' => '2025-07-12',
            'aircraft' => 'Airbus 320',
        ];

        $this->postJson('/api/generate', $payload)->assertCreated();

        $response = $this->postJson('/api/generate', $payload);

        $response->assertStatus(409)
            ->assertJson(['success' => false]);

        $this->assertDatabaseCount('vouchers', 1);
    }

    public function test_generate_requires_valid_aircraft_type(): void
    {
        $response = $this->postJson('/api/generate', [
            'name' => 'Sarah',
            'id' => '98123',
            'flightNumber' => 'ID102',
            'date' => '2025-07-12',
            'aircraft' => 'Concorde',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['aircraft']);
    }

    public function test_generate_requires_all_fields(): void
    {
        $response = $this->postJson('/api/generate', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'id', 'flightNumber', 'date', 'aircraft']);
    }
}
