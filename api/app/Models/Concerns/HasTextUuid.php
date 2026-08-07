<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Clé primaire UUID stockée en texte.
 *
 * Reproduit le comportement de `crypto.randomUUID()` utilisé par Drizzle côté Node,
 * afin que les deux backends puissent écrire dans les mêmes tables pendant la bascule.
 */
trait HasTextUuid
{
    protected static function bootHasTextUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public function getIncrementing(): bool
    {
        return false;
    }
}
