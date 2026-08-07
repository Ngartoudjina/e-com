<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    /** L'adresse e-mail sert de clé primaire (voir schema.js). */
    protected $primaryKey = 'email';

    protected $keyType = 'string';

    public $incrementing = false;

    /** Horodatage unique et non conventionnel : `subscribed_at`. */
    public $timestamps = false;

    protected $fillable = [
        'email',
        'subscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
        ];
    }
}
