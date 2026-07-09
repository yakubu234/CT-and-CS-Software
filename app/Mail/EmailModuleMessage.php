<?php

namespace App\Mail;

use App\Models\EmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailModuleMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected EmailMessage $message,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject($this->message->subject)
            ->html($this->message->body);
    }
}
