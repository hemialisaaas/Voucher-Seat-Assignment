<?php

namespace App\Services;

use InvalidArgumentException;

class SeatGeneratorService
{
    /**
     * Seat map configuration for each supported aircraft type.
     *
     * Each entry defines the valid row range (inclusive) and the valid
     * seat letters per row, as specified in the assessment's seat layout
     * reference table.
     *
     * @var array<string, array{rows: array{int, int}, letters: array<int, string>}>
     */
    private const SEAT_MAPS = [
        'ATR' => [
            'rows' => [1, 18],
            'letters' => ['A', 'C', 'D', 'F'],
        ],
        'Airbus 320' => [
            'rows' => [1, 32],
            'letters' => ['A', 'B', 'C', 'D', 'E', 'F'],
        ],
        'Boeing 737 Max' => [
            'rows' => [1, 32],
            'letters' => ['A', 'B', 'C', 'D', 'E', 'F'],
        ],
    ];

    /**
     * Generate 3 unique, valid, randomly chosen seats for the given
     * aircraft type.
     *
     * @return array<int, string> Exactly 3 unique seat codes, e.g. ["3B", "7C", "14D"]
     *
     * @throws InvalidArgumentException if the aircraft type is not supported.
     */
    public function generateSeats(string $aircraftType): array
    {
        $map = self::SEAT_MAPS[$aircraftType] ?? null;

        if ($map === null) {
            throw new InvalidArgumentException("Unsupported aircraft type: {$aircraftType}");
        }

        [$minRow, $maxRow] = $map['rows'];
        $letters = $map['letters'];

        // Build the full pool of valid seat codes for this aircraft, e.g.
        // ["1A", "1C", "1D", "1F", "2A", ... "18F"] for an ATR.
        $pool = [];
        for ($row = $minRow; $row <= $maxRow; $row++) {
            foreach ($letters as $letter) {
                $pool[] = $row . $letter;
            }
        }

        // Pick 3 unique random seats from the pool without replacement.
        $keys = array_rand($pool, 3);

        // array_rand returns a single value (not an array) when picking 1 item;
        // since we always request 3 here, $keys is guaranteed to be an array.
        $seats = array_map(fn ($key) => $pool[$key], $keys);

        // Shuffle so the returned order isn't tied to the pool's row/letter order.
        shuffle($seats);

        return $seats;
    }

    /**
     * Return the list of aircraft types this service supports.
     *
     * @return array<int, string>
     */
    public function supportedAircraftTypes(): array
    {
        return array_keys(self::SEAT_MAPS);
    }
}
