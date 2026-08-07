<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Inscription en attente de vérification d'e-mail.
 * La ligne est promue vers `users` une fois le lien de confirmation suivi.
 */
class PendingUser extends Model
{
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    public $incrementing = false;

    public const UPDATED_AT = null;

    protected $fillable = [
        'uid',
        'email',
        'name',
        'first_name',
        'last_name',
        'phone',
        'address',
        'hashed_password',
        'is_admin',
        'verification_status',
    ];

    protected $hidden = [
        'hashed_password',
    ];

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
