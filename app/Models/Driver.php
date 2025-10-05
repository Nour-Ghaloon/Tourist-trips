<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Vehicle, Favorite, Review, User, Reservation};
use App\Traits\HasIsFavorite;

class Driver extends Model
{
    use HasFactory;
    use HasIsFavorite;
    protected $fillable = ['license_number', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'driver_id');
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
        return $this->morphMany(Reservation::class, 'favoritable');
    }
    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
