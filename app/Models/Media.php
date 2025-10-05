<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;
    protected $fillable = ['file_path', 'caption', 'alt_text', 'mediable_id', 'mediable_type', 'public_id'];
    public function mediable()
    {
        return $this->morphTo();
    }
}
