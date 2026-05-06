<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ChatPresenceService
{
    public function touch(int $userId): void
    {
        $ttl = (int) config('chat.presence_ttl_seconds', 120);
        Cache::put($this->key($userId), true, now()->addSeconds($ttl));
    }

    public function isOnline(int $userId): bool
    {
        return Cache::has($this->key($userId));
    }

    private function key(int $userId): string
    {
        return 'chat:online:'.$userId;
    }
}
