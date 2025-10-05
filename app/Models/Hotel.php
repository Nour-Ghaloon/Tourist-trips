<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Trip, Room, City, Favorite, Review, User};
use App\Traits\HasIsFavorite;

class Hotel extends Model
{
    use HasFactory;
    use HasIsFavorite;
    protected $fillable = [
        'name',
        'description',
        'address',
        'contact_info',
        'rate',
        'city_id',
        'user_id'
    ];

    public function trips()
    {
        return $this->belongsToMany(Trip::class, 'trip_places', 'trip_id', 'hotel_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function rooms()
    {
        return $this->hasMany(Room::class, 'hotel_id');
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

    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
