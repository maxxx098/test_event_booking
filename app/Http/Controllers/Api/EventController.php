<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * Display a listing of events with pagination, search, and filters
     */
    public function index(Request $request)
    {
        try {
            $query = Event::with(['creator:id,name,email', 'tickets']);

            // Search by title or description
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by location
            if ($request->has('location')) {
                $query->where('location', 'like', "%{$request->location}%");
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('date', '<=', $request->date_to);
            }

            // Filter by specific date
            if ($request->has('date')) {
                $query->whereDate('date', $request->date);
            }

            // Sort by date (default: ascending)
            $sortOrder = $request->get('sort', 'asc');
            $query->orderBy('date', $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $events = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Events retrieved successfully',
                'data' => $events->items(),
                'meta' => [
                    'current_page' => $events->currentPage(),
                    'from' => $events->firstItem(),
                    'last_page' => $events->lastPage(),
                    'per_page' => $events->perPage(),
                    'to' => $events->lastItem(),
                    'total' => $events->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve events',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created event
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'date' => 'required|date|after:now',
                'location' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $event = Event::create([
                'title' => $request->title,
                'description' => $request->description,
                'date' => $request->date,
                'location' => $request->location,
                'created_by' => $request->user()->id,
            ]);

            $event->load(['creator:id,name,email', 'tickets']);

            return response()->json([
                'success' => true,
                'message' => 'Event created successfully',
                'data' => $event,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified event with tickets
     */
    public function show($id)
    {
        try {
            $event = Event::with(['creator:id,name,email', 'tickets'])->find($id);

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Event retrieved successfully',
                'data' => $event,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified event
     */
    public function update(Request $request, $id)
    {
        try {
            $event = Event::find($id);

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
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'date' => 'sometimes|required|date|after:now',
                'location' => 'sometimes|required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $event->update($request->only(['title', 'description', 'date', 'location']));
            $event->load(['creator:id,name,email', 'tickets']);

            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully',
                'data' => $event,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified event
     */
    public function destroy(Request $request, $id)
    {
        try {
            $event = Event::find($id);

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

            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get events created by the authenticated user
     */
    public function myEvents(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $events = Event::with(['creator:id,name,email', 'tickets'])
                ->where('created_by', $request->user()->id)
                ->orderBy('date', 'asc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Your events retrieved successfully',
                'data' => $events->items(),
                'meta' => [
                    'current_page' => $events->currentPage(),
                    'from' => $events->firstItem(),
                    'last_page' => $events->lastPage(),
                    'per_page' => $events->perPage(),
                    'to' => $events->lastItem(),
                    'total' => $events->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve your events',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}