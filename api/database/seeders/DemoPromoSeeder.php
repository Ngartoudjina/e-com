<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

/**
 * Reprend le seul code jusqu'ici écrit en dur dans CartView.vue.
 * Il devient une ligne en base, validée par le serveur.
 */
class DemoPromoSeeder extends Seeder
{
    public function run(): void
    {
        if (PromoCode::where('code', 'ARCHIVES20')->exists()) {
            return;
        }

        PromoCode::create([
            'code' => 'ARCHIVES20',
            'label' => 'ARCHIVES20 appliqué — 20 % sur les archives',
            'type' => 'percent',
            'value' => 20,
            'min_subtotal' => 0,
            'active' => true,
        ]);

        $this->command?->info('Code promotionnel ARCHIVES20 créé.');
    }
}
