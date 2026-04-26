<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\V2UsersController;
use App\Http\Controllers\Assessment\QuestionsController;
use App\Http\Controllers\Assessment\TestsController;
use App\Http\Controllers\Assessment\AttemptsController;
use App\Http\Controllers\Assessment\ResultsController;
use App\Http\Controllers\Assessment\CoursesController;
use App\Http\Controllers\Assessment\TaxonomyController;

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

Route::middleware(['assessment.auth'])->group(function ()
{
    Route::post('/questions', [QuestionsController::class, 'store']);
    Route::get('/questions', [QuestionsController::class, 'index']);
    Route::get('/questions/{id}', [QuestionsController::class, 'show']);
    Route::put('/questions/{id}', [QuestionsController::class, 'update']);
    Route::delete('/questions/{id}', [QuestionsController::class, 'destroy']);

    Route::post('/tests', [TestsController::class, 'store']);
    Route::get('/tests', [TestsController::class, 'index']);
    Route::get('/tests/{id}', [TestsController::class, 'show']);
    Route::post('/tests/{id}/questions', [TestsController::class, 'attachQuestions']);
    Route::delete('/tests/{id}/questions/{question_id}', [TestsController::class, 'removeQuestion']);

    Route::post('/tests/{id}/start', [AttemptsController::class, 'start']);
    Route::get('/attempts/{id}', [AttemptsController::class, 'show']);
    Route::post('/attempts/{id}/answer', [AttemptsController::class, 'answer']);
    Route::post('/attempts/{id}/submit', [AttemptsController::class, 'submit']);

    Route::get('/results/{attempt_id}', [ResultsController::class, 'show']);
    Route::get('/results/user/{user_id}', [ResultsController::class, 'byUser']);

    Route::post('/courses', [CoursesController::class, 'store']);
    Route::post('/courses/list', [CoursesController::class, 'index']);
    Route::get('/courses', [CoursesController::class, 'index']);
    Route::put('/courses/{id}', [CoursesController::class, 'update']);
    Route::delete('/courses/{id}', [CoursesController::class, 'destroy']);

    Route::get('/subjects', [TaxonomyController::class, 'subjects']);
    Route::post('/subjects', [TaxonomyController::class, 'storeSubject']);
    Route::get('/chapters', [TaxonomyController::class, 'chapters']);
    Route::post('/chapters', [TaxonomyController::class, 'storeChapter']);
    Route::get('/topics', [TaxonomyController::class, 'topics']);
    Route::post('/topics', [TaxonomyController::class, 'storeTopic']);
});

// REMOVE show route (important)
Route::resource('users', UsersController::class)->except(['show']);

Route::get('/', function () {
    return "Welcome to OneHour NTPC API 🚀";
});