<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice;
class Payment extends Model
{
    use HasFactory;
    protected $fillable=['amount','payment_method','status','invoice_id'];

    // public function invoice()
    // {
    //     return $this->belongsTo(Invoice::class);
    // }
}
