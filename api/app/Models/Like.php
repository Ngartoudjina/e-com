<?php

namespace App\Models;

use App\Models\Concerns\HasTextUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Like extends Model
{
    use HasTextUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    public const UPDATED_AT = null;

    /**
     * `id` est renseigné explicitement : les favoris utilisent un identifiant
     * composé « uid:productId », ce qui rend le toggle idempotent. Sans lui dans
     * $fillable, il serait ignoré et le trait générerait un UUID aléatoire,
     * empêchant tout retrait du favori.
     */
    protected $fillable = [
        'id',
        'uid',
        'product_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
