<?php

namespace App\Models;

use App\Models\Concerns\HasTextUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    use HasTextUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uid',
        'affiliate_code',
        'referral_link',
        'identity_card_url',
        'commission_rate',
        'total_earnings',
        'total_referrals',
        'referral_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'float',
            'total_earnings' => 'float',
            'total_referrals' => 'integer',
            'referral_count' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'affiliate_id');
    }
}
