<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Place, Hotel, Restaurant, Trip};
use App\Traits\HasIsFavorite;

class City extends Model
{
    use HasFactory;
    use HasIsFavorite;

    protected $fillable = [
        'name',
        'country',
        'latitude',
        'longitude',
        'description',
    ];


    public function trips()
    {
        return $this->hasMany(Trip::class, 'city_id');
    }
    public function hotels()
    {
        return $this->hasMany(Hotel::class, 'city_id');
    }
    public function tourguide()
    {
        return $this->hasMany(Tourguide::class, 'city_id');
    }
    public function places()
    {
        return $this->hasMany(Place::class, 'city_id');
    }
    public function restaurant()
    {
        return $this->hasMany(Restaurant::class, 'city_id');
    }
    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }
    public function favoritedByUsers()
    {
        return $this->morphToMany(User::class, 'favoritable', 'favorites');
    }
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
}
