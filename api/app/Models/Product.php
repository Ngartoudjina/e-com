<?php

namespace App\Models;

use App\Models\Concerns\HasTextUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasTextUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'price',
        'description',
        'rating',
        'stock',
        'category',
        'sold_count',
        'media_url',
        'media_public_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'rating' => 'float',
            'stock' => 'integer',
            'sold_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uid');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class, 'product_id');
    }
}
