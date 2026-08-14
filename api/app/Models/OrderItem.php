<?php

namespace App\Models;

use App\Models\Concerns\HasTextUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de commande.
 *
 * Les champs `name`, `unit_price`, `color`, `size` et `media_url` sont des
 * copies prises au moment de l'achat : ils ne suivent pas les évolutions
 * ultérieures du produit.
 */
class OrderItem extends Model
{
    use HasTextUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'product_id',
        'name',
        'reference',
        'color',
        'size',
        'media_url',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'float',
            'line_total' => 'float',
            'quantity' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /** Le produit peut avoir été retiré du catalogue depuis. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
