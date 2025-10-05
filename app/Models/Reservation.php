<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Invoice, User, Discount, Trip};


class Reservation extends Model
{
    use HasFactory;
    protected $fillable = [
        'number_people',
        'number_children',
        'reservable_id',
        'reservable_type',
        'start_date',
        'end_date',
        'status',
        'comment',
        'guest_count',
        'user_id',
        'trip_id',
        'invoice_id',
    ];

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'reservation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function discounts()
    {
        return $this->morphMany(Discount::class, 'discountable');
    }
    public function reservable()
    {

        return $this->morphto();
    }
}
