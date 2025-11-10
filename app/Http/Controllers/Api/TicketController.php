<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    /**
     * Display tickets for a specific event
     */
    public function index($eventId)
    {
        try {
            $event = Event::find($eventId);

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            $tickets = Ticket::where('event_id', $eventId)->get();

            return response()->json([
                'success' => true,
                'message' => 'Tickets retrieved successfully',
                'data' => $tickets,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tickets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created ticket for an event
     */
    public function store(Request $request, $eventId)
    {
        try {
            $event = Event::find($eventId);

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            // Check if user owns this event (organizer) or is admin
            if (!$request->user()->isAdmin() && $event->created_by !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not own this event.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'type' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $ticket = Ticket::create([
                'event_id' => $eventId,
                'type' => $request->type,
                'price' => $request->price,
                'quantity' => $request->quantity,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ticket created successfully',
                'data' => $ticket,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified ticket
     */
    public function show($eventId, $ticketId)
    {
        try {
            $ticket = Ticket::where('event_id', $eventId)->find($ticketId);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ticket retrieved successfully',
                'data' => $ticket,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified ticket
     */
    public function update(Request $request, $eventId, $ticketId)
    {
        try {
            $event = Event::find($eventId);

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            $ticket = Ticket::where('event_id', $eventId)->find($ticketId);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found',
                ], 404);
            }

            // Check if user owns this event (organizer) or is admin
            if (!$request->user()->isAdmin() && $event->created_by !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not own this event.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'type' => 'sometimes|required|string|max:255',
                'price' => 'sometimes|required|numeric|min:0',
                'quantity' => 'sometimes|required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $ticket->update($request->only(['type', 'price', 'quantity']));

            return response()->json([
                'success' => true,
                'message' => 'Ticket updated successfully',
                'data' => $ticket,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified ticket
     */
    public function destroy(Request $request, $eventId, $ticketId)
    {
        try {
            $event = Event::find($eventId);

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            $ticket = Ticket::where('event_id', $eventId)->find($ticketId);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found',
                ], 404);
            }

            // Check if user owns this event (organizer) or is admin
            if (!$request->user()->isAdmin() && $event->created_by !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not own this event.',
                ], 403);
            }

            $ticket->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ticket deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}