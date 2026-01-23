<?php

namespace App\Helpers;

use App\Models\EmailAccount;

class EmailHelper
{
    /**
     * Lấy email mặc định hoặc email theo ID
     * 
     * @param int|null $emailAccountId ID của email account (null = lấy mặc định)
     * @return string|null
     */
    public static function getFromEmail(?int $emailAccountId = null): ?string
    {
        if ($emailAccountId) {
            $emailAccount = EmailAccount::active()->find($emailAccountId);
            return $emailAccount?->email;
        }

        $default = EmailAccount::getDefault();
        return $default?->email ?? config('mail.from.address');
    }

    /**
     * Lấy tên hiển thị của email
     * 
     * @param int|null $emailAccountId ID của email account (null = lấy mặc định)
     * @return string|null
     */
    public static function getFromName(?int $emailAccountId = null): ?string
    {
        if ($emailAccountId) {
            $emailAccount = EmailAccount::active()->find($emailAccountId);
            return $emailAccount?->name ?? config('mail.from.name');
        }

        $default = EmailAccount::getDefault();
        return $default?->name ?? config('mail.from.name');
    }

    /**
     * Lấy email account object
     * 
     * @param int|null $emailAccountId ID của email account (null = lấy mặc định)
     * @return EmailAccount|null
     */
    public static function getEmailAccount(?int $emailAccountId = null): ?EmailAccount
    {
        if ($emailAccountId) {
            return EmailAccount::active()->find($emailAccountId);
        }

        return EmailAccount::getDefault();
    }

    /**
     * Lấy danh sách email đang hoạt động để chọn
     * 
     * @return array [id => email (name)]
     */
    public static function getEmailOptions(): array
    {
        return EmailAccount::getActiveEmails()
            ->mapWithKeys(function ($email) {
                return [$email->id => "{$email->email} ({$email->name})"];
            })
            ->toArray();
    }
}

