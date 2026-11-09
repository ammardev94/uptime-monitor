<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent to a user an Admin creates in Admin → Users, with the password the Admin set.
 * Sent synchronously (never queued) so the plain-text password is not stored in a queue table.
 */
class WelcomeMail extends Mailable
{
    public function __construct(public User $user, public string $password) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to '.config('app.name').': your login details');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.welcome');
    }
}
