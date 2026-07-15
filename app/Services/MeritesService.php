<?php

namespace App\Services;

// P174-176 : calcule les mérites d'un créateur à partir de ses compteurs.
// Pour chaque catégorie : le niveau atteint (nom + numéro) et la liste complète des
// niveaux avec un drapeau `obtenu` (les non-obtenus s'affichent grisés côté front, P176).
class MeritesService
{
    /**
     * @param array<string,int> $compteurs  clés = 'source' de chaque catégorie
     *        (likes, avis, telechargements, commandes, vues, anciennete_mois)
     * @return array<int,array<string,mixed>>
     */
    public function pour(array $compteurs): array
    {
        $categories = config('merites.categories', []);
        $resultat   = [];

        foreach ($categories as $cle => $cat) {
            $valeur  = (int) ($compteurs[$cat['source']] ?? 0);
            $niveaux = $cat['niveaux'];

            // Niveau courant = le plus haut dont le seuil `min` est atteint.
            $niveauAtteint = $niveaux[0];
            foreach ($niveaux as $n) {
                if ($valeur >= $n['min']) {
                    $niveauAtteint = $n;
                }
            }

            $resultat[] = [
                'cle'            => $cle,
                'emoji'          => $cat['emoji'],
                'label'          => $cat['label'],
                'valeur'         => $valeur,
                'niveau'         => $niveauAtteint['niveau'],
                'nom'            => $niveauAtteint['nom'],
                'niveaux'        => array_map(fn ($n) => [
                    'niveau' => $n['niveau'],
                    'nom'    => $n['nom'],
                    'min'    => $n['min'],
                    'obtenu' => $valeur >= $n['min'],
                ], $niveaux),
            ];
        }

        return $resultat;
    }
}
