<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Models\NtpcForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::post('/submit-form', function (Request $request) {
    $data = NtpcForm::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Form submitted successfully',
        'data' => $data,
    ]);
});
