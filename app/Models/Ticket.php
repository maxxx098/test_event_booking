<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'price',
        'quantity',
        'event_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];


    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id'); 
    }


    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function isAvailable(int $requestedQuantity = 1): bool
    {
        return $this->quantity >= $requestedQuantity;
    }

    public function decreaseQuantity(int $amount): void
    {
        $this->decrement('quantity', $amount);
    }

    public function increaseQuantity(int $amount): void
    {
        $this->increment('quantity', $amount);
    }
}