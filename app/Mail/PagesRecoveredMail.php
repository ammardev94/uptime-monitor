<?php

namespace App\Mail;

use App\Models\Site;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PagesRecoveredMail extends Mailable
{
    public function __construct(public Site $site, public array $items) {}

    public function envelope(): Envelope
    {
        $n = count($this->items);
        $what = $n === 1 ? $this->items[0]['page']->path() : "$n pages";

        return new Envelope(subject: "🟢 UP again: {$this->site->name} — {$what}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.recovered');
    }
}
