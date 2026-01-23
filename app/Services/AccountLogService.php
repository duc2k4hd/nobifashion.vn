<?php

namespace App\Services;

use App\Models\AccountLog;
use Illuminate\Support\Facades\Auth;

class AccountLogService
{
    public function record(
        string $type,
        int $accountId,
        ?int $adminId = null,
        array $changes = [],
        array $meta = [],
        bool $autoDetectAdmin = true
    ): AccountLog {
        $payload = [
            'changes' => $changes,
            'meta' => $meta,
        ];

        $resolvedAdminId = $adminId;
        if ($autoDetectAdmin && $resolvedAdminId === null && Auth::check()) {
            $resolvedAdminId = Auth::id();
        }

        return AccountLog::create([
            'type' => $type,
            'account_id' => $accountId,
            'admin_id' => $resolvedAdminId,
            'payload' => $payload,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}


