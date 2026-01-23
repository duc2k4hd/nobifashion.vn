<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterWelcomeMail extends Mailable
{
    public $email;

    public function __construct($email)
    {
        $this->email = $email;
    }

    public function build()
    {
        return $this->subject('Chào mừng bạn đến với NOBI FASHION!')
                    ->view('emails.newsletter_welcome')   // dùng view, không dùng markdown
                    ->with([
                        'email' => $this->email,
                    ]);
    }
}
