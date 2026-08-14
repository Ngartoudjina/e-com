<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Codes promotionnels.
 *
 * Le seul code existant, ARCHIVES20, était écrit en dur dans CartView.vue :
 * lisible par quiconque ouvre le code livré au navigateur, et validé par
 * personne. Un visiteur pouvait aussi bien inventer sa propre remise en
 * modifiant l'état de la page.
 *
 * La remise est désormais calculée par le serveur au moment de la commande,
 * la saisie du panier ne servant qu'à l'afficher.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->text('id')->primary();
            // Toujours stocké en capitales : la saisie est insensible à la casse.
            $table->text('code')->unique();
            $table->text('label')->nullable();

            // 'percent' → value en pourcentage ; 'amount' → value en euros.
            $table->text('type')->default('percent');
            $table->double('value')->default(0);

            $table->double('min_subtotal')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Plafond d'utilisation, tous clients confondus. Null = illimité.
            $table->integer('max_uses')->nullable();
            $table->integer('used_count')->default(0);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
