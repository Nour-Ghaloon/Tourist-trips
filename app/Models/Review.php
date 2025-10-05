<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class Review extends Model
{
    use HasFactory;
    protected $fillable=['comment','rate','reviewable_id','reviewable_type','user_id'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function reviewable(){

        return $this->morphto();
    }
}
