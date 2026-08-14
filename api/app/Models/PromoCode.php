<?php

namespace App\Models;

use App\Models\Concerns\HasTextUuid;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use HasTextUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'label',
        'type',
        'value',
        'min_subtotal',
        'active',
        'starts_at',
        'expires_at',
        'max_uses',
        'used_count',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'min_subtotal' => 'float',
            'active' => 'boolean',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** Le code est normalisé en capitales à l'écriture comme à la lecture. */
    public function setCodeAttribute(string $valeur): void
    {
        $this->attributes['code'] = strtoupper(trim($valeur));
    }

    public function estUtilisable(float $sousTotal): bool
    {
        if (! $this->active) {
            return false;
        }
        if ($this->starts_at && now()->lessThan($this->starts_at)) {
            return false;
        }
        if ($this->expires_at && now()->greaterThan($this->expires_at)) {
            return false;
        }
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return $sousTotal >= $this->min_subtotal;
    }

    /** La remise ne peut jamais dépasser le montant de la commande. */
    public function remisePour(float $sousTotal): float
    {
        $remise = $this->type === 'amount'
            ? $this->value
            : $sousTotal * ($this->value / 100);

        return round(min($remise, $sousTotal), 2);
    }
}
