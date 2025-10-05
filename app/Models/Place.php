<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Trip, City, Tourguide, Favorite, Review};
use App\Traits\HasIsFavorite;

class Place extends Model
{
    use HasFactory;
    use HasIsFavorite;
    protected $fillable = [
        'name',
        'description',
        'entry_free',
        'latitude',
        'longitude',
        'type',
        'icon',
        'city_id'
    ];

    public function trips()
    {
        return $this->belongsToMany(Trip::class, 'trip_places', 'trip_id', 'place_id');
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
