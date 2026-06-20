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

    /** GET /api/suivi-sprints */
    public function show(): JsonResponse
    {
        $d = $this->read();

        return response()->json([
            'checked'    => (object) ($d['checked'] ?? []),
            'updated_at' => $d['updated_at'] ?? null,
            'locked'     => ! empty($d['code_hash']),
        ]);
    }

    /** POST /api/suivi-sprints */
    public function save(Request $request): JsonResponse
    {
        $code = (string) $request->input('code', '');
        if (! preg_match('/^\d{6}$/', $code)) {
            return response()->json(['message' => 'Code à 6 chiffres requis.'], 422);
        }

        $d = $this->read();

        if (empty($d['code_hash'])) {
            $d['code_hash'] = Hash::make($code);          // première utilisation : on pose le code
        } elseif (! Hash::check($code, $d['code_hash'])) {
            return response()->json(['message' => 'Code incorrect.'], 403);
        }

        // On ne garde que des clés valides « sNtM » à true.
        $clean = [];
        $checked = $request->input('checked', []);
        if (is_array($checked)) {
            foreach ($checked as $k => $v) {
                if (is_string($k) && preg_match('/^s\d+t\d+$/', $k) && $v) {
                    $clean[$k] = true;
                }
            }
        }

        $d['checked']    = $clean;
        $d['updated_at'] = now()->toIso8601String();
        $this->write($d);

        return response()->json([
            'checked'    => (object) $clean,
            'updated_at' => $d['updated_at'],
            'locked'     => true,
        ]);
    }
}
