<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    protected $model = Voucher::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'crew_name' => $this->faker->name(),
            'crew_id' => (string) $this->faker->unique()->numberBetween(10000, 99999),
            'flight_number' => strtoupper($this->faker->bothify('??###')),
            'flight_date' => $this->faker->date('Y-m-d'),
            'aircraft_type' => $this->faker->randomElement(['ATR', 'Airbus 320', 'Boeing 737 Max']),
            'seat1' => '1A',
            'seat2' => '2B',
            'seat3' => '3C',
        ];
    }
}
