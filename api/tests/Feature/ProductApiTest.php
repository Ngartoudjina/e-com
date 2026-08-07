<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private function produit(array $attributs = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Casque audio',
            'price' => 25000.0,
            'description' => 'Un casque',
            'category' => 'Audio',
            'stock' => 5,
            'rating' => 4.5,
            'created_by' => 'uid-admin',
        ], $attributs));
    }

    /**
     * Le frontend type `mediaUrl` (front-vue/src/types/index.ts).
     * Si Eloquent renvoyait media_url, la grille produits se viderait sans erreur.
     */
    public function test_les_cles_sont_en_camel_case(): void
    {
        $this->produit(['media_url' => 'https://exemple.test/image.jpg']);

        $reponse = $this->getJson('/api/products')->assertOk();

        $produit = $reponse->json('products.0');

        $this->assertArrayHasKey('mediaUrl', $produit);
        $this->assertArrayHasKey('soldCount', $produit);
        $this->assertArrayHasKey('createdAt', $produit);
        $this->assertSame('https://exemple.test/image.jpg', $produit['mediaUrl']);

        $this->assertArrayNotHasKey('media_url', $produit);
        $this->assertArrayNotHasKey('sold_count', $produit);
    }

    public function test_created_by_n_est_jamais_expose(): void
    {
        $this->produit();

        $produit = $this->getJson('/api/products')->assertOk()->json('products.0');

        $this->assertArrayNotHasKey('createdBy', $produit);
        $this->assertArrayNotHasKey('created_by', $produit);
    }

    public function test_pagination_et_meta(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->produit(['name' => "Produit $i"]);
        }

        $reponse = $this->getJson('/api/products?limit=2&page=2')->assertOk();

        $reponse->assertJsonPath('pagination.currentPage', 2);
        $reponse->assertJsonPath('pagination.itemsPerPage', 2);
        $reponse->assertJsonPath('pagination.totalItems', 5);
        $reponse->assertJsonPath('pagination.totalPages', 3);
        $reponse->assertJsonPath('pagination.hasNextPage', true);
        $reponse->assertJsonPath('pagination.hasPreviousPage', true);
        $this->assertCount(2, $reponse->json('products'));
    }

    public function test_mode_all_renvoie_tout(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->produit();
        }

        $reponse = $this->getJson('/api/products?all=true')->assertOk();

        $reponse->assertJsonPath('pagination.isComplete', true);
        $reponse->assertJsonPath('meta.retrievedAll', true);
        $this->assertCount(3, $reponse->json('products'));
    }

    public function test_filtre_par_categories_multiples(): void
    {
        $this->produit(['category' => 'Audio']);
        $this->produit(['category' => 'Vidéo']);
        $this->produit(['category' => 'Cuisine']);

        $reponse = $this->getJson('/api/products?category=Audio,Cuisine')->assertOk();

        $this->assertCount(2, $reponse->json('products'));
    }

    public function test_recherche_par_prefixe(): void
    {
        $this->produit(['name' => 'Casque sans fil']);
        $this->produit(['name' => 'Enceinte']);

        $reponse = $this->getJson('/api/products?search=Cas')->assertOk();

        $this->assertCount(1, $reponse->json('products'));
        $this->assertSame('Casque sans fil', $reponse->json('products.0.name'));
    }

    public function test_les_jokers_de_recherche_sont_neutralises(): void
    {
        $this->produit(['name' => 'Casque']);
        $this->produit(['name' => 'Enceinte']);

        // Sans échappement, « % » remonterait tout le catalogue.
        $reponse = $this->getJson('/api/products?search=%')->assertOk();

        $this->assertCount(0, $reponse->json('products'));
    }

    public function test_tri_invalide_rejete(): void
    {
        $this->getJson('/api/products?sortBy=motDePasse')
            ->assertStatus(400)
            ->assertJsonPath('error', 'Champ de tri invalide. Utilisez : createdAt, name, price, rating, ou stock');
    }

    public function test_ordre_invalide_rejete(): void
    {
        $this->getJson('/api/products?order=aleatoire')->assertStatus(400);
    }

    public function test_limite_hors_bornes_rejetee(): void
    {
        $this->getJson('/api/products?limit=500')->assertStatus(400);
        $this->getJson('/api/products?page=0')->assertStatus(400);
    }

    public function test_tri_par_prix(): void
    {
        $this->produit(['name' => 'Cher', 'price' => 90000.0]);
        $this->produit(['name' => 'Bon marché', 'price' => 1000.0]);

        $reponse = $this->getJson('/api/products?sortBy=price&order=asc')->assertOk();

        $this->assertSame('Bon marché', $reponse->json('products.0.name'));
    }

    public function test_fiche_produit(): void
    {
        $p = $this->produit();

        $this->getJson("/api/products/{$p->getKey()}")
            ->assertOk()
            ->assertJsonPath('id', $p->getKey())
            ->assertJsonPath('name', 'Casque audio');
    }

    public function test_produit_inconnu_renvoie_404(): void
    {
        $this->getJson('/api/products/inexistant')
            ->assertStatus(404)
            ->assertJsonPath('error', 'Produit non trouvé');
    }

    /** `simple-all` et `count` ne doivent pas être capturés par la route `{id}`. */
    public function test_les_routes_nommees_priment_sur_le_parametre(): void
    {
        $this->produit();

        $this->getJson('/api/products/simple-all')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/products/count')->assertOk()->assertJsonPath('count', 1);
    }

    public function test_comptage_avec_filtre(): void
    {
        $this->produit(['category' => 'Audio']);
        $this->produit(['category' => 'Vidéo']);

        $this->getJson('/api/products/count?category=Audio')
            ->assertOk()
            ->assertJsonPath('count', 1);
    }
}
