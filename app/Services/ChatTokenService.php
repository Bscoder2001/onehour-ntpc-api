<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChatTokenService
{
    public function issue(int $userId): string
    {
        $plain = Str::random(64);
        $hash = hash('sha256', $plain);
        $ttlDays = (int) config('chat.token_ttl_days', 14);
        Cache::put($this->cacheKey($hash), $userId, now()->addDays($ttlDays));

        return $plain;
    }

    public function resolveUserId(?string $plainToken): ?int
    {
        if ($plainToken === null || $plainToken === '')
        {
            return null;
        }

        $hash = hash('sha256', $plainToken);
        $userId = Cache::get($this->cacheKey($hash));

        return $userId !== null ? (int) $userId : null;
    }

    private function cacheKey(string $tokenHash): string
    {
        return 'chat-token:'.$tokenHash;
    }
}
