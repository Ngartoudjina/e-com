<?php

namespace App\Models;

use App\Models\Concerns\HasTextUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateRequest extends Model
{
    use HasTextUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    /** Seul `created_at` existe sur cette table. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'uid',
        'reason',
        'identity_card_url',
        'identity_card_public_id',
        'status',
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
}
