<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Origines autorisées à appeler l'API depuis un navigateur.
 *
 * Sans config/cors.php, Laravel appliquait son défaut `allowed_origins =>
 * ['*']` : n'importe quel site pouvait interroger l'API depuis le navigateur
 * d'un visiteur. Une configuration CORS se régresse sans bruit — rien ne
 * casse, la porte se rouvre simplement.
 */
class CorsTest extends TestCase
{
    private function prevol(string $origine): \Illuminate\Testing\TestResponse
    {
        return $this->call('OPTIONS', '/api/products', [], [], [], [
            'HTTP_ORIGIN' => $origine,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);
    }

    public function test_l_origine_du_site_est_admise(): void
    {
        // En dehors de la production, les ports de Vite sont ajoutés :
        // sans eux le catalogue resterait vide en développement, sans
        // erreur visible à l'écran.
        $reponse = $this->prevol('http://localhost:5173');

        $reponse->assertNoContent();
        $this->assertSame('http://localhost:5173', $reponse->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_une_origine_etrangere_est_refusee(): void
    {
        $reponse = $this->prevol('https://site-malveillant.example');

        $this->assertNull(
            $reponse->headers->get('Access-Control-Allow-Origin'),
            'Une origine inconnue ne doit recevoir aucune autorisation.'
        );
    }

    /** Le joker était le défaut du framework : il ne doit pas revenir. */
    public function test_aucune_origine_joker(): void
    {
        $this->assertNotContains('*', config('cors.allowed_origins'));
    }

    /**
     * L'authentification passe par un jeton en en-tête, jamais par un
     * cookie : autoriser les identifiants en cross-origin n'aurait aucune
     * utilité et élargirait la surface.
     */
    public function test_les_identifiants_ne_traversent_pas_l_origine(): void
    {
        $this->assertFalse(config('cors.supports_credentials'));
    }

    /** Seules les routes d'API sont concernées. */
    public function test_la_portee_se_limite_a_l_api(): void
    {
        $this->assertSame(['api/*'], config('cors.paths'));
    }
}
