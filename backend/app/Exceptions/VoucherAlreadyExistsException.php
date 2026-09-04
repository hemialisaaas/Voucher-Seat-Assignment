<?php

namespace App\Exceptions;

use Exception;

class VoucherAlreadyExistsException extends Exception
{
    public function __construct(string $flightNumber, string $date)
    {
        parent::__construct(
            "Vouchers have already been generated for flight {$flightNumber} on {$date}."
        );
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 409); // 409 Conflict — most appropriate status for a duplicate resource.
    }
}
