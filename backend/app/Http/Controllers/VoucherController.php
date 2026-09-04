<?php

namespace App\Http\Controllers;

use App\Exceptions\VoucherAlreadyExistsException;
use App\Http\Requests\CheckVoucherRequest;
use App\Http\Requests\GenerateVoucherRequest;
use App\Http\Resources\VoucherCheckResource;
use App\Http\Resources\VoucherResource;
use App\Models\Voucher;
use App\Services\SeatGeneratorService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class VoucherController extends Controller
{
    public function __construct(private readonly SeatGeneratorService $seatGenerator)
    {
    }

    /**
     * POST /api/check
     *
     * Checks whether voucher assignments already exist for a given
     * flight number and date.
     */
    public function check(CheckVoucherRequest $request): JsonResponse
    {
        $exists = Voucher::query()
            ->where('flight_number', $request->input('flightNumber'))
            ->where('flight_date', $request->input('date'))
            ->exists();

        return (new VoucherCheckResource($exists))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * POST /api/generate
     *
     * Generates 3 random unique seats for the given flight/aircraft,
     * persists the assignment, and returns the result.
     *
     * @throws VoucherAlreadyExistsException if a voucher assignment already
     *         exists for this flight number and date.
     */
    public function generate(GenerateVoucherRequest $request): JsonResponse
    {
        $flightNumber = $request->input('flightNumber');
        $date = $request->input('date');

        $alreadyExists = Voucher::query()
            ->where('flight_number', $flightNumber)
            ->where('flight_date', $date)
            ->exists();

        if ($alreadyExists) {
            throw new VoucherAlreadyExistsException($flightNumber, $date);
        }

        [$seat1, $seat2, $seat3] = $this->seatGenerator->generateSeats($request->input('aircraft'));

        try {
            $voucher = Voucher::create([
                'crew_name' => $request->input('name'),
                'crew_id' => $request->input('id'),
                'flight_number' => $flightNumber,
                'flight_date' => $date,
                'aircraft_type' => $request->input('aircraft'),
                'seat1' => $seat1,
                'seat2' => $seat2,
                'seat3' => $seat3,
            ]);
        } catch (QueryException $e) {
            // Guards against a race condition: two concurrent requests could
            // both pass the exists() check above before either has inserted
            // a row. The database-level unique constraint on
            // (flight_number, flight_date) catches that case here.
            if ($this->isUniqueConstraintViolation($e)) {
                throw new VoucherAlreadyExistsException($flightNumber, $date);
            }

            throw $e;
        }

        return (new VoucherResource($voucher))
            ->response()
            ->setStatusCode(201);
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        // SQLite unique constraint violations surface SQLSTATE 23000.
        return $e->getCode() === '23000';
    }
}
