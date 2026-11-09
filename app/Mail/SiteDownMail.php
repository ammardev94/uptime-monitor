<?php

namespace App\Mail;

use App\Models\Site;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Sent to the Admin who added a site when the site (its landing page) goes down. */
class SiteDownMail extends Mailable
{
    public function __construct(public Site $site, public User $owner, public ?string $reason) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "🔴 DOWN: {$this->site->name} ({$this->site->host()}) is not loading");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.site-down');
    }
}
