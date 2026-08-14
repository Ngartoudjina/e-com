<?php

namespace App\Models;

use App\Models\Concerns\HasTextUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasTextUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Statuts possibles, dans l'ordre du cycle de vie.
     * `cancelled` et `refunded` sont des sorties, pas des étapes.
     */
    public const STATUTS = [
        'pending',
        'paid',
        'preparing',
        'shipped',
        'delivered',
        'cancelled',
        'refunded',
    ];

    /** Transitions autorisées : on n'expédie pas une commande remboursée. */
    public const TRANSITIONS = [
        'pending' => ['paid', 'cancelled'],
        'paid' => ['preparing', 'cancelled', 'refunded'],
        'preparing' => ['shipped', 'cancelled', 'refunded'],
        'shipped' => ['delivered', 'refunded'],
        'delivered' => ['refunded'],
        'cancelled' => [],
        'refunded' => [],
    ];

    protected $fillable = [
        'reference',
        'uid',
        'email',
        'phone',
        'status',
        'subtotal',
        'discount',
        'shipping',
        'total',
        'currency',
        'promo_code',
        'shipping_method',
        'shipping_name',
        'shipping_address',
        'shipping_postal_code',
        'shipping_city',
        'shipping_country',
        'placed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'float',
            'discount' => 'float',
            'shipping' => 'float',
            'total' => 'float',
            'placed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }

    public function peutPasserA(string $statut): bool
    {
        return in_array($statut, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Référence lisible et non devinable en séquence.
     * Le préfixe reste stable pour le service client, le suffixe est tiré au
     * sort afin qu'on ne puisse pas énumérer les commandes voisines.
     */
    public static function genererReference(): string
    {
        do {
            $reference = 'GS-'.random_int(10000, 99999);
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
