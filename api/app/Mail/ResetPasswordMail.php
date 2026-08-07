<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $utilisateur,
        public string $jeton,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Réinitialisation de votre mot de passe GOLDSHOP');
    }

    public function content(): Content
    {
        $base = rtrim((string) config('app.frontend_url'), '/');

        return new Content(
            markdown: 'mail.reset-password',
            with: [
                'prenom' => $this->utilisateur->first_name ?: $this->utilisateur->name,
                'lien' => $base.'/reinitialisation?token='.$this->jeton,
            ],
        );
    }
}
