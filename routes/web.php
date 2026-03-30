<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;

Route::get('/users/live', [UsersController::class, 'live']);
Route::post('/users/addUser', [UsersController::class, 'addUser']);

// REMOVE show route (important)
Route::resource('users', UsersController::class)->except(['show']);

Route::get('/', function () {
    return "Welcome to OneHour NTPC API 🚀";
});