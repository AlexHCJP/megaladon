<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderView extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'order_id', 'seen_status', 'seen_offers_count'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
