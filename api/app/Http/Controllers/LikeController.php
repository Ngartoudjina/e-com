<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Like;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Favoris. Porté depuis back/src/controllers/like.controller.js.
 *
 * La version Node écrivait `likes.userId` et une colonne `product` absentes de
 * schema.js — l'endpoint ne pouvait pas fonctionner. On conserve l'intention
 * (identifiant composé « uid:productId », donc idempotent) sur les colonnes réelles.
 */
class LikeController extends Controller
{
    public function toggle(Request $request): JsonResponse
    {
        $uid = $request->user()->getKey();
        $productId = $request->input('productId');

        if (! $productId) {
            return response()->json(['error' => 'productId requis'], 400);
        }

        if (! Product::whereKey($productId)->exists()) {
            return response()->json(['error' => 'Produit non trouvé'], 404);
        }

        $id = $uid.':'.$productId;
        $existant = Like::find($id);

        if ($existant) {
            $existant->delete();

            return response()->json([
                'success' => true,
                'liked' => false,
                'message' => 'Retiré des favoris',
            ]);
        }

        Like::create([
            'id' => $id,
            'uid' => $uid,
            'product_id' => $productId,
        ]);

        return response()->json([
            'success' => true,
            'liked' => true,
            'message' => 'Ajouté aux favoris',
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $uid = $request->user()->getKey();

        $favoris = Like::with('product')->where('uid', $uid)->get();

        return response()->json([
            'success' => true,
            'likes' => $favoris->map(fn (Like $like) => [
                'id' => $like->id,
                'productId' => $like->product_id,
                'product' => $like->product
                    ? (new ProductResource($like->product))->resolve()
                    : null,
                'createdAt' => $like->created_at?->toIso8601String(),
            ])->all(),
            'likedProductIds' => $favoris->pluck('product_id')->all(),
        ]);
    }
}
