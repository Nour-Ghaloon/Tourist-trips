<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripItinerary extends Model
{
    use HasFactory;
    protected $fillable = [
        'day_number',
        'title',
        'start_time',
        'end_time',
        'map_location',
        'notes',
        'description',
        'short_title',
        'full_description',
        'trip_id',
        'place_id',
        'hotel_id',
        'restaurant_id',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
