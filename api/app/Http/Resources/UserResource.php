<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation publique d'un utilisateur, en camelCase comme le reste de l'API.
 *
 * Ne jamais y ajouter `password`, `reset_token` ni `verification_token`.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->uid,
            'email' => $this->email,
            'name' => $this->name,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'isAdmin' => (bool) $this->is_admin,
            'isAffiliate' => (bool) $this->is_affiliate,
            'emailVerified' => (bool) $this->email_verified,
            'photoUrl' => $this->photo_url,
            'provider' => $this->provider,
            'createdAt' => $this->created_at?->toIso8601String(),
            'lastLogin' => $this->last_login?->toIso8601String(),
        ];
    }
}
