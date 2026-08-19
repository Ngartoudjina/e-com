<?php

namespace App\Services;

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use RuntimeException;

/**
 * Envoi de médias vers Cloudinary.
 *
 * Porté depuis back/src/services/uploadService.js : sharp est remplacé par
 * Intervention Image, avec les mêmes réglages (800x600 au plus, sans
 * agrandissement, WebP qualité 80).
 *
 * Le backend Node coupait le processus au démarrage si la configuration
 * Cloudinary manquait (`process.exit(1)`). On échoue plutôt à l'usage, pour
 * qu'une clé absente n'empêche pas le reste de l'API de fonctionner.
 */
class MediaService
{
    private const LARGEUR_MAX = 800;
    private const HAUTEUR_MAX = 600;
    private const QUALITE = 80;

    public function estConfigure(): bool
    {
        return (bool) (config('services.cloudinary.cloud_name')
            && config('services.cloudinary.api_key')
            && config('services.cloudinary.api_secret'));
    }

    /**
     * Optimise puis envoie une image. Renvoie l'URL sécurisée et l'identifiant public.
     *
     * @return array{url: string, publicId: string}
     */
    public function envoyerImage(UploadedFile $fichier, string $dossier = 'products'): array
    {
        $this->exigerConfiguration();

        $optimisee = $this->optimiser($fichier);

        $resultat = (new UploadApi())->upload($this->versDataUri($optimisee, 'image/webp'), [
            'folder' => $dossier,
            'resource_type' => 'image',
            'format' => 'webp',
        ]);

        return [
            'url' => (string) $resultat['secure_url'],
            'publicId' => (string) $resultat['public_id'],
        ];
    }

    /**
     * Envoie un média sans transformation : l'admin peut publier des vidéos,
     * qu'Intervention Image ne sait pas traiter.
     *
     * @return array{url: string, publicId: string}
     */
    public function envoyerMedia(UploadedFile $fichier, string $dossier = 'products'): array
    {
        $this->exigerConfiguration();

        if (str_starts_with((string) $fichier->getMimeType(), 'image/')) {
            return $this->envoyerImage($fichier, $dossier);
        }

        $resultat = (new UploadApi())->upload($fichier->getRealPath(), [
            'folder' => $dossier,
            'resource_type' => 'auto',
        ]);

        return [
            'url' => (string) $resultat['secure_url'],
            'publicId' => (string) $resultat['public_id'],
        ];
    }

    /** La suppression ne doit jamais faire échouer l'opération métier appelante. */
    public function supprimer(?string $publicId): bool
    {
        if (! $publicId || ! $this->estConfigure()) {
            return false;
        }

        try {
            (new UploadApi())->destroy($publicId);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    private function optimiser(UploadedFile $fichier): string
    {
        /*
         * API d'Intervention Image 4 : `read()` et `toWebp()` appartenaient
         * à la version 3. Le code les appelait encore, mais le chemin n'était
         * jamais emprunté faute d'identifiants Cloudinary : le premier envoi
         * réel a échoué en « Call to undefined method ».
         */
        $image = (new ImageManager(new Driver()))->decodePath($fichier->getRealPath());

        // `scaleDown` respecte le ratio et n'agrandit jamais une image plus petite,
        // équivalent du `fit: 'inside', withoutEnlargement: true` de sharp.
        $image->scaleDown(self::LARGEUR_MAX, self::HAUTEUR_MAX);

        return (string) $image->encode(new WebpEncoder(quality: self::QUALITE));
    }

    private function versDataUri(string $binaire, string $type): string
    {
        return 'data:'.$type.';base64,'.base64_encode($binaire);
    }

    private function exigerConfiguration(): void
    {
        if ($this->estConfigure()) {
            Configuration::instance([
                'cloud' => [
                    'cloud_name' => config('services.cloudinary.cloud_name'),
                    'api_key' => config('services.cloudinary.api_key'),
                    'api_secret' => config('services.cloudinary.api_secret'),
                ],
                'url' => ['secure' => true],
            ]);

            return;
        }

        throw new RuntimeException('Configuration Cloudinary incomplète');
    }
}
