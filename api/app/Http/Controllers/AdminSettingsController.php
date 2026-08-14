<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Réglages de la boutique et codes promotionnels, côté administration.
 *
 * Ces valeurs étaient jusqu'ici figées dans le code : le seuil de franco dans
 * deux composants Vue, le code ARCHIVES20 en clair dans le panier. Elles
 * deviennent modifiables sans redéploiement.
 */
class AdminSettingsController extends Controller
{
    public function __construct(private readonly SettingsService $reglages) {}

    // ---------------------------------------------------------------- réglages

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'settings' => $this->reglages->tous(),
            // Valeurs de configuration, pour permettre un retour en arrière.
            'defaults' => collect(SettingsService::CHAMPS)
                ->map(fn ($cle) => config($cle))
                ->all(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'freeShippingThreshold' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            'shippingStandard' => ['sometimes', 'numeric', 'min:0', 'max:1000'],
            'shippingExpress' => ['sometimes', 'numeric', 'min:0', 'max:1000'],
            // La TVA est une fraction : 0,20 et non 20.
            'vatRate' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'returnDays' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'announcements' => ['sometimes', 'array', 'max:5'],
            'announcements.*' => ['string', 'max:120'],
        ]);

        $this->reglages->enregistrer($donnees);

        return response()->json([
            'success' => true,
            'settings' => $this->reglages->tous(),
            'message' => 'Réglages enregistrés',
        ]);
    }

    public function reset(string $champ): JsonResponse
    {
        if (! array_key_exists($champ, SettingsService::CHAMPS)) {
            return response()->json(['error' => 'Réglage inconnu'], 404);
        }

        $this->reglages->reinitialiser($champ);

        return response()->json([
            'success' => true,
            'settings' => $this->reglages->tous(),
            'message' => 'Réglage remis à sa valeur d’origine',
        ]);
    }

    // ---------------------------------------------------------------- codes promo

    public function promos(): JsonResponse
    {
        $codes = PromoCode::latest('created_at')->get();

        return response()->json([
            'success' => true,
            'promos' => $codes->map(fn (PromoCode $p) => $this->presenter($p))->all(),
            'count' => $codes->count(),
        ]);
    }

    public function creerPromo(Request $request): JsonResponse
    {
        // Le doublon est arrêté par la règle d'unicité, sur un code déjà
        // normalisé en capitales : pas de second contrôle ici.
        $donnees = $this->validerPromo($request);

        $promo = PromoCode::create($donnees + ['used_count' => 0]);

        return response()->json([
            'success' => true,
            'promo' => $this->presenter($promo),
            'message' => 'Code créé',
        ], 201);
    }

    public function modifierPromo(Request $request, string $id): JsonResponse
    {
        $promo = PromoCode::find($id);

        if (! $promo) {
            return response()->json(['error' => 'Code non trouvé'], 404);
        }

        $donnees = $this->validerPromo($request, $promo->getKey());
        $promo->fill($donnees)->save();

        return response()->json([
            'success' => true,
            'promo' => $this->presenter($promo->fresh()),
            'message' => 'Code mis à jour',
        ]);
    }

    public function supprimerPromo(string $id): JsonResponse
    {
        $promo = PromoCode::find($id);

        if (! $promo) {
            return response()->json(['error' => 'Code non trouvé'], 404);
        }

        /*
         * Un code déjà utilisé n'est pas supprimé mais désactivé : les
         * commandes passées y font référence, et l'effacer priverait la
         * comptabilité de son origine.
         */
        if ($promo->used_count > 0) {
            $promo->forceFill(['active' => false])->save();

            return response()->json([
                'success' => true,
                'promo' => $this->presenter($promo->fresh()),
                'message' => 'Code désactivé — il a déjà servi et reste référencé par des commandes.',
            ]);
        }

        $promo->delete();

        return response()->json(['success' => true, 'message' => 'Code supprimé']);
    }

    private function validerPromo(Request $request, ?string $ignorer = null): array
    {
        /*
         * Le code est mis en capitales avant la validation, et non après :
         * la règle d'unicité comparait la saisie brute au stock déjà
         * normalisé, si bien que « hiver25 » passait à côté de « HIVER25 ».
         */
        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }

        return $request->validate([
            'code' => [
                $ignorer ? 'sometimes' : 'required',
                'string',
                'max:40',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('promo_codes', 'code')->ignore($ignorer),
            ],
            'label' => ['nullable', 'string', 'max:160'],
            'type' => ['sometimes', 'string', 'in:percent,amount'],
            'value' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            'min_subtotal' => ['sometimes', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
        ], [
            // L'interface est en français : ses messages d'erreur aussi.
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code existe déjà.',
            'code.regex' => 'Le code n’accepte que lettres, chiffres, tiret et souligné.',
            'code.max' => 'Le code ne peut pas dépasser 40 caractères.',
            'type.in' => 'Le type doit être « percent » ou « amount ».',
            'value.numeric' => 'La valeur doit être un nombre.',
            'value.min' => 'La valeur ne peut pas être négative.',
            'min_subtotal.min' => 'Le minimum d’achat ne peut pas être négatif.',
            'expires_at.date' => 'La date d’expiration est invalide.',
            'max_uses.min' => 'Le nombre d’utilisations doit valoir au moins 1.',
        ]);
    }

    private function presenter(PromoCode $promo): array
    {
        return [
            'id' => $promo->id,
            'code' => $promo->code,
            'label' => $promo->label,
            'type' => $promo->type,
            'value' => (float) $promo->value,
            'minSubtotal' => (float) $promo->min_subtotal,
            'active' => (bool) $promo->active,
            'startsAt' => $promo->starts_at?->toIso8601String(),
            'expiresAt' => $promo->expires_at?->toIso8601String(),
            'maxUses' => $promo->max_uses,
            'usedCount' => (int) $promo->used_count,
        ];
    }
}
