<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schéma calqué sur la base Postgres existante (Drizzle / back/src/db/schema.js).
 *
 * Écarts assumés par rapport au squelette Laravel, imposés par les données déjà en place :
 *  - la clé primaire de `users` est `uid` (text), pas un auto-incrément ;
 *  - la vérification d'e-mail est un booléen `email_verified`, pas un `email_verified_at` ;
 *  - la réinitialisation de mot de passe passe par `reset_token` sur la ligne utilisateur,
 *    donc pas de table `password_reset_tokens`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->text('uid')->primary();
            $table->text('email')->unique();
            $table->text('name')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('phone')->nullable();
            $table->text('address')->nullable();
            // Hachages bcrypt produits par bcryptjs ($2a$/$2b$) : password_verify les accepte.
            $table->text('password')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_affiliate')->default(false);
            $table->boolean('email_verified')->default(false);
            $table->text('photo_url')->nullable();
            $table->text('provider')->nullable();
            $table->text('google_id')->nullable();
            $table->text('reset_token')->nullable();
            $table->timestamp('reset_token_expiry')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->timestamp('last_login')->nullable();
        });

        Schema::create('pending_users', function (Blueprint $table) {
            $table->text('uid')->primary();
            $table->text('email');
            $table->text('name')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('hashed_password')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->text('verification_status')->default('pending');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // `users.uid` est du texte : la colonne de rattachement doit l'être aussi.
            $table->text('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('pending_users');
        Schema::dropIfExists('users');
    }
};
