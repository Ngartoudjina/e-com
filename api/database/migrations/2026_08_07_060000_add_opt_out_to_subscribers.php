<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute `opt_out` à la table des abonnés.
 *
 * Le backend Node lisait et écrivait déjà ce champ (subscribe / unsubscribe),
 * mais il ne figurait pas dans schema.js : le désabonnement ne pouvait donc pas
 * fonctionner. La colonne est ajoutée ici pour que l'intention soit réalisable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->boolean('opt_out')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn('opt_out');
        });
    }
};
