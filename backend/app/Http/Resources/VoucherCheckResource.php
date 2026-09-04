<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherCheckResource extends JsonResource
{
    public function __construct(private readonly bool $exists)
    {
        // No underlying model to pass to the parent — this resource
        // simply wraps a boolean existence flag.
        parent::__construct(null);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'exists' => $this->exists,
        ];
    }
}
