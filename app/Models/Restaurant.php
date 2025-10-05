<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{City, Favorite, Review, Trip, Reservation, User};
use App\Traits\HasIsFavorite;

class Restaurant extends Model
{
    use HasFactory;
    use HasIsFavorite;
    protected $fillable = [
        'name',
        'address',
        'contact_info',
        'opening_hours',
        'rate',
        'city_id',
        'description',
        'user_id',
        'capacity',
        // 'menu',
        // 'menu_public_id'
    ];

    public function trips()
    {
        return $this->belongsToMany(Trip::class, 'trip_places', 'trip_id', 'restaurant_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function favoritedByUsers()
    {
        return $this->morphToMany(User::class, 'favoritable', 'favorites');
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
}
