<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountEmailVerification;
use Illuminate\Support\Str;

class AccountEmailVerificationService
{
    public function createToken(Account $account, int $ttlMinutes = 60 * 24 * 3): string
    {
        $token = Str::random(64);

        AccountEmailVerification::where('account_id', $account->id)->delete();

        AccountEmailVerification::create([
            'account_id' => $account->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        return $token;
    }

    public function findValidToken(string $plainToken): ?AccountEmailVerification
    {
        $hashed = hash('sha256', $plainToken);

        return AccountEmailVerification::where('token', $hashed)
            ->where('expires_at', '>=', now())
            ->first();
    }

    public function consume(AccountEmailVerification $record): void
    {
        AccountEmailVerification::where('account_id', $record->account_id)->delete();
    }
}

