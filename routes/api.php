<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ChatController;
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

Route::prefix('chat')->middleware(['chat.auth'])->group(function ()
{
    Route::get('/bootstrap', [ChatController::class, 'bootstrap']);
    Route::post('/presence', [ChatController::class, 'presence']);
    Route::get('/users', [ChatController::class, 'users']);
    Route::get('/conversation', [ChatController::class, 'conversation']);
    Route::post('/sendMessage', [ChatController::class, 'sendMessage']);
    Route::post('/markRead', [ChatController::class, 'markRead']);
    Route::post('/typing', [ChatController::class, 'typing']);
    Route::post('/ackDelivered', [ChatController::class, 'acknowledgeDelivered']);
    Route::post('/deleteMessageForEveryone', [ChatController::class, 'deleteMessageForEveryone']);
});
