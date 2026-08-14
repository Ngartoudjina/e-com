<?php

namespace App\Services;

use App\Models\PromoCode;

/**
 * Validation des codes promotionnels.
 *
 * Le calcul appartient au serveur : la valeur affichée dans le panier n'est
 * qu'une prévisualisation, elle est recalculée à la création de la commande.
 */
class PromoService
{
    /**
     * Remise applicable, en euros. Renvoie 0 si le code est absent, inconnu,
     * expiré, épuisé ou sous son minimum d'achat.
     */
    public function remisePour(?string $code, float $sousTotal): float
    {
        $promo = $this->trouver($code);

        if (! $promo || ! $promo->estUtilisable($sousTotal)) {
            return 0.0;
        }

        return $promo->remisePour($sousTotal);
    }

    public function trouver(?string $code): ?PromoCode
    {
        $propre = strtoupper(trim((string) $code));

        return $propre === '' ? null : PromoCode::where('code', $propre)->first();
    }

    /**
     * Décrit un code pour l'affichage du panier.
     *
     * @return array{valide: bool, message: string, remise: float}
     */
    public function evaluer(?string $code, float $sousTotal): array
    {
        $propre = strtoupper(trim((string) $code));

        if ($propre === '') {
            return ['valide' => false, 'message' => 'Saisissez un code.', 'remise' => 0.0];
        }

        $promo = $this->trouver($propre);

        // Message identique pour un code inconnu et un code désactivé : rien
        // ne doit permettre de deviner quels codes existent.
        if (! $promo || ! $promo->active) {
            return ['valide' => false, 'message' => 'Ce code n’est pas valide.', 'remise' => 0.0];
        }

        if ($promo->expires_at && now()->greaterThan($promo->expires_at)) {
            return ['valide' => false, 'message' => 'Ce code a expiré.', 'remise' => 0.0];
        }

        if ($promo->max_uses !== null && $promo->used_count >= $promo->max_uses) {
            return ['valide' => false, 'message' => 'Ce code n’est plus disponible.', 'remise' => 0.0];
        }

        if ($sousTotal < $promo->min_subtotal) {
            return [
                'valide' => false,
                'message' => sprintf('Ce code s’applique à partir de %s €.', number_format($promo->min_subtotal, 2, ',', ' ')),
                'remise' => 0.0,
            ];
        }

        return [
            'valide' => true,
            'message' => $promo->label ?: sprintf('%s appliqué', $promo->code),
            'remise' => $promo->remisePour($sousTotal),
        ];
    }

    /** Consomme une utilisation, à l'enregistrement de la commande. */
    public function consommer(?string $code): void
    {
        $promo = $this->trouver($code);

        if ($promo) {
            $promo->increment('used_count');
        }
    }
}
