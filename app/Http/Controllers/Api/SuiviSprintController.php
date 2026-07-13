<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * État PARTAGÉ du tracker de sprints (public, sans authentification).
 *
 *   GET  /api/suivi-sprints  → état commun visible par tous (jamais le hash du code)
 *   POST /api/suivi-sprints  → { code, checked } : enregistre l'état.
 *
 * Le code à 6 chiffres est défini à la PREMIÈRE sauvegarde (puis exigé ensuite).
 * Stocké hashé dans un fichier JSON privé (storage/app, non exposé au web,
 * exclu du rsync de déploiement → survit aux déploiements).
 */
class SuiviSprintController extends Controller
{
    private function path(): string
    {
        return storage_path('app/suivi-sprints.json');
    }

    private function read(): array
    {
        $p = $this->path();
        if (! is_file($p)) {
            return ['code_hash' => null, 'checked' => [], 'updated_at' => null];
        }
        $d = json_decode((string) file_get_contents($p), true);

        return is_array($d) ? $d : ['code_hash' => null, 'checked' => [], 'updated_at' => null];
    }

    private function write(array $d): void
    {
        @file_put_contents($this->path(), json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function payload(array $d): array
    {
        return [
            'checked'     => (object) ($d['checked'] ?? []),
            'priorities'  => array_values($d['priorities'] ?? []),
            'updated_at'  => $d['updated_at'] ?? null,
            'locked'      => ! empty($d['code_hash']),        // volet validations (propriétaire)
            'prio_locked' => ! empty($d['prio_code_hash']),   // volet priorités (chef)
        ];
    }

    /** GET /api/suivi-sprints */
    public function show(): JsonResponse
    {
        return response()->json($this->payload($this->read()));
    }

    /**
     * POST /api/suivi-sprints
     *
     * Deux volets INDÉPENDANTS, chacun protégé par son propre code à 6 chiffres :
     *   { code, checked }        → validations « fait/pas fait » (propriétaire)
     *   { prio_code, priorities} → corrections prioritaires (chef)
     * Le code de chaque volet est posé à sa première sauvegarde, puis exigé.
     */
    public function save(Request $request): JsonResponse
    {
        $d = $this->read();

        // ── Volet 1 : validations (propriétaire) ──────────────────────────────
        if ($request->has('checked')) {
            $code = (string) $request->input('code', '');
            if (! preg_match('/^\d{6}$/', $code)) {
                return response()->json(['message' => 'Code à 6 chiffres requis.'], 422);
            }
            if (empty($d['code_hash'])) {
                $d['code_hash'] = Hash::make($code);
            } elseif (! Hash::check($code, $d['code_hash'])) {
                return response()->json(['message' => 'Code incorrect.'], 403);
            }
            $clean = [];
            foreach ((array) $request->input('checked', []) as $k => $v) {
                if (is_string($k) && preg_match('/^s\d+t\d+$/', $k) && $v) {
                    $clean[$k] = true;
                }
            }
            $d['checked'] = $clean;
        }

        // ── Volet 2 : priorités (chef) ────────────────────────────────────────
        if ($request->has('priorities')) {
            $pcode = (string) $request->input('prio_code', '');
            if (! preg_match('/^\d{6}$/', $pcode)) {
                return response()->json(['message' => 'Code priorité à 6 chiffres requis.'], 422);
            }
            if (empty($d['prio_code_hash'])) {
                $d['prio_code_hash'] = Hash::make($pcode);
            } elseif (! Hash::check($pcode, $d['prio_code_hash'])) {
                return response()->json(['message' => 'Code priorité incorrect.'], 403);
            }
            $pr = [];
            foreach ((array) $request->input('priorities', []) as $k) {
                if (is_string($k) && preg_match('/^s\d+t\d+$/', $k)) {
                    $pr[] = $k;
                }
            }
            $d['priorities'] = array_values(array_unique($pr));
        }

        $d['updated_at'] = now()->toIso8601String();
        $this->write($d);

        return response()->json($this->payload($d));
    }
}
