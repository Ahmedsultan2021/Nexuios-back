<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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



// GET|HEAD        / ..............................................................................................................  
// POST            _ignition/execute-solution ....... ignition.executeSolution › Spatie\LaravelIgnition › ExecuteSolutionController  
// GET|HEAD        _ignition/health-check ................... ignition.healthCheck › Spatie\LaravelIgnition › HealthCheckController  
// POST            _ignition/update-config ................ ignition.updateConfig › Spatie\LaravelIgnition › UpdateConfigController  
// GET|HEAD        api/authenticated ..............................................................................................  
// DELETE          api/deleteUser/{id} ...................................................................... UserController@delete  
// POST            api/login ....................................................................... Auth\LoginController@loginUser  
// POST            api/logout .....................................................................................................  
// POST            api/register .................................................................. Auth\RegisterController@register  
// GET|HEAD        api/reservations .............................................. reservations.index › ReservationController@index  
// POST            api/reservations .............................................. reservations.store › ReservationController@store  
// GET|HEAD        api/reservations/{reservation} .................................. reservations.show › ReservationController@show  
// PUT|PATCH       api/reservations/{reservation} .............................. reservations.update › ReservationController@update  
// DELETE          api/reservations/{reservation} ............................ reservations.destroy › ReservationController@destroy  
// GET|HEAD        api/reservations2/last .........................................................................................  
// GET|HEAD        api/reservations2/search .......................................................................................  
// GET|HEAD        api/room ..................................................................... room.index › RoomController@index  
// POST            api/room ..................................................................... room.store › RoomController@store  
// GET|HEAD        api/room/{room} ................................................................ room.show › RoomController@show  
// PUT|PATCH       api/room/{room} ............................................................ room.update › RoomController@update  
// DELETE          api/room/{room} .......................................................... room.destroy › RoomController@destroy  
// GET|HEAD        api/user .......................................................................................................  
// GET|HEAD        api/users ................................................................................. UserController@index  
// GET|HEAD        sanctum/csrf-cookie .......................... sanctum.csrf-cookie › Laravel\Sanctum › CsrfCookieController@show  