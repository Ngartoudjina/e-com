<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'reason' => $this->reason,
            'identityCardUrl' => $this->identity_card_url,
            'status' => $this->status,
            'createdAt' => $this->created_at?->toIso8601String(),
            // Le demandeur est joint quand la relation a été chargée : l'écran
            // d'administration a besoin du nom et de l'e-mail pour trancher.
            'user' => $this->whenLoaded('user', fn () => [
                'uid' => $this->user->uid,
                'email' => $this->user->email,
                'name' => $this->user->name,
            ]),
        ];
    }
}
