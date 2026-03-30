<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => true,
            'message' => 'Users fetched successfully',
            'data' => User::all()
        ]);
    }

    public function live()
    {

        echo json_encode([
            'isOk' => true,
            'status' => 200,
            'message' => 'Live users fetched successfully'
        ]);
    }

    public function addUser(Request $request)
    {
        Log::info('Incoming request', $request->all());

        $requestData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
        ]);

        $name = $requestData['name'];
        $email = $requestData['email'];
        $password = bcrypt($requestData['password']);

        DB::insert("
            INSERT INTO users (name, email, email_verified_at, password, remember_token, created_at, updated_at)
            VALUES (?, ?, NOW(), ?, ?, NOW(), NOW())
        ", 
        [
            $name,
            $email,
            $password,
            null,
        ]);
        Log::info('User added successfully', ['email' => $email]);

        return response()->json([
            'status' => true,
            'message' => 'User added successfully'
        ]);
    }
}