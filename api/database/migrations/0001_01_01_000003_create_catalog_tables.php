<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue, affiliation et newsletter — calqués sur back/src/db/schema.js.
 *
 * Les identifiants sont des UUID stockés en texte (crypto.randomUUID() côté Node,
 * Str::uuid() côté Laravel) : ils restent interchangeables entre les deux backends.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('name');
            $table->double('price');
            $table->text('description');
            $table->double('rating')->default(0);
            $table->integer('stock')->default(0);
            $table->text('category');
            $table->integer('sold_count')->default(0);
            $table->text('media_url')->nullable();
            $table->text('media_public_id')->nullable();
            $table->text('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('category');
        });

        Schema::create('affiliates', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('uid')->unique();
            $table->text('affiliate_code')->unique();
            $table->text('referral_link')->nullable();
            $table->text('identity_card_url')->nullable();
            $table->double('commission_rate')->default(0.05);
            $table->double('total_earnings')->default(0);
            $table->integer('total_referrals')->default(0);
            $table->integer('referral_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('affiliate_id');
            $table->text('referred_user_id');
            $table->text('affiliate_code')->nullable();
            $table->timestamp('first_click_at')->nullable();
            $table->timestamp('last_click_at')->nullable();
            $table->timestamp('conversion_at')->nullable();
            $table->text('status')->default('pending');
            $table->json('orders')->default('[]');
            $table->double('total_value')->default(0);

            $table->index('affiliate_id');
            $table->index('referred_user_id');
        });

        Schema::create('affiliate_requests', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('uid');
            $table->text('reason');
            $table->text('identity_card_url');
            $table->text('identity_card_public_id')->nullable();
            $table->text('status')->default('pending');
            $table->timestamp('created_at')->useCurrent();

            $table->index('uid');
            $table->index('status');
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->text('id')->primary();
            $table->text('uid');
            $table->text('product_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index('uid');
            $table->index('product_id');
        });

        Schema::create('subscribers', function (Blueprint $table) {
            $table->text('email')->primary();
            $table->timestamp('subscribed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
        Schema::dropIfExists('likes');
        Schema::dropIfExists('affiliate_requests');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('affiliates');
        Schema::dropIfExists('products');
    }
};
