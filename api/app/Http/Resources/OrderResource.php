<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Commande, en camelCase comme le reste de l'API. */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'email' => $this->email,
            'phone' => $this->phone,

            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'shipping' => (float) $this->shipping,
            'total' => (float) $this->total,
            'currency' => $this->currency,
            'promoCode' => $this->promo_code,

            'shippingMethod' => $this->shipping_method,
            'shippingAddress' => [
                'name' => $this->shipping_name,
                'address' => $this->shipping_address,
                'postalCode' => $this->shipping_postal_code,
                'city' => $this->shipping_city,
                'country' => $this->shipping_country,
            ],

            'placedAt' => $this->placed_at?->toIso8601String(),
            'shippedAt' => $this->shipped_at?->toIso8601String(),
            'deliveredAt' => $this->delivered_at?->toIso8601String(),
            'cancelledAt' => $this->cancelled_at?->toIso8601String(),

            'itemCount' => $this->whenLoaded('items', fn () => $this->items->sum('quantity')),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($ligne) => [
                'id' => $ligne->id,
                'productId' => $ligne->product_id,
                'name' => $ligne->name,
                'reference' => $ligne->reference,
                'color' => $ligne->color,
                'size' => $ligne->size,
                'mediaUrl' => $ligne->media_url,
                'unitPrice' => (float) $ligne->unit_price,
                'quantity' => (int) $ligne->quantity,
                'lineTotal' => (float) $ligne->line_total,
            ])->all()),
        ];
    }
}
