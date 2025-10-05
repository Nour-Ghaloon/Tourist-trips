<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Place, Favorite, Review, User, Reservation};
use App\Traits\HasIsFavorite;

class Tourguide extends Model
{
    protected $casts = ['language' => 'array',];
    use HasFactory;
    use HasIsFavorite;
    protected $fillable = [
        'name',
        'phone',
        'email',
        'language',
        'price',
        'user_id',
        'city_id'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
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
