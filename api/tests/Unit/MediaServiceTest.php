<?php

namespace Tests\Unit;

use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Optimisation des visuels avant envoi à Cloudinary.
 *
 * Ce chemin n'avait aucun test, et n'était jamais emprunté faute
 * d'identifiants Cloudinary. Le premier envoi réel a donc échoué en
 * « Call to undefined method Intervention\Image\ImageManager::read() » :
 * le code suivait l'API de la version 3, alors que la 4 est installée.
 */
class MediaServiceTest extends TestCase
{
    private function optimiser(UploadedFile $fichier): string
    {
        $methode = new ReflectionMethod(MediaService::class, 'optimiser');
        $methode->setAccessible(true);

        return $methode->invoke(new MediaService(), $fichier);
    }

    private function imageTemporaire(int $largeur, int $hauteur): UploadedFile
    {
        $chemin = tempnam(sys_get_temp_dir(), 'essai').'.png';

        $toile = imagecreatetruecolor($largeur, $hauteur);
        imagefilledrectangle($toile, 0, 0, $largeur, $hauteur, imagecolorallocate($toile, 40, 80, 160));
        imagepng($toile, $chemin);
        imagedestroy($toile);

        return new UploadedFile($chemin, basename($chemin), 'image/png', null, true);
    }

    public function test_l_image_est_convertie_en_webp(): void
    {
        $binaire = $this->optimiser($this->imageTemporaire(200, 150));

        // En-tête d'un fichier WebP : « RIFF » puis « WEBP » à l'octet 8.
        $this->assertSame('RIFF', substr($binaire, 0, 4));
        $this->assertSame('WEBP', substr($binaire, 8, 4));
    }

    public function test_une_grande_image_est_reduite_sans_deformation(): void
    {
        // 2000x1000 déborde des 800x600 : la largeur touche la limite en
        // premier, la hauteur suit le ratio.
        $binaire = $this->optimiser($this->imageTemporaire(2000, 1000));

        [$largeur, $hauteur] = getimagesizefromstring($binaire);

        $this->assertSame(800, $largeur);
        $this->assertSame(400, $hauteur);
    }

    /** `scaleDown` ne doit jamais agrandir une image plus petite que la limite. */
    public function test_une_petite_image_n_est_pas_agrandie(): void
    {
        $binaire = $this->optimiser($this->imageTemporaire(120, 90));

        [$largeur, $hauteur] = getimagesizefromstring($binaire);

        $this->assertSame(120, $largeur);
        $this->assertSame(90, $hauteur);
    }
}
