<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public event routes (anyone can view events)
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::get('/events/{eventId}/tickets', [TicketController::class, 'index']);
Route::get('/events/{eventId}/tickets/{ticketId}', [TicketController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Booking routes - FIXED
    Route::post('/tickets/{id}/bookings', [BookingController::class, 'store'])
        ->middleware('prevent.double.booking');
    
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    // Payment routes - FIXED
    Route::post('/bookings/{id}/payment', [PaymentController::class, 'processPayment']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);

    // Organizer & Admin routes - Event Management
    Route::middleware('role:organizer,admin')->group(function () {
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{id}', [EventController::class, 'update']);
        Route::delete('/events/{id}', [EventController::class, 'destroy']);
        Route::get('/my-events', [EventController::class, 'myEvents']);

        // Ticket Management
        Route::post('/events/{eventId}/tickets', [TicketController::class, 'store']);
        Route::put('/events/{eventId}/tickets/{ticketId}', [TicketController::class, 'update']);
        Route::delete('/events/{eventId}/tickets/{ticketId}', [TicketController::class, 'destroy']);
    });

    // Admin only routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Admin-specific routes for managing all resources
        // Will be expanded in future sections
    });
});