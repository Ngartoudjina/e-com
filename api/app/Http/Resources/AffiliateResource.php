<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compte affilié, dans la forme attendue par AffiliateView.vue.
 *
 * `commissionRate` est renvoyé tel qu'il est stocké : une fraction (0.05 = 5 %),
 * puisque le calcul des gains en dépend. Le frontend l'affiche actuellement
 * avec « {{ commissionRate }}% », ce qui produit « 0.05% » : c'est la vue qu'il
 * faut corriger en multipliant par 100, pas la valeur transmise.
 */
class AffiliateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->uid,
            'affiliateCode' => $this->affiliate_code,
            'referralLink' => $this->referral_link,
            'identityCardUrl' => $this->identity_card_url,
            'commissionRate' => (float) $this->commission_rate,
            'totalEarnings' => (float) $this->total_earnings,
            'totalReferrals' => (int) $this->total_referrals,
            'referralCount' => (int) $this->referral_count,
            'isActive' => (bool) $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
