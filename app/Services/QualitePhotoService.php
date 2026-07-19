<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * PHOTO-1 — Contrôle qualité automatique des photos envoyées.
 *
 * Analyse quatre critères à l'envoi : résolution, luminosité, netteté et cadrage.
 * L'analyse est SYNCHRONE (quelques dizaines de millisecondes sur une image
 * réduite) : le créateur a donc un retour immédiat et peut reprendre sa photo
 * autant de fois qu'il veut, sans pénalité ni file d'attente.
 *
 * Les problèmes sont renvoyés sous forme de CODES, jamais de phrases : l'interface
 * les traduit en icônes/couleurs, ce qui reste compréhensible quelle que soit la
 * langue de l'utilisateur (exigence de la direction : retour visuel uniquement).
 */
class QualitePhotoService
{
    /** Taille de travail pour l'analyse de netteté (compromis précision / rapidité). */
    private const TAILLE_ANALYSE = 320;

    /**
     * Seuils, éditables en admin sans redéploiement (`VitrineSetting` → `controle_photo`).
     *
     * ⚠️ `nettete_bloquante` est à FAUX par défaut, volontairement. La variance du
     * laplacien dépend fortement du contenu de l'image : sur nos essais, une photo
     * NETTE et réaliste mesure ~46 quand un damier très flouté dépasse 3 000. Un
     * seuil absolu non calibré sur de VRAIES photos Gextimo rejetterait donc des
     * photos légitimes — soit exactement le goulot d'étranglement à éviter.
     * → tant que le seuil n'est pas calibré sur un échantillon réel, la netteté
     *   est signalée comme un AVERTISSEMENT (retour visuel au créateur) mais ne
     *   bloque pas l'envoi. Passer `nettete_bloquante` à vrai une fois calibré.
     */
    public function seuils(): array
    {
        $cfg = \App\Models\VitrineSetting::where('cle', 'controle_photo')->value('valeur');

        return array_merge([
            'largeur_min'       => 600,
            'hauteur_min'       => 600,
            'luminosite_min'    => 40,
            'luminosite_max'    => 215,
            'ratio_min'         => 0.4,
            'ratio_max'         => 2.5,
            'nettete_min'       => 20.0,
            'nettete_bloquante' => false,
        ], is_array($cfg) ? $cfg : []);
    }

    /**
     * @return array{ok:bool, problemes:array<int,string>, mesures:array<string,mixed>}
     */
    public function analyser(string $pathPublic): array
    {
        $s    = $this->seuils();
        $vide = ['ok' => false, 'problemes' => ['illisible'], 'avertissements' => [], 'mesures' => []];

        $disk = Storage::disk('public');
        if (! $disk->exists($pathPublic)) {
            return $vide;
        }

        $full = $disk->path($pathPublic);
        $info = @getimagesize($full);
        if (! $info) {
            return $vide;
        }

        [$largeur, $hauteur] = $info;
        $problemes      = [];
        $avertissements = [];

        // 1) Résolution — critère objectif, bloquant.
        if ($largeur < $s['largeur_min'] || $hauteur < $s['hauteur_min']) {
            $problemes[] = 'resolution';
        }

        // 2) Cadrage (proportions extrêmes) — objectif, bloquant.
        $ratio = $hauteur > 0 ? $largeur / $hauteur : 0;
        if ($ratio < $s['ratio_min'] || $ratio > $s['ratio_max']) {
            $problemes[] = 'cadrage';
        }

        $img = $this->charger($full, $info[2]);
        if (! $img) {
            return $vide;
        }

        // Image de travail réduite en niveaux de gris.
        $gris = $this->reduireEnGris($img, $largeur, $hauteur);
        imagedestroy($img);

        $luminosite = $this->luminositeMoyenne($gris);
        $nettete    = $this->varianceLaplacien($gris);
        imagedestroy($gris);

        // 3) Luminosité (trop sombre ou surexposée) — objectif, bloquant.
        if ($luminosite < $s['luminosite_min'] || $luminosite > $s['luminosite_max']) {
            $problemes[] = 'luminosite';
        }

        // 4) Netteté — AVERTISSEMENT par défaut (voir seuils() : non calibré sur de
        //    vraies photos, un seuil absolu rejetterait des images légitimes).
        if ($nettete < $s['nettete_min']) {
            if ($s['nettete_bloquante']) {
                $problemes[] = 'nettete';
            } else {
                $avertissements[] = 'nettete';
            }
        }

        return [
            'ok'             => $problemes === [],
            'problemes'      => $problemes,
            'avertissements' => $avertissements,
            'mesures'        => [
                'largeur'    => $largeur,
                'hauteur'    => $hauteur,
                'luminosite' => round($luminosite, 1),
                'nettete'    => round($nettete, 1),
            ],
        ];
    }

    private function charger(string $full, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($full),
            IMAGETYPE_PNG  => @imagecreatefrompng($full),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($full) : null,
            default        => null,
        };
    }

    /** Réduit l'image et la convertit en niveaux de gris (analyse rapide et stable). */
    private function reduireEnGris($img, int $largeur, int $hauteur)
    {
        $echelle = min(self::TAILLE_ANALYSE / max($largeur, 1), self::TAILLE_ANALYSE / max($hauteur, 1), 1);
        $l = max(3, (int) round($largeur * $echelle));
        $h = max(3, (int) round($hauteur * $echelle));

        $petit = imagecreatetruecolor($l, $h);
        imagecopyresampled($petit, $img, 0, 0, 0, 0, $l, $h, $largeur, $hauteur);
        imagefilter($petit, IMG_FILTER_GRAYSCALE);

        return $petit;
    }

    private function luminositeMoyenne($gris): float
    {
        $l = imagesx($gris);
        $h = imagesy($gris);
        $somme = 0;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $l; $x++) {
                $somme += imagecolorat($gris, $x, $y) & 0xFF;   // canal bleu = niveau de gris
            }
        }

        return $somme / max(1, $l * $h);
    }

    /**
     * Variance du laplacien : mesure de netteté classique. Une image floue a peu
     * de variations locales, donc une variance faible.
     */
    private function varianceLaplacien($gris): float
    {
        $l = imagesx($gris);
        $h = imagesy($gris);

        $valeurs = [];
        for ($y = 1; $y < $h - 1; $y++) {
            for ($x = 1; $x < $l - 1; $x++) {
                $centre = imagecolorat($gris, $x, $y) & 0xFF;
                $lap = (imagecolorat($gris, $x - 1, $y) & 0xFF)
                     + (imagecolorat($gris, $x + 1, $y) & 0xFF)
                     + (imagecolorat($gris, $x, $y - 1) & 0xFF)
                     + (imagecolorat($gris, $x, $y + 1) & 0xFF)
                     - 4 * $centre;
                $valeurs[] = $lap;
            }
        }

        $n = count($valeurs);
        if ($n === 0) {
            return 0.0;
        }

        $moyenne = array_sum($valeurs) / $n;
        $variance = 0.0;
        foreach ($valeurs as $v) {
            $variance += ($v - $moyenne) ** 2;
        }

        return $variance / $n;
    }
}
