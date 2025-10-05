<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Reservation, Vehicle, City, Place, Hotel, Restaurant, Discount, Favorite, Review};
use App\Traits\HasIsFavorite;

class Trip extends Model
{
    use HasFactory;
    use HasIsFavorite;
    protected $fillable = [
        'name',
        'description',
        'type',
        'start_date',
        'end_date',
        'base_price',
        'city_id',
        'vehicle_id',
        'tourguide_id',
        'capacity',
        'is_custom',
        'created_by',
        'duration_days',
        'duration_time',
        'meeting_point',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'trip_id');
    }

    public function itineraries()
    {
        return $this->hasMany(TripItinerary::class);
    }

    public function vehcile()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function places()
    {
        return $this->belongsToMany(Place::class, 'trip_places', 'trip_id', 'place_id');
    }
    public function hotels()
    {
        return $this->belongsToMany(Hotel::class, 'trip_places', 'trip_id', 'hotel_id');
    }
    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'trip_places', 'trip_id', 'restaurant_id');
    }
    public function discounts()
    {
        return $this->morphMany(Discount::class, 'discountable');
    }
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
    public function reservation()
    {
        return $this->morphMany(Reservation::class, 'reservable');
    }
    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }
    public function favoritedByUsers()
    {
        return $this->morphToMany(User::class, 'favoritable', 'favorites');
    }
}
