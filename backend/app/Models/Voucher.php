<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'crew_name',
        'crew_id',
        'flight_number',
        'flight_date',
        'aircraft_type',
        'seat1',
        'seat2',
        'seat3',
    ];

    /**
     * Return the three assigned seats as an array.
     *
     * Kept as an accessor (rather than storing seats as JSON) since the
     * schema explicitly requires three discrete seat1/seat2/seat3 columns.
     *
     * @return array<int, string>
     */
    public function getSeatsAttribute(): array
    {
        return [$this->seat1, $this->seat2, $this->seat3];
    }
}
