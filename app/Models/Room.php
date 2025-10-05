<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Hotel, Favorite, Review, Roomtype, Reservation};
use App\Traits\HasIsFavorite;

class Room extends Model
{
    use HasFactory;
    use HasIsFavorite;
    protected $fillable = ['price_per_night', 'hotel_id', 'roomtype_id'];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
    public function roomtype()
    {
        return $this->belongsTo(Roomtype::class);
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
