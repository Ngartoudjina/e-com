<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jeton de vérification d'e-mail porté par l'inscription en attente.
 *
 * Le backend Node déléguait ce lien à Firebase
 * (`auth.generateEmailVerificationLink`). En passant à Sanctum, l'application
 * émet et vérifie son propre jeton : il lui faut donc un emplacement.
 *
 * Seul le condensat est stocké, jamais le jeton en clair : une fuite de la base
 * ne permettrait pas de valider un compte à la place de son propriétaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_users', function (Blueprint $table) {
            $table->text('verification_token')->nullable();
            $table->timestamp('verification_token_expiry')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pending_users', function (Blueprint $table) {
            $table->dropColumn(['verification_token', 'verification_token_expiry']);
        });
    }
};
