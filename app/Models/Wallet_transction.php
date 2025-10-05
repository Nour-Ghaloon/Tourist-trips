<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet_transction extends Model
{
    use HasFactory;
    protected $fillable=['type','amount','description','wallet_id'];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
