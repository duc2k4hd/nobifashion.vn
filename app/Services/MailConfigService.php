<?php

namespace App\Services;

use App\Helpers\EmailHelper;
use App\Models\EmailAccount;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class MailConfigService
{
    /**
     * Cấu hình mail để sử dụng SMTP của email account cụ thể
     * 
     * @param int|null $emailAccountId ID của email account (null = dùng mặc định)
     * @return void
     */
    public static function configureMailForAccount(?int $emailAccountId = null): void
    {
        $emailAccount = EmailHelper::getEmailAccount($emailAccountId);
        
        if (!$emailAccount) {
            return; // Dùng cấu hình mặc định từ .env
        }

        $smtpConfig = $emailAccount->getSmtpConfig();

        // Cập nhật config mail động
        Config::set('mail.mailers.smtp.host', $smtpConfig['host']);
        Config::set('mail.mailers.smtp.port', $smtpConfig['port']);
        Config::set('mail.mailers.smtp.username', $smtpConfig['username']);
        Config::set('mail.mailers.smtp.password', $smtpConfig['password']);
        Config::set('mail.mailers.smtp.encryption', $smtpConfig['encryption']);
        
        // Cập nhật from address và name
        Config::set('mail.from.address', $emailAccount->email);
        Config::set('mail.from.name', $emailAccount->name);
    }

    /**
     * Gửi mail với cấu hình từ email account cụ thể
     * 
     * @param int|null $emailAccountId ID của email account
     * @param callable $callback Callback function nhận Mail instance
     * @return mixed
     */
    public static function sendWithAccount(?int $emailAccountId, callable $callback)
    {
        // Lưu config cũ
        $oldConfig = [
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username'),
            'password' => config('mail.mailers.smtp.password'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];

        try {
            // Cấu hình mail cho account
            self::configureMailForAccount($emailAccountId);

            // Clear mailer cache để đảm bảo mailer được tạo lại với config mới
            if (App::bound('mail.manager')) {
                App::forgetInstance('mail.manager');
            }

            // Gửi mail
            return $callback();
        } finally {
            // Khôi phục config cũ
            Config::set('mail.mailers.smtp.host', $oldConfig['host']);
            Config::set('mail.mailers.smtp.port', $oldConfig['port']);
            Config::set('mail.mailers.smtp.username', $oldConfig['username']);
            Config::set('mail.mailers.smtp.password', $oldConfig['password']);
            Config::set('mail.mailers.smtp.encryption', $oldConfig['encryption']);
            Config::set('mail.from.address', $oldConfig['from_address']);
            Config::set('mail.from.name', $oldConfig['from_name']);
        }
    }
}

