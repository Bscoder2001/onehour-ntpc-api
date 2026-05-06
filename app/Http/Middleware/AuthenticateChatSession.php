<?php

namespace App\Http\Middleware;

use App\Services\ChatTokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateChatSession
{
    public function __construct(
        private ChatTokenService $chatTokens
    )
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check())
        {
            return $next($request);
        }

        $plain = $request->bearerToken() ?? $request->header('X-Chat-Token');
        $userId = $this->chatTokens->resolveUserId($plain);

        if ($userId === null)
        {
            return response()->json([
                'isOk' => false,
                'status' => 401,
                'message' => 'Unauthenticated',
                'data' => [],
            ], 401);
        }

        Auth::onceUsingId($userId);

        return $next($request);
    }
}
