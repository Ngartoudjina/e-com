<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Mail\BulkEmailMail;
use App\Models\Affiliate;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\CatalogueCache;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Administration. Porté depuis back/src/controllers/admin.controller.js.
 *
 * Le contrôleur Node écrivait `products.imageUrl` et `products.isActive`, et
 * ciblait les utilisateurs par `users.id` : aucune de ces colonnes n'existe.
 * Le schéma définit `media_url`, `media_public_id`, et `uid` comme clé.
 *
 * Il passait aussi une URL à `deleteImage()` là où Cloudinary attend un
 * identifiant public : les médias remplacés n'étaient jamais supprimés.
 * `media_public_id` est désormais conservé et utilisé pour cela.
 */
class AdminController extends Controller
{
    public function __construct(
        private readonly MediaService $medias,
        private readonly CatalogueCache $cache,
    ) {}

    // ---------------------------------------------------------------- produits

    public function listProducts(): JsonResponse
    {
        $produits = Product::latest('created_at')->get();

        return response()->json([
            'success' => true,
            'products' => ProductResource::collection($produits)->resolve(),
            'count' => $produits->count(),
        ]);
    }

    public function createProduct(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:120'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'mediaUrl' => ['nullable', 'string', 'url'],
            'media' => ['nullable', 'file', 'max:51200'],
        ]);

        $url = $donnees['mediaUrl'] ?? null;
        $publicId = null;

        if ($request->hasFile('media')) {
            $media = $this->medias->envoyerMedia($request->file('media'));
            $url = $media['url'];
            $publicId = $media['publicId'];
        }

        if (! $url) {
            return response()->json(['error' => 'Nom, prix et image sont requis'], 400);
        }

        $produit = Product::create([
            'name' => $donnees['name'],
            'price' => (float) $donnees['price'],
            'description' => $donnees['description'] ?? '',
            'category' => $donnees['category'] ?? 'Autre',
            'stock' => (int) ($donnees['stock'] ?? 0),
            'rating' => (float) ($donnees['rating'] ?? 0),
            'media_url' => $url,
            'media_public_id' => $publicId,
            'created_by' => $request->user()->getKey(),
        ]);

        $this->cache->invalider();

        return response()->json([
            'success' => true,
            'productId' => $produit->getKey(),
            'product' => (new ProductResource($produit))->resolve(),
            'message' => 'Produit créé avec succès',
        ], 201);
    }

    public function updateProduct(Request $request, string $id): JsonResponse
    {
        $produit = Product::find($id);

        if (! $produit) {
            return response()->json(['error' => 'Produit non trouvé'], 404);
        }

        $donnees = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'category' => ['sometimes', 'string', 'max:120'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'rating' => ['sometimes', 'numeric', 'between:0,5'],
            'mediaUrl' => ['sometimes', 'string', 'url'],
            'media' => ['sometimes', 'file', 'max:51200'],
        ]);

        $ancienPublicId = $produit->media_public_id;
        $mediaRemplace = false;

        if ($request->hasFile('media')) {
            $media = $this->medias->envoyerMedia($request->file('media'));
            $produit->media_url = $media['url'];
            $produit->media_public_id = $media['publicId'];
            $mediaRemplace = true;
        } elseif (array_key_exists('mediaUrl', $donnees)) {
            $produit->media_url = $donnees['mediaUrl'];
        }

        foreach (['name', 'price', 'description', 'category', 'stock', 'rating'] as $champ) {
            if (array_key_exists($champ, $donnees)) {
                $produit->{$champ} = $donnees[$champ];
            }
        }

        $produit->save();

        // L'ancien média n'est supprimé qu'une fois la nouvelle version enregistrée :
        // en cas d'échec, on ne perd pas l'image existante.
        if ($mediaRemplace) {
            $this->medias->supprimer($ancienPublicId);
        }

        $this->cache->invalider();

        return response()->json([
            'success' => true,
            'product' => (new ProductResource($produit->fresh()))->resolve(),
            'message' => 'Produit mis à jour avec succès',
        ]);
    }

    public function deleteProduct(string $id): JsonResponse
    {
        $produit = Product::find($id);

        if (! $produit) {
            return response()->json(['error' => 'Produit non trouvé'], 404);
        }

        $publicId = $produit->media_public_id;
        $produit->delete();
        $this->medias->supprimer($publicId);
        $this->cache->invalider();

        return response()->json(['success' => true, 'message' => 'Produit supprimé avec succès']);
    }

    // ---------------------------------------------------------------- commandes

    /** Liste des commandes, filtrable par statut. */
    public function listOrders(Request $request): JsonResponse
    {
        $statut = (string) $request->query('status', '');

        $commandes = Order::with('items')
            ->when($statut !== '', fn ($q) => $q->where('status', $statut))
            ->latest('placed_at')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => OrderResource::collection($commandes)->resolve(),
            'count' => $commandes->count(),
            // Compteurs par statut, pour les onglets de filtre.
            'byStatus' => Order::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    /**
     * Fait avancer une commande.
     * Les transitions sont contraintes par le modèle : on n'expédie pas une
     * commande annulée, et on ne revient pas en arrière.
     */
    public function updateOrderStatus(Request $request, string $reference): JsonResponse
    {
        $donnees = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', Order::STATUTS)],
        ]);

        $commande = Order::with('items')->where('reference', $reference)->first();

        if (! $commande) {
            return response()->json(['error' => 'Commande non trouvée'], 404);
        }

        $nouveau = $donnees['status'];

        if ($commande->status === $nouveau) {
            return response()->json([
                'success' => true,
                'order' => (new OrderResource($commande))->resolve(),
                'message' => 'Statut inchangé',
            ]);
        }

        if (! $commande->peutPasserA($nouveau)) {
            return response()->json([
                'error' => sprintf('Transition impossible : %s vers %s.', $commande->status, $nouveau),
            ], 422);
        }

        $horodatage = match ($nouveau) {
            'shipped' => ['shipped_at' => now()],
            'delivered' => ['delivered_at' => now()],
            'cancelled' => ['cancelled_at' => now()],
            default => [],
        };

        $commande->forceFill(['status' => $nouveau] + $horodatage)->save();

        return response()->json([
            'success' => true,
            'order' => (new OrderResource($commande->fresh('items')))->resolve(),
            'message' => 'Statut mis à jour',
        ]);
    }

    // ---------------------------------------------------------------- utilisateurs

    public function listUsers(): JsonResponse
    {
        $utilisateurs = User::latest('created_at')->get();

        /*
         * Commandes et dépenses réelles, en une seule requête agrégée plutôt
         * qu'une par client. L'écran affichait jusqu'ici des montants inventés.
         * Les commandes annulées et remboursées ne comptent pas comme dépense.
         */
        $cumuls = Order::query()
            ->whereNotNull('uid')
            ->whereIn('status', ['paid', 'preparing', 'shipped', 'delivered'])
            ->selectRaw('uid, count(*) as commandes, sum(total) as depense')
            ->groupBy('uid')
            ->get()
            ->keyBy('uid');

        $charge = UserResource::collection($utilisateurs)->resolve();

        foreach ($charge as $index => $utilisateur) {
            $cumul = $cumuls[$utilisateur['uid']] ?? null;
            $charge[$index]['ordersCount'] = (int) ($cumul->commandes ?? 0);
            $charge[$index]['totalSpent'] = round((float) ($cumul->depense ?? 0), 2);
        }

        return response()->json([
            'success' => true,
            'users' => $charge,
            'count' => $utilisateurs->count(),
        ]);
    }

    public function updateUserRole(Request $request, string $uid): JsonResponse
    {
        $donnees = $request->validate([
            'isAdmin' => ['required', 'boolean'],
        ]);

        $utilisateur = User::find($uid);

        if (! $utilisateur) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Un administrateur ne peut pas se retirer lui-même ses droits : sans
        // cela, le dernier admin peut se verrouiller hors de l'administration.
        if ($utilisateur->getKey() === $request->user()->getKey() && ! $donnees['isAdmin']) {
            return response()->json(['error' => 'Vous ne pouvez pas retirer vos propres droits admin'], 400);
        }

        $utilisateur->forceFill(['is_admin' => $donnees['isAdmin']])->save();

        return response()->json([
            'success' => true,
            'user' => (new UserResource($utilisateur))->resolve(),
            'message' => 'Rôle mis à jour',
        ]);
    }

    // ---------------------------------------------------------------- statistiques

    public function analytics(): JsonResponse
    {
        return response()->json($this->cache->memoriser('analytics', [], function () {
        $produits = Product::query();

        return [
            'success' => true,
            'analytics' => [
                'totalProducts' => (clone $produits)->count(),
                'totalStock' => (int) (clone $produits)->sum('stock'),
                'totalSold' => (int) (clone $produits)->sum('sold_count'),
                'outOfStock' => (clone $produits)->where('stock', 0)->count(),
                'totalUsers' => User::count(),
                'admins' => User::where('is_admin', true)->count(),
                'affiliates' => Affiliate::count(),
                'subscribers' => Subscriber::where('opt_out', false)->count(),
                'productsByCategory' => Product::query()
                    ->selectRaw('category, count(*) as total')
                    ->groupBy('category')
                    ->pluck('total', 'category'),
            ],
            'ventes' => $this->ventes(),
            'timestamp' => now()->toIso8601String(),
        ];
        }));
    }

    /**
     * Chiffres de vente réels.
     *
     * L'écran d'analyse affichait des montants écrits en dur — un revenu
     * inventé, un nombre de visiteurs qui n'a jamais été mesuré. Mieux vaut
     * peu de chiffres exacts que beaucoup de chiffres faux.
     *
     * Les commandes annulées et remboursées sortent du chiffre d'affaires ;
     * elles restent comptées à part pour ne pas disparaître de la vue.
     */
    private function ventes(): array
    {
        $encaissables = ['paid', 'preparing', 'shipped', 'delivered'];

        $retenues = Order::whereIn('status', $encaissables);
        $revenu = (float) (clone $retenues)->sum('total');
        $nombre = (clone $retenues)->count();

        /*
         * Douze mois glissants, y compris les mois sans vente : une série
         * trouée déformerait la courbe en rapprochant deux points éloignés.
         */
        $debut = now()->startOfMonth()->subMonths(11);

        $parMois = Order::whereIn('status', $encaissables)
            ->where('placed_at', '>=', $debut)
            ->get(['placed_at', 'total'])
            ->groupBy(fn (Order $c) => $c->placed_at?->format('Y-m'))
            ->map(fn ($groupe) => [
                'total' => round((float) $groupe->sum('total'), 2),
                'commandes' => $groupe->count(),
            ]);

        $serie = [];
        for ($i = 0; $i < 12; $i++) {
            $mois = $debut->copy()->addMonths($i);
            $cle = $mois->format('Y-m');
            $serie[] = [
                'mois' => $cle,
                'total' => $parMois[$cle]['total'] ?? 0,
                'commandes' => $parMois[$cle]['commandes'] ?? 0,
            ];
        }

        return [
            'revenu' => round($revenu, 2),
            'commandes' => $nombre,
            'panierMoyen' => $nombre > 0 ? round($revenu / $nombre, 2) : 0,
            'enAttente' => Order::where('status', 'pending')->count(),
            'annulees' => Order::whereIn('status', ['cancelled', 'refunded'])->count(),
            'parStatut' => Order::selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'parMois' => $serie,
            'meilleuresVentes' => Product::query()
                ->where('sold_count', '>', 0)
                ->orderByDesc('sold_count')
                ->limit(5)
                ->get(['id', 'name', 'price', 'sold_count', 'stock'])
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'nom' => $p->name,
                    'vendus' => (int) $p->sold_count,
                    'stock' => (int) $p->stock,
                    'revenu' => round((float) $p->price * (int) $p->sold_count, 2),
                ]),
        ];
    }

    // ---------------------------------------------------------------- newsletter

    /**
     * Diffusion à tous les abonnés.
     *
     * Les messages sont mis en file plutôt qu'envoyés dans la requête : le
     * backend Node bouclait sur toute la liste en synchrone, ce qui garantissait
     * une expiration de la requête passé quelques dizaines d'abonnés.
     * Un worker doit tourner (`php artisan queue:work`).
     */
    public function sendBulkEmail(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:20000'],
            'imageUrl' => ['nullable', 'string', 'url'],
        ]);

        $destinataires = Subscriber::where('opt_out', false)->pluck('email');

        if ($destinataires->isEmpty()) {
            return response()->json(['error' => 'Aucun abonné disponible'], 400);
        }

        foreach ($destinataires as $email) {
            Mail::to($email)->queue(new BulkEmailMail(
                $donnees['subject'],
                $donnees['message'],
                $email,
                $donnees['imageUrl'] ?? null,
            ));
        }

        return response()->json([
            'success' => true,
            'queued' => $destinataires->count(),
            'message' => $destinataires->count().' email(s) mis en file d\'envoi',
        ]);
    }

    // ---------------------------------------------------------------- upload

    /**
     * Envoi direct utilisé par l'écran produits.
     *
     * Les clés `secure_url` / `public_id` sont volontairement en snake_case :
     * ProductsAdmin.vue lit la réponse brute de Cloudinary.
     */
    public function upload(Request $request): JsonResponse
    {
        // Le champ s'appelle `file` : c'est ce qu'envoie ProductsAdmin.vue.
        // La route Node attendait `image`, d'où un envoi qui n'aboutissait jamais.
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
        ]);

        if (! $this->medias->estConfigure()) {
            return response()->json(['error' => 'Service de médias non configuré'], 503);
        }

        $media = $this->medias->envoyerMedia($request->file('file'));

        return response()->json([
            'secure_url' => $media['url'],
            'public_id' => $media['publicId'],
        ]);
    }
}
