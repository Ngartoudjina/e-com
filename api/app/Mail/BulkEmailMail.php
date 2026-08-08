<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Message de la newsletter, adressé à un abonné.
 *
 * Mis en file d'attente : le backend Node bouclait sur tous les abonnés au sein
 * de la requête HTTP, qui expirait bien avant la fin dès que la liste
 * s'allongeait. Il faut donc un worker (`php artisan queue:work`).
 */
class BulkEmailMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $sujet,
        public string $message,
        public string $destinataire,
        public ?string $imageUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->sujet);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.bulk',
            with: [
                'message' => $this->message,
                'imageUrl' => $this->imageUrl,
                // Lien signé : sans signature, n'importe qui pourrait désabonner
                // n'importe quelle adresse en devinant l'URL.
                'lienDesabonnement' => URL::signedRoute('newsletter.unsubscribe', [
                    'email' => $this->destinataire,
                ]),
            ],
        );
    }
}
