<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $userId = (int) ($request->header('X-User-Id') ?: $request->input('user_id'));

        if ($userId <= 0)
        {
            return response()->json(['isOk' => false, 'status' => 401, 'message' => 'Unauthorized user', 'data' => []], 401);
        }

        $user = DB::table('users')->select('id', 'user_type_id')->where('id', $userId)->first();

        if (!$user)
        {
            return response()->json(['isOk' => false, 'status' => 401, 'message' => 'Unauthorized user', 'data' => []], 401);
        }

        $request->attributes->set('assessment_user_id', (int) $user->id);
        $request->attributes->set('assessment_user_type_id', (int) $user->user_type_id);

        return $next($request);
    }
}
