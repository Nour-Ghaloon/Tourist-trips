<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Payment;
use App\Models\Reservation;

class Invoice extends Model
{
    use HasFactory;
    protected $fillable = ['total_amount', 'payment_status', 'reservation_id'];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function transction()
    {
        return $this->hasone(Wallet_transction::class);
    }
}
