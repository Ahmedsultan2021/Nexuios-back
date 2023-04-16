<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\logoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use logoutController as GlobalLogoutController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/login', [LoginController::class, 'loginUser']);
// Route::post('/logout', [GlobalLogoutController::class,'logout']);
Route::post('/register', [RegisterController::class, 'register']);
Route::apiResource('room',RoomController::class);
Route::apiResource('reservations',ReservationController::class);
Route::get('/users',  [UserController::class, 'index']);
Route::delete('/deleteUser/{id}',  [UserController::class, 'delete']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', function (Request $request) {
        try {
            $request->user()->tokens()->delete();
            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    });
});

Route::get('/reservations2/search',function (Request $request){
    $query = $request->input('q');

    if (!$query) {
        return response()->json(['message' => 'Reservation not found.'], 404);
    }

    $results = DB::table('reservations')
    ->select('reservations.*', 'rooms.name as room_name')
    ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
    ->where('reservations.id', 'like', '%' . $query . '%')
    ->orWhere('rooms.name', 'like', '%' . $query . '%')
    ->orWhere('reservations.date', 'like', '%' . $query . '%')
    ->orWhere('reservations.start_time', 'like', '%' . $query . '%')
    ->orWhere('reservations.end_time', 'like', '%' . $query . '%')
    ->orWhere('reservations.num_seats', 'like', '%' . $query . '%')
    ->orWhere('reservations.type', 'like', '%' . $query . '%')
    ->get();

return response()->json([
    ['result' => $results],
]);
});


Route::middleware('auth:sanctum')->get('/authenticated', function () {
    return response()->json(['authenticated' => true]);
});

Route::get('/reservations2/last', function (Request $request) {
    $reservations = DB::table('reservations')
        ->select('reservations.*', 'rooms.name as room_name')
        ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
        ->orderBy('reservations.created_at', 'desc')
        ->take(10)
        ->get();

    return response()->json([
        'data' => $reservations,
    ]);
});