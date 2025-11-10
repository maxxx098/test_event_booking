<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\CommonQueryScopes;

class Event extends Model
{
     use HasFactory, CommonQueryScopes;
     
    protected $fillable = [
        'title',
        'description',
        'date',
        'location',
        'created_by',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function bookings()
    {
        return $this->hasManyThrough(Booking::class, Ticket::class);
    }

    public function hasAvailableTickets(): bool
    {
        return $this->tickets()->where('quantity', '>', 0)->exists();
    }

    public function totalRevenue()
    {
        return $this->bookings()
            ->where('status', 'confirmed')
            ->join('payments', 'bookings.id', '=', 'payments.booking_id')
            ->where('payments.status', 'success')
            ->sum('payments.amount');
    }
}