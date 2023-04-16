<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rooms = Room::all()->map(function ($room) {
            if ($room->thumbnail) {
                $room->thumbnail_url = asset('storage/thumbnails/' . $room->thumbnail);
            }
            if ($room->images) {
                $room->images = json_decode($room->images, true); // decode the JSON string
                $room->images_url = collect($room->images)->map(function ($image) {
                    return asset('storage/images/' . $image);
                });
            }
            return $room;
        });

        return response()->json([
            'data' => $rooms
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'num_seats' => 'required|integer|min:1',
            'room_type' => 'required|in:room,sharedSpace',
            'availability' => 'required|boolean',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'name.required' => 'The name field is required.',
            'description.required' => 'The description field is required.',
            'price.required' => 'The price field is required.',
            'price.numeric' => 'The price field must be a numeric value.',
            'num_seats.required' => 'The number of seats field is required.',
            'num_seats.integer' => 'The number of seats field must be an integer value.',
            'num_seats.min' => 'The number of seats field must be at least 1.',
            'availability.required' => 'The availability field is required.',
            'availability.boolean' => 'The availability field must be a boolean value.',
            'thumbnail.image' => 'The thumbnail field must be an image.',
            'thumbnail.mimes' => 'The thumbnail field must be a file of type: jpeg, png, jpg.',
            'thumbnail.max' => 'The thumbnail file size must be less than 2MB.',
            'images.array' => 'The images field must be an array.',
            'images.*.image' => 'Each image in the images field must be an image.',
            'images.*.mimes' => 'Each image in the images field must be a file of type: jpeg, png, jpg.',
            'images.*.max' => 'Each image file size in the images field must be less than 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $room = new Room();

        $room->name = $request->name;
        $room->description = $request->description;
        $room->price = $request->price;
        $room->room_type = $request->room_type;
        $room->num_seats = $request->num_seats;
        if ($request->availability) {
            $room->availability = $request->availability;
        }

        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail');
            $thumbnailName = time() . '_' . $thumbnail->getClientOriginalName();
            $thumbnail->storeAs('public/thumbnails', $thumbnailName);
            $room->thumbnail = $thumbnailName;
        }

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $imageNames = [];

            foreach ($images as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->storeAs('public/images', $imageName);
                $imageNames[] = $imageName;
            }

            $room->images = json_encode($imageNames);;
        }

        $room->save();

        return response()->json([
            'message' => 'Room created successfully',
            'data' => $room
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {

        $room = Room::find($id);

        if (!$room) {
            return response()->json([
                'error' => 'Room not found'
            ], 404);
        }

        if ($room->thumbnail) {
            $room->thumbnail_url = asset('storage/thumbnails/' . $room->thumbnail);
        }

        if ($room->images) {
            $room->images = json_decode($room->images, true); // decode the JSON string
            $room->images_url = collect($room->images)->map(function ($image) {
                return asset('storage/images/' . $image);
            });
        }

        $reservations2 = $room->reservations()->get();
        // $room = Room::findOrFail($room_id);

        $total_seats = $room->num_seats;
        $date = Carbon::today(); // Get today's date

        // Get the reservations for the room on the given day
        $reservations = Reservation::where('room_id', $id)
            ->where('date', $date)
            ->get();
        
        // Calculate the number of seats that are already reserved
        $reserved_seats = $reservations->sum('num_seats');
        
        // Calculate the remaining seats
        $remaining_seats = $total_seats - $reserved_seats;

        return response()->json([
            'data' => $room,
            'reservations' => $reservations2,
            'remaining_seats' => $remaining_seats
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
            'price' => 'required|numeric',
            'num_seats' => 'required|integer|min:1',
            'availability' => 'boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $room = Room::findOrFail($id);

        $room->name = $request->name;
        $room->description = $request->description;
        $room->price = $request->price;
        $room->num_seats = $request->num_seats;
        if ($request->availability) {
            $room->availability = $request->availability;
        }

        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail');
            $thumbnailName = time() . '_' . $thumbnail->getClientOriginalName();
            $thumbnail->storeAs('public/thumbnails', $thumbnailName);
            $room->thumbnail = $thumbnailName;
        }

        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $imageNames = [];

            foreach ($images as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->storeAs('public/images', $imageName);
                $imageNames[] = $imageName;
            }

            $room->images =
                $imageNames;
        }

        $room->save();

        return response()->json([
            'message' => 'Room updated successfully',
            'data' => $room
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $room = Room::find($id);

        if (!$room) {
            return response()->json([
                'message' => 'Room not found',
            ], 404);
        }

        // Delete associated reservations
        $room->reservations()->delete();

        if ($room->thumbnail) {
            Storage::delete($room->thumbnail);
            if ($room->images) {
                $images = json_decode($room->images);
                foreach ($images as $image) {
                    Storage::delete($image);
                }
            }
        }

        $room->delete();

        return response()->json([
            'message' => 'Room deleted successfully',
        ]);
    }
}
