<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commandes et lignes de commande.
 *
 * Deux partis pris qui comptent :
 *
 * 1. Les lignes figent le nom, le prix unitaire et la variante au moment de
 *    l'achat. Un produit renommé, repricé ou supprimé ne doit pas réécrire
 *    l'historique — une facture émise reste ce qu'elle était.
 *
 * 2. `uid` est nullable : une commande peut être passée sans compte. C'est ce
 *    que permet le tunnel, dont l'étape d'identité ne demande qu'un e-mail.
 *    Le rattachement à un compte se fera plus tard sur l'adresse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->text('id')->primary();
            // Référence lisible communiquée au client : GS-24831.
            $table->text('reference')->unique();

            $table->text('uid')->nullable()->index();
            $table->text('email')->index();
            $table->text('phone')->nullable();

            $table->text('status')->default('pending')->index();

            // Montants figés à la commande, en euros.
            $table->double('subtotal')->default(0);
            $table->double('discount')->default(0);
            $table->double('shipping')->default(0);
            $table->double('total')->default(0);
            $table->text('currency')->default('EUR');
            $table->text('promo_code')->nullable();

            $table->text('shipping_method')->default('standard');
            $table->text('shipping_name')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('shipping_postal_code')->nullable();
            $table->text('shipping_city')->nullable();
            $table->text('shipping_country')->default('France');

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('order_id')->index();

            // Le produit peut disparaître du catalogue : la ligne lui survit.
            $table->text('product_id')->nullable()->index();

            $table->text('name');
            $table->text('reference')->nullable();
            $table->text('color')->nullable();
            $table->text('size')->nullable();
            $table->text('media_url')->nullable();

            $table->double('unit_price');
            $table->integer('quantity')->default(1);
            $table->double('line_total');

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
