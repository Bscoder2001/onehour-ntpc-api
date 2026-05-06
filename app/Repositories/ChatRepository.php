<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class ChatRepository
{
    public function insertMessage(int $senderId, int $receiverId, string $messageText): object
    {
        DB::insert(
            'INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)',
            [$senderId, $receiverId, $messageText]
        );

        $id = (int) DB::getPdo()->lastInsertId();

        return (object) [
            'id' => $id,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $messageText,
        ];
    }

    /**
     * @return list<object{id:int,sender_id:int,receiver_id:int,message:string}>
     */
    public function findConversation(int $userId, int $peerId, int $limit = 500): array
    {
        $limit = max(1, min(500, $limit));
        $rows = DB::select(
            'SELECT id, sender_id, receiver_id, message, deleted_for_everyone_at
             FROM messages
             WHERE (sender_id = ? AND receiver_id = ?)
                OR (sender_id = ? AND receiver_id = ?)
             ORDER BY id ASC
             LIMIT '.$limit,
            [$userId, $peerId, $peerId, $userId]
        );

        $out = [];
        foreach ($rows as $row)
        {
            $out[] = (object) [
                'id' => (int) $row->id,
                'sender_id' => (int) $row->sender_id,
                'receiver_id' => (int) $row->receiver_id,
                'message' => $row->message,
                'deleted_for_everyone_at' => $row->deleted_for_everyone_at ?? null,
            ];
        }

        return $out;
    }

    public function maxMessageIdInConversation(int $userId, int $peerId): int
    {
        $row = DB::selectOne(
            'SELECT MAX(id) AS m FROM messages
             WHERE (sender_id = ? AND receiver_id = ?)
                OR (sender_id = ? AND receiver_id = ?)',
            [$userId, $peerId, $peerId, $userId]
        );

        return $row && $row->m !== null ? (int) $row->m : 0;
    }

    public function markConversationRead(int $userId, int $peerId, int $upToMessageId): void
    {
        if ($upToMessageId <= 0)
        {
            return;
        }

        $exists = DB::selectOne(
            'SELECT id FROM messages WHERE id = ?
             AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
             LIMIT 1',
            [$upToMessageId, $userId, $peerId, $peerId, $userId]
        );

        if ($exists === null)
        {
            return;
        }

        $row = DB::table('chat_conversation_reads')
            ->where('user_id', $userId)
            ->where('peer_id', $peerId)
            ->first();

        $next = $upToMessageId;
        if ($row !== null)
        {
            $next = max((int) $row->last_read_message_id, $upToMessageId);
        }

        if ($row === null)
        {
            DB::table('chat_conversation_reads')->insert([
                'user_id' => $userId,
                'peer_id' => $peerId,
                'last_read_message_id' => $next,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        else
        {
            DB::table('chat_conversation_reads')
                ->where('user_id', $userId)
                ->where('peer_id', $peerId)
                ->update([
                    'last_read_message_id' => $next,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * @param  list<int>  $messageIds
     * @return list<object{id:int,sender_id:int,receiver_id:int}>
     */
    public function messagesInConversation(array $messageIds, int $authId, int $peerId): array
    {
        $messageIds = array_values(array_filter(array_map('intval', $messageIds)));
        if ($messageIds === [])
        {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
        $bindings = array_merge(
            $messageIds,
            [$authId, $peerId, $peerId, $authId]
        );

        return DB::select(
            'SELECT id, sender_id, receiver_id FROM messages
             WHERE id IN ('.$placeholders.')
               AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))',
            $bindings
        );
    }

    /**
     * @param  list<int>  $peerIds
     * @return array<int, object{id:int,sender_id:int,receiver_id:int,message:string}>
     */
    public function lastMessagePerPeer(int $authId, array $peerIds): array
    {
        $peerIds = array_values(array_unique(array_filter(array_map('intval', $peerIds))));
        if ($peerIds === [])
        {
            return [];
        }

        $ph = implode(',', array_fill(0, count($peerIds), '?'));
        /*
         * Latest message id per peer without CASE...GROUP BY (MySQL ONLY_FULL_GROUP_BY rejects that).
         * Branch A: auth → peer (group by receiver_id). Branch B: peer → auth (group by sender_id).
         * UNION ALL then MAX(mid) per peer_id picks the true latest across both directions.
         */
        $bindings = array_merge(
            [$authId],
            $peerIds,
            [$authId],
            $peerIds
        );

        $rows = DB::select(
            'SELECT m.id, m.sender_id, m.receiver_id,
                    CASE WHEN m.deleted_for_everyone_at IS NOT NULL THEN \'This message was deleted.\' ELSE m.message END AS message
             FROM messages m
             INNER JOIN (
                 SELECT u.peer_id, MAX(u.mid) AS mid
                 FROM (
                     SELECT receiver_id AS peer_id, MAX(id) AS mid
                     FROM messages
                     WHERE sender_id = ? AND receiver_id IN ('.$ph.')
                     GROUP BY receiver_id
                     UNION ALL
                     SELECT sender_id AS peer_id, MAX(id) AS mid
                     FROM messages
                     WHERE receiver_id = ? AND sender_id IN ('.$ph.')
                     GROUP BY sender_id
                 ) u
                 GROUP BY u.peer_id
             ) t ON m.id = t.mid',
            $bindings
        );

        $map = [];
        foreach ($rows as $row)
        {
            $peer = (int) $row->sender_id === $authId ? (int) $row->receiver_id : (int) $row->sender_id;
            $map[$peer] = $row;
        }

        return $map;
    }

    public function findMessageById(int $messageId): ?object
    {
        return DB::selectOne(
            'SELECT id, sender_id, receiver_id, deleted_for_everyone_at
             FROM messages WHERE id = ? LIMIT 1',
            [$messageId]
        );
    }

    public function markMessageDeletedForEveryone(int $messageId): bool
    {
        $updated = DB::update(
            'UPDATE messages SET deleted_for_everyone_at = NOW() WHERE id = ? AND deleted_for_everyone_at IS NULL',
            [$messageId]
        );

        return $updated > 0;
    }

    /**
     * @param  list<int>  $senderPeerIds  peers who may have sent unread messages to $receiverId
     * @return array<int, int> peer_id => count
     */
    public function unreadCountsForReceiver(int $receiverId, array $senderPeerIds): array
    {
        $senderPeerIds = array_values(array_unique(array_filter(array_map('intval', $senderPeerIds))));
        if ($senderPeerIds === [])
        {
            return [];
        }

        $ph = implode(',', array_fill(0, count($senderPeerIds), '?'));
        $bindings = array_merge([$receiverId, $receiverId], $senderPeerIds);

        $rows = DB::select(
            'SELECT m.sender_id AS peer_id, COUNT(*) AS cnt
             FROM messages m
             LEFT JOIN chat_conversation_reads cr
               ON cr.user_id = ? AND cr.peer_id = m.sender_id
             WHERE m.receiver_id = ?
               AND m.sender_id IN ('.$ph.')
               AND m.id > COALESCE(cr.last_read_message_id, 0)
             GROUP BY m.sender_id',
            $bindings
        );

        $map = [];
        foreach ($rows as $row)
        {
            $map[(int) $row->peer_id] = (int) $row->cnt;
        }

        return $map;
    }

    public function findActiveUserInInstitute(int $userId, int $instituteId): ?object
    {
        $row = DB::selectOne(
            'SELECT id, institute_id, status, user_type_id, name, user_name, email
             FROM users
             WHERE id = ?
               AND institute_id = ?
               AND status = ?
             LIMIT 1',
            [$userId, $instituteId, 'active']
        );

        return $row;
    }

    public function userExistsActive(int $userId): bool
    {
        $row = DB::selectOne(
            'SELECT id FROM users WHERE id = ? AND status = ? LIMIT 1',
            [$userId, 'active']
        );

        return $row !== null;
    }

    /**
     * @param  list<int>  $userTypeIds  allowed peer user_type_id values (e.g. students only, or students + admins)
     * @return list<object{id:int,name:mixed,user_name:mixed,email:mixed,user_type_id:int}>
     */
    public function listChatPeersInInstitute(int $instituteId, int $exceptUserId, array $userTypeIds): array
    {
        $userTypeIds = array_values(array_unique(array_filter(array_map('intval', $userTypeIds))));
        if ($userTypeIds === [])
        {
            return [];
        }

        $ph = implode(',', array_fill(0, count($userTypeIds), '?'));

        return DB::select(
            'SELECT id, name, user_name, email, user_type_id
             FROM users
             WHERE institute_id = ?
               AND status = ?
               AND id != ?
               AND user_type_id IN ('.$ph.')
             ORDER BY name ASC',
            array_merge([$instituteId, 'active', $exceptUserId], $userTypeIds)
        );
    }

    public function getUserInstituteId(int $userId): ?int
    {
        $row = DB::selectOne(
            'SELECT institute_id FROM users WHERE id = ? LIMIT 1',
            [$userId]
        );

        if ($row === null || $row->institute_id === null)
        {
            return null;
        }

        return (int) $row->institute_id;
    }
}
