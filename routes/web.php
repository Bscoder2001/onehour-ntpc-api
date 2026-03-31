<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;

Route::get('/users/live', [UsersController::class, 'live']);
Route::post('/users/login', [UsersController::class, 'login']);
Route::post('/users/addUser', [UsersController::class, 'addUser']);
Route::post('/users/sendOtp', [UsersController::class, 'sendOtp']);
Route::post('/users/verifyOtp', [UsersController::class, 'verifyOtp']);
Route::post('/users/resetPassword', [UsersController::class, 'resetPassword']);
Route::get('/users/getTeachers', [UsersController::class, 'getTeachers']);
Route::post('/users/updateTeacher', [UsersController::class, 'updateTeacher']);
Route::post('/users/deleteTeacher', [UsersController::class, 'deleteTeacher']);

// REMOVE show route (important)
Route::resource('users', UsersController::class)->except(['show']);

Route::get('/', function () {
    return "Welcome to OneHour NTPC API 🚀";
});