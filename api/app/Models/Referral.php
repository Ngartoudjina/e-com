<?php

namespace App\Models;

use App\Models\Concerns\HasTextUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasTextUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    /** La table n'a ni created_at ni updated_at (voir schema.js). */
    public $timestamps = false;

    protected $fillable = [
        'affiliate_id',
        'referred_user_id',
        'affiliate_code',
        'first_click_at',
        'last_click_at',
        'conversion_at',
        'status',
        'orders',
        'total_value',
    ];

    protected function casts(): array
    {
        return [
            'orders' => 'array',
            'total_value' => 'float',
            'first_click_at' => 'datetime',
            'last_click_at' => 'datetime',
            'conversion_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'affiliate_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id', 'uid');
    }
}
