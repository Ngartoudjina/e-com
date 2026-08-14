<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\CatalogueCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Catalogue public. Porté depuis back/src/controllers/product.controller.js
 * en conservant la forme exacte des réponses attendue par le frontend Vue.
 */
class ProductController extends Controller
{
    public function __construct(private readonly CatalogueCache $cache) {}

    /** Champs de tri autorisés, exprimés côté API en camelCase. */
    private const TRIS = [
        'createdAt' => 'created_at',
        'name' => 'name',
        'price' => 'price',
        'rating' => 'rating',
        'stock' => 'stock',
    ];

    public function index(Request $request): JsonResponse
    {
        $sortBy = (string) $request->query('sortBy', 'createdAt');
        $order = (string) $request->query('order', 'desc');

        // Messages repris à l'identique du backend Node, le frontend peut les afficher.
        if (! array_key_exists($sortBy, self::TRIS)) {
            return response()->json(
                ['error' => 'Champ de tri invalide. Utilisez : createdAt, name, price, rating, ou stock'],
                400
            );
        }

        if (! in_array($order, ['asc', 'desc'], true)) {
            return response()->json(['error' => 'Ordre invalide. Utilisez : asc ou desc'], 400);
        }

        $tout = filter_var($request->query('all', 'false'), FILTER_VALIDATE_BOOL);

        $limite = (int) $request->query('limit', 46);
        $page = (int) $request->query('page', 1);

        if (! $tout) {
            if ($limite < 1 || $limite > 100) {
                return response()->json(['error' => 'Limite invalide. Doit être entre 1 et 100'], 400);
            }
            if ($page < 1) {
                return response()->json(['error' => 'Page invalide. Doit être un nombre positif'], 400);
            }
        }

        $categorie = trim((string) $request->query('category', ''));
        $recherche = trim((string) $request->query('search', ''));

        $parametres = [
            'page' => $page, 'limit' => $limite, 'all' => $tout,
            'category' => $categorie, 'search' => $recherche,
            'sortBy' => $sortBy, 'order' => $order,
        ];

        return response()->json($this->cache->memoriser('index', $parametres, function () use (
            $categorie, $recherche, $sortBy, $order, $tout, $limite, $page
        ) {
        $requete = Product::query()
            ->when($categorie !== '', function ($q) use ($categorie) {
                $categories = array_values(array_filter(array_map('trim', explode(',', $categorie))));
                if ($categories) {
                    $q->whereIn('category', $categories);
                }
            })
            // `like` sur un préfixe, comme côté Node ; on échappe les jokers saisis.
            ->when($recherche !== '', fn ($q) => $q->where('name', 'like', $this->echapper($recherche).'%'))
            ->orderBy(self::TRIS[$sortBy], $order);

        $total = (clone $requete)->toBase()->getCountForPagination();

        $lignes = $tout
            ? $requete->get()
            : $requete->limit($limite)->offset(($page - 1) * $limite)->get();

        $pages = $limite > 0 ? (int) ceil($total / $limite) : 1;

        return [
            'products' => ProductResource::collection($lignes)->resolve(),
            'pagination' => $tout
                ? [
                    'currentPage' => 1,
                    'itemsPerPage' => $lignes->count(),
                    'totalItems' => $total,
                    'totalPages' => 1,
                    'isComplete' => true,
                ]
                : [
                    'currentPage' => $page,
                    'itemsPerPage' => $limite,
                    'totalItems' => $total,
                    'totalPages' => $pages,
                    'hasNextPage' => $page < $pages,
                    'hasPreviousPage' => $page > 1,
                ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'filters' => [
                    'category' => $categorie !== '' ? $categorie : null,
                    'search' => $recherche !== '' ? $recherche : null,
                    'sortBy' => $sortBy,
                    'order' => $order,
                ],
                'retrievedAll' => $tout,
                'count' => $lignes->count(),
            ],
        ];
        }));
    }

    public function show(string $id): JsonResponse
    {
        $produit = Product::find($id);

        if (! $produit) {
            return response()->json(['error' => 'Produit non trouvé'], 404);
        }

        return response()->json((new ProductResource($produit))->resolve());
    }

    public function simpleAll(): JsonResponse
    {
        return response()->json($this->cache->memoriser('simple-all', [], function () {
            $lignes = Product::all();

            return [
                'success' => true,
                'products' => ProductResource::collection($lignes)->resolve(),
                'count' => $lignes->count(),
                'timestamp' => now()->toIso8601String(),
            ];
        }));
    }

    public function count(Request $request): JsonResponse
    {
        $categorie = trim((string) $request->query('category', ''));
        $recherche = trim((string) $request->query('search', ''));

        return response()->json($this->cache->memoriser(
            'count',
            ['category' => $categorie, 'search' => $recherche],
            function () use ($categorie, $recherche) {
                $total = Product::query()
                    ->when($categorie !== '', fn ($q) => $q->where('category', $categorie))
                    ->when($recherche !== '', fn ($q) => $q->where('name', 'like', $this->echapper($recherche).'%'))
                    ->count();

                return [
                    'success' => true,
                    'count' => $total,
                    'filters' => ['category' => $categorie, 'search' => $recherche],
                    'timestamp' => now()->toIso8601String(),
                ];
            }
        ));
    }

    /** Neutralise les jokers LIKE saisis par l'utilisateur. */
    private function echapper(string $valeur): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $valeur);
    }
}
