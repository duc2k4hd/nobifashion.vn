<?php

namespace App\Jobs;

use App\Mail\NewsletterWelcomeMail;
use App\Services\MailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNewsletterWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;

    public function __construct($email)
    {
        $this->email = $email;
    }

    public function handle()
    {
        try {
            $emailAccountId = config('email_defaults.newsletter_welcome');
            MailConfigService::sendWithAccount($emailAccountId, function () {
                Mail::to($this->email)->send(new NewsletterWelcomeMail($this->email));
            });
        } catch (\Throwable $e) {
            Log::error("Queue email failed: " . $e->getMessage());
        }
    }
}
