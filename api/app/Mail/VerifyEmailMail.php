<?php

namespace App\Mail;

use App\Models\PendingUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public PendingUser $enAttente,
        public string $jeton,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Vérifiez votre email pour rejoindre GOLDSHOP');
    }

    public function content(): Content
    {
        $base = rtrim((string) config('app.frontend_url'), '/');

        return new Content(
            markdown: 'mail.verify-email',
            with: [
                'prenom' => trim($this->enAttente->first_name.' '.$this->enAttente->last_name) ?: $this->enAttente->name,
                'lien' => $base.'/verification-email?token='.$this->jeton,
            ],
        );
    }
}
