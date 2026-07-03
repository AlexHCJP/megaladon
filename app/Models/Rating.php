<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'rate',
        'comment',
    ];

    public function ratingable()
    {
        return $this->morphTo('ratingable');
    }

    public function media()
    {
        return $this->morphMany(MediaFiles::class, 'mediable');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
