<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Trip;
use App\Models\Driver;
use App\Models\Reservation;
use App\Traits\HasIsFavorite;

class Vehicle extends Model
{

    use HasFactory;
    use HasIsFavorite;
    protected $fillable = ['type', 'capacity', 'name', 'price', 'driver_id'];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
    public function trips()
    {
        return $this->hasMany(Trip::class, 'vehcile_id');
    }
    public function reservation()
    {
        return $this->morphMany(Reservation::class, 'reservable');
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
