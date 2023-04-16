<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;


class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
    
        return response()->json([
            'data' => $users
        ]);
    }
    public function delete($id){
        $user = User::find($id);
        $user->delete();
        return response()->json([
            'message' => 'user deleted successfully',
        ]);
    }
}
