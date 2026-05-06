<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Global CORS for all routes (ERP calls web + api from various static hosts).
 * Laravel's HandleCors only runs for paths listed in config/cors.php; this
 * middleware avoids missing routes (questions, tests, courses, etc.).
 */
class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $allowedHeaders = $request->header(
            'Access-Control-Request-Headers',
            'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, X-Chat-Token, Accept, Origin'
        );
        $origin = $request->header('Origin');
        $headers = [
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => $allowedHeaders,
            'Access-Control-Max-Age' => '86400',
        ];
        if ($origin !== null && $origin !== '')
        {
            $headers['Access-Control-Allow-Origin'] = $origin;
            $headers['Access-Control-Allow-Credentials'] = 'true';
            $headers['Vary'] = 'Origin';
        }
        else
        {
            $headers['Access-Control-Allow-Origin'] = '*';
        }

        if ($request->isMethod('OPTIONS'))
        {
            return response()->json([], 204, $headers);
        }

        $response = $next($request);

        foreach ($headers as $key => $value)
        {
            $response->header($key, $value);
        }

        return $response;
    }
}
