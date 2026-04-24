<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->append(\App\Http\Middleware\CorsMiddleware::class);
        $middleware->alias([
            'assessment.auth' => \App\Http\Middleware\AssessmentAuthMiddleware::class,
        ]);
    
        $middleware->validateCsrfTokens(except: [
            'users/login',
            'users/addUser',
            'users/addAcademicYear',
            'users/listAcademicYears',
            'users/deleteAcademicYear',
            'users/sendOtp',
            'users/verifyOtp',
            'users/resetPassword',
            'v2users/getMembers',
            'v2users/updateMember',
            'v2users/deleteMember',
            'v2users/addMember',
            'questions',
            'questions/*',
            'tests',
            'tests/*',
            'attempts/*',
            'results/*',
            'courses',
            'courses/*',
            'subjects',
            'chapters',
            'topics',
        ]);
    
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();