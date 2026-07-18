<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Point 101 — Filigrane automatique appliqué à la PUBLICATION d'une réalisation
 * (pas à l'envoi). Utilise GD (extension présente sur l'environnement de déploiement).
 * Produit une copie filigranée ; l'original reste inchangé.
 */
class WatermarkService
{
    private const TEXTE = 'Gextimo · novafriq.africa';

    /**
     * Applique le filigrane sur une image du disque « public ».
     *
     * @return array{path:string,url:string}|null  chemin/URL de la copie filigranée
     */
    public function appliquer(string $pathRelatifPublic): ?array
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($pathRelatifPublic)) {
            return null;
        }

        [$img] = $this->charger($disk->path($pathRelatifPublic));
        if (! $img) {
            return null;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        $this->dessinerFiligrane($img, $w, $h);

        $dir  = trim(dirname($pathRelatifPublic), '.');
        $base = pathinfo($pathRelatifPublic, PATHINFO_FILENAME);
        $out  = ($dir ? $dir . '/' : '') . $base . '_wm.jpg';

        $outFull = $disk->path($out);
        @mkdir(dirname($outFull), 0775, true);
        imagejpeg($img, $outFull, 88);
        imagedestroy($img);

        return ['path' => $out, 'url' => url(Storage::url($out))];
    }

    /** Charge l'image selon son type réel. */
    private function charger(string $full): array
    {
        $info = @getimagesize($full);
        if (! $info) {
            return [null];
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => [@imagecreatefromjpeg($full)],
            IMAGETYPE_PNG  => [@imagecreatefrompng($full)],
            IMAGETYPE_WEBP => [function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($full) : null],
            default        => [null],
        };
    }

    /** Bandeau translucide en bas + marque, dimensionné selon l'image. */
    private function dessinerFiligrane($img, int $w, int $h): void
    {
        $barH = (int) max(36, $h * 0.07);
        $y0   = $h - $barH;

        $noir = imagecolorallocatealpha($img, 0, 0, 0, 78); // ~40% d'opacité
        imagefilledrectangle($img, 0, $y0, $w, $h, $noir);

        $blanc = imagecolorallocate($img, 255, 255, 255);
        $font  = $this->police();
        $marge = (int) ($barH * 0.4);

        if ($font) {
            $size = (int) max(11, $barH * 0.4);
            $bbox = imagettfbbox($size, 0, $font, self::TEXTE);
            $tw   = abs($bbox[2] - $bbox[0]);
            $tx   = max($marge, $w - $tw - $marge);
            $ty   = $y0 + (int) ($barH * 0.5) + (int) ($size * 0.42);
            imagettftext($img, $size, 0, $tx, $ty, $blanc, $font, self::TEXTE);
        } else {
            $f  = 5;
            $tw = imagefontwidth($f) * strlen(self::TEXTE);
            $th = imagefontheight($f);
            imagestring($img, $f, max($marge, $w - $tw - 10), $y0 + (int) (($barH - $th) / 2), self::TEXTE, $blanc);
        }
    }

    /** Première police TTF disponible (config, DejaVu système, ou police empaquetée). */
    private function police(): ?string
    {
        $candidats = [
            config('services.watermark_font'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            resource_path('fonts/DejaVuSans-Bold.ttf'),
        ];

        foreach ($candidats as $p) {
            if ($p && is_file($p)) {
                return $p;
            }
        }

        return null;
    }
}
