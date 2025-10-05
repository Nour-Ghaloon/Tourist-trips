<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Hotel,Room};


class Roomtype extends Model
{
    use HasFactory;
    protected $fillable=['name','description'];

    public function hotels()
    {
        return $this->belongsToMany(Hotel::class, 'rooms', 'roomtype_id', 'hotel_id');
    }
    public function rooms()
    {
        return $this->hasMany(Room::class,'roomtype_id');
    }
}
