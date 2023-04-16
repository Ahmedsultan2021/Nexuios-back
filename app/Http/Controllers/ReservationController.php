<?php

namespace App\Http\Controllers;

use App\Models\Reservation as ModelsReservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    public function index()
    {
        $reservations = ModelsReservation::paginate(15);

        return response()->json([
            'success' => true,
            'data' => $reservations,
        ]);
    }
    public function store(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'num_seats' => 'required_if:type,sharedSpace|integer|min:1',
            'type' => 'required|in:room,sharedSpace',
        ], [
            'room_id.required' => 'The room ID field is required.',
            'room_id.exists' => 'The selected room ID is invalid.',
            'date.required' => 'The date field is required.',
            'date.date' => 'The date field must be a valid date.',
            'date.after_or_equal' => 'The date field must be equal to or greater than today\'s date.',
            'start_time.required' => 'The start time field is required.',
            'start_time.date_format' => 'The start time field must be in H:i format.',
            'end_time.required' => 'The end time field is required.',
            'end_time.date_format' => 'The end time field must be in H:i format.',
            'end_time.after' => 'The end time field must be greater than the start time field.',
            'num_seats.required_if' => 'The number of seats field is required for shared space type.',
            'num_seats.integer' => 'The number of seats field must be an integer value.',
            'num_seats.min' => 'The number of seats field must be at least 1.',
            'type.required' => 'The type field is required.',
            'type.in' => 'The selected type is invalid.',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = [
            'room_id' => $request->input('room_id'),
            'date' => $request->input('date'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'num_seats' => $request->input('num_seats') ?? null,
            'type' => $request->input('type'),
        ];

        // Get the room and check availability
        $room = Room::find($validatedData['room_id']);
        if (!$room || !$room->availability) {
            return response()->json(['message' => 'The room is not available.'], 400);
        }

        // Check if the room type matches the reservation type
        if ($validatedData['type'] !== $room->room_type) {
            return response()->json(['message' => 'The room type does not match the reservation type.'], 400);
        }

        // Check if the reservation overlaps with an existing reservation for the same room
        $overlappingReservation = ModelsReservation::where('room_id', $room->id)
            ->where('date', $validatedData['date'])
            ->where(function ($query) use ($validatedData) {
                $query->whereBetween('start_time', [$validatedData['start_time'], $validatedData['end_time']])
                    ->orWhereBetween('end_time', [$validatedData['start_time'], $validatedData['end_time']])
                    ->orWhere(function ($query) use ($validatedData) {
                        $query->where('start_time', '<', $validatedData['start_time'])
                            ->where('end_time', '>', $validatedData['end_time']);
                    });
            })
            ->get();

        // Calculate the total number of seats already reserved during the requested interval
        $totalReservedSeats = 0;
        foreach ($overlappingReservation as $reservation) {
            if ($reservation->type === 'sharedSpace') {
                $totalReservedSeats += $reservation->num_seats;
            } else {
                $totalReservedSeats = $room->num_seats;
                break;
            }
        }


        if ($validatedData["type"] === 'room') {

            if ($overlappingReservation->isNotEmpty()) {
                return response()->json(['message' => 'The room is already reserved for this time.'], 400);
            }
        }
        $availableSeats = $room->num_seats;

        foreach ($overlappingReservation as $reservation) {
            if ($reservation->type === 'sharedSpace') {
                $availableSeats -= $reservation->num_seats;
            } else {
                $availableSeats = 0;
                break;
            }
        }

        // Check if there are enough seats available in the shared space for the requested reservation
        if ($validatedData['type'] === 'sharedSpace' && ($totalReservedSeats + $validatedData['num_seats']) > $room->num_seats) {
            return response()->json(['message' => 'The room does not have enough available seats.'], 400);
        }

        // Create the reservation
        $reservation = new ModelsReservation();
        $reservation->room_id = $room->id;
        $reservation->date = $validatedData['date'];
        $reservation->start_time = $validatedData['start_time'];
        $reservation->end_time = $validatedData['end_time'];
        $reservation->num_seats = $validatedData['num_seats'] ?? null;
        $reservation->type = $validatedData['type'];
        $reservation->save();

        // // Update the room availability
        // if ($validatedData['type'] === 'sharedSpace') {
        //     $room->num_seats -= $validatedData['num_seats'];
        // } else {
        //     $room->availability = false;
        // }
        $room->save();

        return response()->json([
            'message' => 'Reservation created successfully.',
            'data' => $reservation,
        ]);
    }
    public function show($id)
    {
        $reservation = ModelsReservation::find($id);
        return response()->json([
            'success' => true,
            'data' => $reservation,
        ]);
    }
    public function update($id, Request $request)
    {
        // Validate the request data
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'num_seats' => 'required_if:type,sharedSpace|integer|min:1',
            'type' => 'required|in:room,sharedSpace',
        ], [
            'room_id.required' => 'The room ID field is required.',
            'room_id.exists' => 'The selected room ID is invalid.',
            'date.required' => 'The date field is required.',
            'date.date' => 'The date field must be a valid date.',
            'date.after_or_equal' => 'The date field must be equal to or greater than today\'s date.',
            'start_time.required' => 'The start time field is required.',
            'start_time.date_format' => 'The start time field must be in H:i format.',
            'end_time.required' => 'The end time field is required.',
            'end_time.date_format' => 'The end time field must be in H:i format.',
            'end_time.after' => 'The end time field must be greater than the start time field.',
            'num_seats.required_if' => 'The number of seats field is required for shared space type.',
            'num_seats.integer' => 'The number of seats field must be an integer value.',
            'num_seats.min' => 'The number of seats field must be at least 1.',
            'type.required' => 'The type field is required.',
            'type.in' => 'The selected type is invalid.',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $validatedData = [
            'room_id' => $request->input('room_id'),
            'date' => $request->input('date'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'num_seats' => $request->input('num_seats') ?? null,
            'type' => $request->input('type'),
        ];
        $reservation = ModelsReservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found.'], 404);
        }

        $room = Room::find($validatedData['room_id']);

        if (!$room || !$room->availability) {
            return response()->json(['message' => 'The room is not available.'], 400);
        }

        if ($validatedData['type'] !== $room->room_type) {
            return response()->json(['message' => 'The room type does not match the reservation type.'], 400);
        }

        $overlappingReservation = ModelsReservation::where('room_id', $room->id)
            ->where('date', $validatedData['date'])
            ->where(function ($query) use ($validatedData, $reservation) {
                $query->whereBetween('start_time', [$validatedData['start_time'], $validatedData['end_time']])
                    ->orWhereBetween('end_time', [$validatedData['start_time'], $validatedData['end_time']])
                    ->orWhere(function ($query) use ($validatedData, $reservation) {
                        $query->where('start_time', '<', $validatedData['start_time'])
                            ->where('end_time', '>', $validatedData['end_time']);
                    });
            })
            ->where('id', '<>', $reservation->id)
            ->get();

        if ($validatedData["type"] === 'room') {
            if ($overlappingReservation->isNotEmpty()) {
                return response()->json(['message' => 'The room is already reserved for this time.'], 400);
            }
        }

        $availableSeats = $room->num_seats;

        foreach ($overlappingReservation as $res) {
            if ($res->type === 'sharedSpace') {
                $availableSeats -= $res->num_seats;
            } else {
                $availableSeats = 0;
                break;
            }
        }

        if ($validatedData['type'] === 'sharedSpace' && $availableSeats < $validatedData['num_seats']) {
            return response()->json(['message' => 'The room does not have enough available seats.'], 400);
        }

        $reservation->room_id = $room->id;
        $reservation->date = $validatedData['date'];
        $reservation->start_time = $validatedData['start_time'];
        $reservation->end_time = $validatedData['end_time'];
        $reservation->num_seats = $validatedData['num_seats'];
        $reservation->type = $validatedData['type'];
        $reservation->save();

        return response()->json([
            'message' => 'Reservation updated successfully.',
            'data' => $reservation,
        ]);
    }
    public function destroy($id)
    {

        // Find the reservation
        $reservation = ModelsReservation::find($id);
        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found.'], 404);
        }

        // Check if reservation date is in the future
        $reservationDate = Carbon::parse($reservation->date);
        if ($reservationDate->isFuture()) {
            // Update the room availability or shared space seats
            $room = Room::find($reservation->room_id);
            if (!$room) {
                return response()->json(['message' => 'Room not found.'], 404);
            }


            $room->availability = true;

            $room->save();
        }

        // Delete the reservation
        $reservation->delete();

        return response()->json(['message' => 'Reservation deleted successfully.']);
    }
    public function search(Request $request)
    {
      
    }
}
