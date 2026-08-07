<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;

    /**
     * La base existante identifie les utilisateurs par un `uid` texte
     * (hérité des UID Firebase), pas par un auto-incrément.
     */
    protected $primaryKey = 'uid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uid',
        'email',
        'name',
        'first_name',
        'last_name',
        'phone',
        'address',
        'password',
        'is_admin',
        'is_affiliate',
        'email_verified',
        'photo_url',
        'provider',
        'google_id',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'reset_token',
        'reset_token_expiry',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_affiliate' => 'boolean',
            'email_verified' => 'boolean',
            'reset_token_expiry' => 'datetime',
            'last_login' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function affiliate(): HasOne
    {
        return $this->hasOne(Affiliate::class, 'uid', 'uid');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class, 'uid', 'uid');
    }

    public function affiliateRequests(): HasMany
    {
        return $this->hasMany(AffiliateRequest::class, 'uid', 'uid');
    }
}
