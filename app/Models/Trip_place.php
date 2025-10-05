<?php

namespace App\Models;

use App\Traits\HasIsFavorite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip_place extends Model
{
    use HasFactory;
    use HasIsFavorite;
    protected $fillable = ['trip_id', 'place_id', 'hotel_id', 'restaurant_id'];

    public function favoritedByUsers()
    {
        return $this->morphToMany(User::class, 'favoritable', 'favorites');
    }
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }
}
