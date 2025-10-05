<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\{Driver, Favorite, Review, Reservation, Tourguide, Discount};


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function hotel()
    {
        return $this->hasMany(Hotel::class, 'user_id');
    }
    public function restuarant()
    {
        return $this->hasMany(Restaurant::class, 'user_id');
    }
    public function drivers()
    {
        return $this->hasMany(Driver::class, 'user_id');
    }
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'user_id');
    }
    public function tourguide()
    {
        return $this->hasMany(Tourguide::class, 'user_id');
    }
    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'user_id');
    }
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
    public function favoriteCities()
    {
        return $this->morphedByMany(City::class, 'favoritable', 'favorites');
    }

    public function favoriteDrivers()
    {
        return $this->morphedByMany(Driver::class, 'favoritable', 'favorites');
    }

    public function favoriteHotels()
    {
        return $this->morphedByMany(Hotel::class, 'favoritable', 'favorites');
    }

    public function favoritePlaces()
    {
        return $this->morphedByMany(Place::class, 'favoritable', 'favorites');
    }

    public function favoriteRestaurants()
    {
        return $this->morphedByMany(Restaurant::class, 'favoritable', 'favorites');
    }

    public function favoriteRooms()
    {
        return $this->morphedByMany(Room::class, 'favoritable', 'favorites');
    }

    public function favoriteTourGuides()
    {
        return $this->morphedByMany(Tourguide::class, 'favoritable', 'favorites');
    }

    public function favoriteTripPlaces()
    {
        return $this->morphedByMany(Trip_place::class, 'favoritable', 'favorites');
    }

    public function favoriteTrips()
    {
        return $this->morphedByMany(Trip::class, 'favoritable', 'favorites');
    }

    public function favoriteVehicles()
    {
        return $this->morphedByMany(Vehicle::class, 'favoritable', 'favorites');
    }
}
