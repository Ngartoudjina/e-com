<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réglages modifiables depuis l'administration.
 *
 * Table clé/valeur volontairement simple : ces réglages sont peu nombreux,
 * hétérogènes et lus en bloc. Une colonne par réglage imposerait une
 * migration à chaque ajout.
 *
 * config/boutique.php reste la source des valeurs par défaut : une clé
 * absente d'ici retombe dessus. La table ne contient donc que les écarts
 * volontaires, ce qui rend un retour aux valeurs d'origine trivial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->text('key')->primary();
            // Toujours du JSON : un réglage peut être un nombre, une chaîne
            // ou une liste (les messages du bandeau).
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
