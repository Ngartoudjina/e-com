<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Mise en cache des lectures du catalogue.
 *
 * Le magasin de cache configuré est la base de données, qui ne gère pas les
 * étiquettes (`Cache::tags`). L'invalidation passe donc par un numéro de
 * version : il entre dans chaque clé, et l'incrémenter périme d'un coup
 * toutes les entrées dérivées du catalogue, sans avoir à les énumérer.
 *
 * Ce détour évite aussi de purger tout le cache applicatif à chaque
 * modification de produit.
 */
class CatalogueCache
{
    private const CLE_VERSION = 'catalogue:version';

    /** Durée volontairement courte : le catalogue bouge, les prix aussi. */
    public const DUREE = 300; // secondes

    public function version(): int
    {
        return (int) Cache::rememberForever(self::CLE_VERSION, fn () => 1);
    }

    /** Périme toutes les lectures du catalogue. */
    public function invalider(): void
    {
        // `increment` échoue si la clé n'existe pas encore sur certains magasins.
        $this->version();
        Cache::increment(self::CLE_VERSION);
    }

    /**
     * Mémorise le résultat sous une clé versionnée.
     *
     * @template T
     *
     * @param  callable(): T  $calcul
     * @return T
     */
    public function memoriser(string $nom, array $parametres, callable $calcul)
    {
        // Les paramètres sont triés : ?a=1&b=2 et ?b=2&a=1 doivent partager
        // la même entrée.
        ksort($parametres);

        $cle = sprintf(
            'catalogue:v%d:%s:%s',
            $this->version(),
            $nom,
            md5(json_encode($parametres))
        );

        return Cache::remember($cle, self::DUREE, $calcul);
    }
}
