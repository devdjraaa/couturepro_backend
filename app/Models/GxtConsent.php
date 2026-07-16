<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// P202 / Espace Client v3 — consentement APDP d'un client (historisé : une ligne par mise à jour).
class GxtConsent extends Model
{
    use HasUuids;

    protected $table = 'gxt_consents';

    protected $fillable = [
        'client_id', 'cookie_consent', 'marketing_consent',
        'analytics_consent', 'personalization_consent',
        'version_politique', 'ip_hash',
    ];

    protected $casts = [
        'cookie_consent'          => 'boolean',
        'marketing_consent'       => 'boolean',
        'analytics_consent'       => 'boolean',
        'personalization_consent' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(GxtClient::class, 'client_id');
    }
}
