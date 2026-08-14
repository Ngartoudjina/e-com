<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PromoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Commandes côté client. */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $commandes,
        private readonly PromoService $promos,
    ) {}

    /**
     * Enregistre une commande.
     *
     * La requête n'apporte aucun montant : seulement des références, des
     * quantités et l'adresse. Tout le calcul est refait côté serveur.
     */
    public function store(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.productId' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.size' => ['nullable', 'string', 'max:12'],
            'items.*.color' => ['nullable', 'string', 'max:40'],

            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'name' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'postalCode' => ['nullable', 'string', 'max:12'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],

            'shippingMethod' => ['nullable', 'string', 'in:standard,express'],
            'promoCode' => ['nullable', 'string', 'max:40'],
        ]);

        /*
         * La route est publique pour permettre la commande sans compte.
         * Sur une route publique, `$request->user()` sans argument renvoie
         * null même avec un jeton valide : le garde Sanctum n'y est pas
         * appliqué. Il faut donc le nommer explicitement, sans quoi les
         * commandes des clients connectés partent en « invitée ».
         */
        $uid = $request->user('sanctum')?->getKey();

        try {
            $commande = $this->commandes->creer(
                $donnees['items'],
                $donnees,
                $uid
            );
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->validator->errors()->first()], 422);
        }

        $this->promos->consommer($donnees['promoCode'] ?? null);

        return response()->json([
            'success' => true,
            'order' => (new OrderResource($commande))->resolve(),
        ], 201);
    }

    /** Commandes du compte connecté. */
    public function index(Request $request): JsonResponse
    {
        $commandes = Order::with('items')
            ->where('uid', $request->user()->getKey())
            ->latest('placed_at')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => OrderResource::collection($commandes)->resolve(),
            'count' => $commandes->count(),
        ]);
    }

    /**
     * Détail d'une commande.
     * Un client ne peut consulter que les siennes ; un administrateur, toutes.
     */
    public function show(Request $request, string $reference): JsonResponse
    {
        $commande = Order::with('items')->where('reference', $reference)->first();

        if (! $commande) {
            return response()->json(['error' => 'Commande non trouvée'], 404);
        }

        $utilisateur = $request->user();
        if (! $utilisateur->is_admin && $commande->uid !== $utilisateur->getKey()) {
            // Même réponse que pour une commande inexistante : on ne confirme
            // pas l'existence d'une référence qui ne nous appartient pas.
            return response()->json(['error' => 'Commande non trouvée'], 404);
        }

        return response()->json([
            'success' => true,
            'order' => (new OrderResource($commande))->resolve(),
        ]);
    }

    /** Prévisualisation d'un code promotionnel, depuis le panier. */
    public function verifierPromo(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $resultat = $this->promos->evaluer($donnees['code'], (float) $donnees['subtotal']);

        return response()->json($resultat, $resultat['valide'] ? 200 : 422);
    }
}
