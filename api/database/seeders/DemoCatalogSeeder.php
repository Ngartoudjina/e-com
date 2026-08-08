<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Catalogue de démonstration, repris des pièces nommées dans les maquettes.
 *
 * Couvre volontairement les cas que la grille doit savoir afficher :
 * une pièce en promotion, une en stock faible, une épuisée.
 */
class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $pieces = [
            ['Manteau Ardenne', 490.00, 590.00, 'Manteaux', 8],
            ['Veste Bruyère', 380.00, null, 'Vestes', 3],
            ['Trench Estuaire', 620.00, null, 'Trenchs', 0],
            ['Blazer Cadran', 340.00, null, 'Vestes', 12],
            ['Caban Ostende', 450.00, null, 'Manteaux', 6],
            ['Cape Vosges', 520.00, null, 'Capes', 2],
            ['Parka Havre', 590.00, 690.00, 'Manteaux', 9],
            ['Manteau Colline', 470.00, null, 'Manteaux', 15],
            ['Doudoune Cime', 390.00, null, 'Doudounes', 4],
        ];

        foreach ($pieces as [$nom, $prix, $initial, $categorie, $stock]) {
            if (Product::where('name', $nom)->exists()) {
                continue;
            }

            Product::create([
                'name' => $nom,
                'price' => $prix,
                'description' => 'Coupe droite, épaules tombantes, fermeture croisée. Tissée en Italie, assemblée au Portugal dans un atelier familial.',
                'category' => $categorie,
                'stock' => $stock,
                'rating' => 4.8,
                'media_url' => 'https://picsum.photos/seed/'.rawurlencode($nom).'/800/1000',
            ]);
        }

        $this->command?->info('Catalogue : '.Product::count().' pièces.');
    }
}
