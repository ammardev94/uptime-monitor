<?php

namespace App\Mail;

use App\Models\Site;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SslExpiringMail extends Mailable
{
    public function __construct(public Site $site, public int $days) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "⚠️ SSL certificate for {$this->site->host()} ".($this->days < 0 ? 'has EXPIRED' : "expires in {$this->days} days"));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.ssl');
    }
}
