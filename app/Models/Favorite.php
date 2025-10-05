<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Favorite extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'favoritable_id', 'favoritable_type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // public function favoritable()
    // {

    //     return $this->morphTo();
    // }

    public function favoritable()
    {
        return $this->morphTo()->morphWith([
            // مدينة + صورها
            City::class => ['media'],

            // فندق + صوره + الغرف وصورها
            Hotel::class => ['media'],

            // غرفة + صورها
            Room::class => ['media','roomtype'],

            // مطعم + صوره
            Restaurant::class => ['media'],

            // رحلة + صورها + الأماكن المرتبطة وصورها
            Trip::class => ['media'],

            // مكان + صوره
            Place::class => ['media'],

            // سيارة + صورها
            Vehicle::class => ['media'],

            Driver::class => ['media','user'],

            // دليل سياحي + صوره
            Tourguide::class => ['media','user'],

            // مكان تابع لرحلة + صوره
            Trip_place::class => ['media'],
        ]);
    }
}
