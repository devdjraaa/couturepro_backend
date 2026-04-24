<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\NiveauConfig;
use App\Models\TransactionAbonnement;
use App\Traits\LogsAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    use LogsAdminAction;

    public function index(Request $request): JsonResponse
    {
        $transactions = TransactionAbonnement::with(['atelier.proprietaire', 'niveau', 'createdBy'])
            ->when($request->statut, fn($q, $s) => $q->where('statut', $s))
            ->when($request->canal,  fn($q, $c) => $q->where('canal', $c))
            ->when($request->atelier_id, fn($q, $id) => $q->where('atelier_id', $id))
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $admin = $this->adminUser();

        $data = $request->validate([
            'atelier_id' => ['required', 'uuid', 'exists:ateliers,id'],
            'niveau_cle' => ['required', 'string', 'exists:niveaux_config,cle'],
        ]);

        $niveau = NiveauConfig::where('cle', $data['niveau_cle'])->firstOrFail();

        $transaction = TransactionAbonnement::create([
            'code_transaction' => 'COUP-' . strtoupper(Str::random(8)),
            'atelier_id'       => $data['atelier_id'],
            'niveau_cle'       => $niveau->cle,
            'duree_jours'      => $niveau->duree_jours,
            'montant'          => $niveau->prix_xof,
            'devise'           => 'XOF',
            'canal'            => 'manuel',
            'statut'           => 'disponible',
            'created_by'       => $admin->id,
        ]);

        $this->audit($admin, 'transaction.create', 'transaction', $transaction->id, [
            'code'       => $transaction->code_transaction,
            'atelier_id' => $data['atelier_id'],
            'niveau'     => $niveau->cle,
        ], $request->ip());

        return response()->json($transaction->load(['niveau', 'atelier.proprietaire']), 201);
    }

    public function cancel(Request $request, TransactionAbonnement $transaction): JsonResponse
    {
        $admin = $this->adminUser();

        if ($transaction->statut !== 'disponible') {
            return response()->json(['message' => 'Seule une transaction disponible peut être annulée.'], 422);
        }

        $transaction->update(['statut' => 'annule']);

        $this->audit($admin, 'transaction.annuler', 'transaction', $transaction->id, [
            'code' => $transaction->code_transaction,
        ], $request->ip());

        return response()->json(['message' => 'Transaction annulée.']);
    }
}
