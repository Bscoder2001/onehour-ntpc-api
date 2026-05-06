<?php

namespace App\Services;

use App\Events\ChatConversationRead;
use App\Events\ChatMessagesDelivered;
use App\Events\ChatUserTyping;
use App\Events\MessageDeletedForEveryone;
use App\Events\MessageSent;
use App\Repositories\ChatRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ChatService
{
    public function __construct(
        private ChatRepository $repository,
        private ChatPresenceService $presence
    )
    {
    }

    public function touchPresence(int $userId): void
    {
        $this->presence->touch($userId);
    }

    public function bootstrapMeta(): array
    {
        $user = Auth::user();

        return [
            'id' => (int) $user->id,
            'name' => $user->name,
            'display_name' => $this->chatDisplayNameForUser($user),
            'email' => $user->email ?? '',
            'user_name' => $user->user_name ?? '',
            'institute_id' => $user->institute_id !== null ? (int) $user->institute_id : null,
            'user_type_id' => $user->user_type_id !== null ? (int) $user->user_type_id : null,
            'role_label' => $this->chatRoleLabelForUser($user),
        ];
    }

    /**
     * One rule for every account type: prefer full name, then login/username, then email.
     */
    private function chatDisplayNameForUser(object $user): string
    {
        $name = trim((string) ($user->name ?? ''));
        if ($name !== '')
        {
            return $name;
        }

        $userName = trim((string) ($user->user_name ?? ''));
        if ($userName !== '')
        {
            return $userName;
        }

        $email = trim((string) ($user->email ?? ''));
        if ($email !== '' && str_contains($email, '@'))
        {
            return (string) strstr($email, '@', true);
        }

        if ($email !== '')
        {
            return $email;
        }

        return 'User';
    }

    private function chatRoleLabelForUser(object $user): string
    {
        $adminTypeIds = config('chat.admin_user_type_ids', [2]);
        if (! is_array($adminTypeIds))
        {
            $adminTypeIds = [2];
        }

        $teacherTypeIds = config('chat.teacher_user_type_ids', [3]);
        if (! is_array($teacherTypeIds))
        {
            $teacherTypeIds = [3];
        }

        $studentTypeId = (int) config('chat.student_user_type_id', 4);
        $tid = (int) ($user->user_type_id ?? 0);

        if (in_array($tid, $adminTypeIds, true))
        {
            return 'Institution admin';
        }

        if (in_array($tid, $teacherTypeIds, true))
        {
            return 'Teacher';
        }

        if ($tid === $studentTypeId)
        {
            return 'Student';
        }

        return 'Member';
    }

    /**
     * All user types that may appear in the chat directory and receive DMs within an institute.
     * Same list for every logged-in role (admin, teacher, student).
     *
     * @return list<int>
     */
    private function chatParticipantUserTypeIds(): array
    {
        $admins = config('chat.admin_user_type_ids', [2]);
        if (! is_array($admins))
        {
            $admins = [2];
        }

        $teachers = config('chat.teacher_user_type_ids', [3]);
        if (! is_array($teachers))
        {
            $teachers = [3];
        }

        $studentId = (int) config('chat.student_user_type_id', 4);

        $merged = array_merge(
            array_map('intval', $admins),
            array_map('intval', $teachers),
            [$studentId]
        );

        return array_values(array_unique(array_filter($merged, static function (int $id): bool
        {
            return $id > 0;
        })));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listDirectoryForAuthUser(): array
    {
        $authId = (int) Auth::id();
        $instituteId = $this->repository->getUserInstituteId($authId);

        if ($instituteId === null)
        {
            return [];
        }

        $peerTypeFilter = $this->chatParticipantUserTypeIds();

        $rows = $this->repository->listChatPeersInInstitute($instituteId, $authId, $peerTypeFilter);
        $peerIds = [];
        foreach ($rows as $row)
        {
            $peerIds[] = (int) $row->id;
        }

        $lastByPeer = $this->repository->lastMessagePerPeer($authId, $peerIds);
        $unreadMap = $this->repository->unreadCountsForReceiver($authId, $peerIds);

        $previewLen = (int) config('chat.preview_max_chars', 72);
        $out = [];
        foreach ($rows as $row)
        {
            $uid = (int) $row->id;
            $lastId = 0;
            $preview = '';
            $lastSenderId = null;
            if (isset($lastByPeer[$uid]))
            {
                $lm = $lastByPeer[$uid];
                $lastId = (int) $lm->id;
                $raw = (string) $lm->message;
                $preview = mb_strlen($raw) > $previewLen
                    ? mb_substr($raw, 0, $previewLen).'…'
                    : $raw;
                $lastSenderId = (int) $lm->sender_id;
            }

            $out[] = [
                'id' => $uid,
                'name' => (string) $row->name,
                'email' => (string) ($row->email ?? ''),
                'user_name' => (string) ($row->user_name ?? ''),
                'online' => $this->presence->isOnline($uid),
                'last_message_id' => $lastId,
                'last_message_preview' => $preview,
                'last_message_from_self' => $lastSenderId === $authId,
                'unread_count' => (int) ($unreadMap[$uid] ?? 0),
            ];
        }

        usort($out, function (array $a, array $b): int
        {
            return ($b['last_message_id'] ?? 0) <=> ($a['last_message_id'] ?? 0);
        });

        return $out;
    }

    /**
     * @return list<array{id:int,sender_id:int,receiver_id:int,message:string,deleted_for_everyone:bool}>
     */
    public function conversationForAuthUser(int $peerId): array
    {
        $authId = (int) Auth::id();
        $this->assertPeerAllowed($authId, $peerId);

        $rows = $this->repository->findConversation($authId, $peerId);
        $messages = [];
        foreach ($rows as $row)
        {
            $deleted = $row->deleted_for_everyone_at !== null && $row->deleted_for_everyone_at !== '';
            $messages[] = [
                'id' => $row->id,
                'sender_id' => $row->sender_id,
                'receiver_id' => $row->receiver_id,
                'message' => $deleted ? 'This message was deleted.' : (string) $row->message,
                'deleted_for_everyone' => $deleted,
            ];
        }

        return $messages;
    }

    public function deleteMessageForEveryoneForAuthUser(int $messageId): void
    {
        $authId = (int) Auth::id();
        $row = $this->repository->findMessageById($messageId);
        if ($row === null)
        {
            throw ValidationException::withMessages([
                'message_id' => ['Message not found.'],
            ]);
        }

        $senderId = (int) $row->sender_id;
        $receiverId = (int) $row->receiver_id;
        if ($senderId !== $authId)
        {
            throw ValidationException::withMessages([
                'message_id' => ['Only the sender can delete this message for everyone.'],
            ]);
        }

        $this->assertPeerAllowed($authId, $receiverId);

        if ($row->deleted_for_everyone_at !== null && $row->deleted_for_everyone_at !== '')
        {
            throw ValidationException::withMessages([
                'message_id' => ['This message was already deleted.'],
            ]);
        }

        if (! $this->repository->markMessageDeletedForEveryone($messageId))
        {
            throw ValidationException::withMessages([
                'message_id' => ['Could not delete this message.'],
            ]);
        }

        broadcast(new MessageDeletedForEveryone($messageId, $senderId, $receiverId));
    }

    /**
     * @return array{id:int,sender_id:int,receiver_id:int,message:string}
     */
    public function sendForAuthUser(int $receiverId, string $messageText): array
    {
        $senderId = (int) Auth::id();
        $this->assertPeerAllowed($senderId, $receiverId);

        $payload = $this->repository->insertMessage($senderId, $receiverId, $messageText);
        $sender = Auth::user();
        $payload->sender_display_name = $sender !== null
            ? $this->chatDisplayNameForUser($sender)
            : '';
        broadcast(new MessageSent($payload));

        return [
            'id' => $payload->id,
            'sender_id' => $payload->sender_id,
            'receiver_id' => $payload->receiver_id,
            'message' => $payload->message,
        ];
    }

    public function markConversationReadForAuthUser(int $peerId, ?int $upToMessageId): void
    {
        $authId = (int) Auth::id();
        $this->assertPeerAllowed($authId, $peerId);

        $maxId = $upToMessageId ?? $this->repository->maxMessageIdInConversation($authId, $peerId);
        if ($maxId <= 0)
        {
            return;
        }

        $this->repository->markConversationRead($authId, $peerId, $maxId);
        broadcast(new ChatConversationRead($peerId, $authId, $maxId));
    }

    public function broadcastTypingForAuthUser(int $peerId, bool $typing): void
    {
        $authId = (int) Auth::id();
        $this->assertPeerAllowed($authId, $peerId);

        $user = Auth::user();
        $name = (string) ($user->name ?? 'Someone');

        broadcast(new ChatUserTyping($peerId, $authId, $name, $typing));
    }

    /**
     * @param  list<int>  $messageIds
     */
    public function acknowledgeDeliveredForAuthUser(int $peerId, array $messageIds): void
    {
        $authId = (int) Auth::id();
        $this->assertPeerAllowed($authId, $peerId);

        $rows = $this->repository->messagesInConversation($messageIds, $authId, $peerId);
        $bySender = [];
        foreach ($rows as $row)
        {
            if ((int) $row->receiver_id !== $authId)
            {
                continue;
            }

            $sid = (int) $row->sender_id;
            $bySender[$sid][] = (int) $row->id;
        }

        foreach ($bySender as $senderId => $ids)
        {
            if ($senderId === $authId || $ids === [])
            {
                continue;
            }

            broadcast(new ChatMessagesDelivered($senderId, $ids));
        }
    }

    private function assertPeerAllowed(int $senderId, int $receiverId): void
    {
        if ($receiverId === $senderId)
        {
            throw ValidationException::withMessages([
                'receiver_id' => ['You cannot message yourself.'],
            ]);
        }

        if (! $this->repository->userExistsActive($receiverId))
        {
            throw ValidationException::withMessages([
                'receiver_id' => ['Recipient not found.'],
            ]);
        }

        $senderInstitute = $this->repository->getUserInstituteId($senderId);
        if ($senderInstitute === null)
        {
            throw ValidationException::withMessages([
                'receiver_id' => ['Your account is not linked to an institute.'],
            ]);
        }

        $peer = $this->repository->findActiveUserInInstitute($receiverId, $senderInstitute);

        if ($peer === null)
        {
            throw ValidationException::withMessages([
                'receiver_id' => ['Recipient is not available for chat.'],
            ]);
        }

        $peerTypeId = (int) $peer->user_type_id;
        $allowedTypes = $this->chatParticipantUserTypeIds();

        if (! in_array($peerTypeId, $allowedTypes, true))
        {
            throw ValidationException::withMessages([
                'receiver_id' => ['Invalid recipient.'],
            ]);
        }
    }
}
