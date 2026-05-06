<?php

return [

    'student_user_type_id' => (int) env('CHAT_STUDENT_USER_TYPE_ID', 4),

    /*
     * Institute / school admin accounts (e.g. user_type_id = 2 from UsersController addUser).
     * Comma-separated in .env: CHAT_ADMIN_USER_TYPE_IDS=2
     */
    'admin_user_type_ids' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('CHAT_ADMIN_USER_TYPE_IDS', '2'))
    ), static function (int $id): bool
    {
        return $id > 0;
    })) ?: [2],

    /*
     * Teacher accounts (matches ntpc-constants USER_TYPE_ID_TEACHER = 3).
     * Comma-separated: CHAT_TEACHER_USER_TYPE_IDS=3
     */
    'teacher_user_type_ids' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('CHAT_TEACHER_USER_TYPE_IDS', '3'))
    ), static function (int $id): bool
    {
        return $id > 0;
    })) ?: [3],

    'presence_ttl_seconds' => (int) env('CHAT_PRESENCE_TTL', 120),

    'token_ttl_days' => (int) env('CHAT_TOKEN_TTL_DAYS', 14),

    'preview_max_chars' => (int) env('CHAT_PREVIEW_MAX_CHARS', 72),

    'composer_max_chars' => (int) env('CHAT_COMPOSER_MAX_CHARS', 10000),

    /*
     * 0 = no time limit. Otherwise only the original sender may delete within this many minutes.
     */
    'delete_for_everyone_within_minutes' => (int) env('CHAT_DELETE_FOR_EVERYONE_WITHIN_MINUTES', 0),

];
