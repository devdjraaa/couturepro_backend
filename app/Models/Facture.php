<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Facture extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'atelier_id', 'numero', 'type', 'statut',
        'client_nom', 'client_telephone',
        'date_emission', 'date_echeance',
        'lignes', 'mode_paiement', 'gabarit', 'acompte', 'tva_taux',
        'code_tracage', 'notes',
        'dgi_pdf_path', 'emecef_code', 'emecef_qr_url', 'emecef_data', 'normalisee_at',
    ];

    protected $casts = [
        'lignes'        => 'array',
        'emecef_data'   => 'array',
        'acompte'       => 'decimal:2',
        'tva_taux'      => 'decimal:2',
        'date_emission' => 'date:Y-m-d',
        'date_echeance' => 'date:Y-m-d',
        'normalisee_at' => 'datetime',
    ];

    protected $appends = ['dgi_pdf_url', 'qr_code_url', 'total'];

    protected function dgiPdfUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->dgi_pdf_path ? url(Storage::url($this->dgi_pdf_path)) : null,
        );
    }

    // QR de normalisation renvoyé par e-MECeF (étape B). Null tant que non normalisée.
    protected function qrCodeUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->emecef_qr_url,
        );
    }

    // Total TTC calculé depuis les lignes (quantité × prix unitaire).
    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn () => collect($this->lignes ?? [])->sum(
                fn ($l) => (float) ($l['quantite'] ?? 0) * (float) ($l['prix_unitaire'] ?? 0)
            ),
        );
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }
}
