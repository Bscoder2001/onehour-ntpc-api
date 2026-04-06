<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google's OAuth consent screen.
     */
    public function redirect(): RedirectResponse|SymfonyRedirectResponse
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Handle the OAuth callback from Google.
     */
    public function callback(): JsonResponse|RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();
        } catch (Throwable $e) {
            Log::warning('Google OAuth callback failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Google authentication failed.',
                'error' => config('app.debug') ? $e->getMessage() : 'Invalid or expired OAuth response.',
            ], 422);
        }

        $frontend = env('FRONTEND_URL');
        if (is_string($frontend) && $frontend !== '') {
            $url = rtrim($frontend, '/').'/admin.html?google_login=1';

            return redirect()->away($url);
        }

        return response()->json([
            'message' => 'Google login successful',
            'user' => [
                'id' => $googleUser->getId(),
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar(),
            ],
        ]);
    }
}
