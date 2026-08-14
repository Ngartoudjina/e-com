<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Réglages de la boutique : base de données par-dessus configuration.
 *
 * Une clé absente de la table retombe sur config/boutique.php. La table ne
 * contient donc que les écarts volontaires, et remettre un réglage à sa
 * valeur d'origine revient à supprimer sa ligne.
 *
 * Le tout est mis en cache : ces valeurs sont lues à chaque page.
 */
class SettingsService
{
    private const CLE_CACHE = 'boutique:reglages';

    private const DUREE = 300;

    /** Réglages exposés, avec la clé de configuration correspondante. */
    public const CHAMPS = [
        'freeShippingThreshold' => 'boutique.livraison.franco',
        'shippingStandard' => 'boutique.livraison.modes.standard.prix',
        'shippingExpress' => 'boutique.livraison.modes.express.prix',
        'vatRate' => 'boutique.tva',
        'announcements' => 'boutique.annonces',
        'returnDays' => 'boutique.retours.jours',
    ];

    /** @return array<string, mixed> */
    public function tous(): array
    {
        return Cache::remember(self::CLE_CACHE, self::DUREE, function () {
            $enregistres = DB::table('settings')->pluck('value', 'key');

            $valeurs = [];
            foreach (self::CHAMPS as $champ => $cleConfig) {
                $valeurs[$champ] = isset($enregistres[$champ])
                    ? json_decode($enregistres[$champ], true)
                    : config($cleConfig);
            }

            return $valeurs;
        });
    }

    public function get(string $champ): mixed
    {
        return $this->tous()[$champ] ?? null;
    }

    /**
     * Enregistre les réglages fournis. Les autres sont laissés intacts.
     *
     * @param  array<string, mixed>  $valeurs
     */
    public function enregistrer(array $valeurs): void
    {
        foreach ($valeurs as $champ => $valeur) {
            if (! array_key_exists($champ, self::CHAMPS)) {
                continue;
            }

            DB::table('settings')->updateOrInsert(
                ['key' => $champ],
                ['value' => json_encode($valeur), 'updated_at' => now()]
            );
        }

        $this->oublier();
    }

    /** Remet un réglage à la valeur de configuration. */
    public function reinitialiser(string $champ): void
    {
        DB::table('settings')->where('key', $champ)->delete();
        $this->oublier();
    }

    public function oublier(): void
    {
        Cache::forget(self::CLE_CACHE);
    }

    /** Charge utile publique, servie par GET /api/settings. */
    public function pourLeSite(): array
    {
        $reglages = $this->tous();

        $modes = config('boutique.livraison.modes');

        return [
            'currency' => config('boutique.devise'),
            'freeShippingThreshold' => (float) $reglages['freeShippingThreshold'],
            'shippingMethods' => [
                [
                    'key' => 'standard',
                    'label' => $modes['standard']['libelle'],
                    'detail' => $modes['standard']['detail'],
                    'price' => (float) $reglages['shippingStandard'],
                ],
                [
                    'key' => 'express',
                    'label' => $modes['express']['libelle'],
                    'detail' => $modes['express']['detail'],
                    'price' => (float) $reglages['shippingExpress'],
                ],
            ],
            'vatRate' => (float) $reglages['vatRate'],
            'announcements' => (array) $reglages['announcements'],
            'returnDays' => (int) $reglages['returnDays'],
        ];
    }
}
