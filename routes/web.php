<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\V2UsersController;

Route::get('/users/live', [UsersController::class, 'live']);
Route::post('/users/login', [UsersController::class, 'login']);
Route::post('/users/addUser', [UsersController::class, 'addUser']);
Route::post('/users/addAcademicYear', [UsersController::class, 'addAcademicYear']);
Route::post('/users/listAcademicYears', [UsersController::class, 'listAcademicYears']);
Route::post('/users/deleteAcademicYear', [UsersController::class, 'deleteAcademicYear']);
Route::post('/users/sendOtp', [UsersController::class, 'sendOtp']);
Route::post('/users/verifyOtp', [UsersController::class, 'verifyOtp']);
Route::post('/users/resetPassword', [UsersController::class, 'resetPassword']);
Route::post('/v2users/getMembers', [V2UsersController::class, 'getMembers']);
Route::post('/v2users/updateMember', [V2UsersController::class, 'updateMember']);
Route::post('/v2users/deleteMember', [V2UsersController::class, 'deleteMember']);
Route::post('/v2users/addMember', [V2UsersController::class, 'addMember']);

// REMOVE show route (important)
Route::resource('users', UsersController::class)->except(['show']);

Route::get('/', function () {
    return "Welcome to OneHour NTPC API 🚀";
});