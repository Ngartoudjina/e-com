<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation publique d'un produit.
 *
 * Deux contraintes héritées du backend Node, à ne pas perdre de vue :
 *  - les clés sont en camelCase. Le frontend Vue type `mediaUrl` (voir
 *    front-vue/src/types/index.ts) ; renvoyer le snake_case d'Eloquent viderait
 *    silencieusement la grille produits.
 *  - `created_by` n'est jamais exposé, comme le faisait `publicProduct()`.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'description' => $this->description,
            'rating' => (float) $this->rating,
            'stock' => (int) $this->stock,
            'category' => $this->category,
            'soldCount' => (int) $this->sold_count,
            'mediaUrl' => $this->media_url,
            'mediaPublicId' => $this->media_public_id,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
