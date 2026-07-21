<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// P202 / Espace Client v3 — client final de la vitrine (auth sans mot de passe : Google ou OTP e-mail).
class GxtClient extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $table = 'gxt_clients';

    protected $fillable = [
        'nom', 'prenom', 'email', 'telephone_whatsapp', 'google_id',
        'utm_source', 'utm_medium', 'utm_campaign', 'referrer_url',
        'appareil', 'systeme_os', 'navigateur', 'pays', 'ville', 'langue',
        'date_naissance', 'derniere_connexion_at',
        // Lot 2 (20/07) : consentements DISTINCTS — la politique est obligatoire,
        // la newsletter est facultative et n'a aucun effet sur l'usage du service.
        'privacy_policy_accepted', 'privacy_policy_accepted_at', 'privacy_policy_version',
        'newsletter_opt_in', 'newsletter_opt_in_at',
    ];

    protected $casts = [
        'date_naissance'             => 'date',
        'derniere_connexion_at'      => 'datetime',
        'privacy_policy_accepted'    => 'boolean',
        'privacy_policy_accepted_at' => 'datetime',
        'newsletter_opt_in'          => 'boolean',
        'newsletter_opt_in_at'       => 'datetime',
    ];

    /**
     * Version courante de la politique de confidentialité. Sert à tracer CE qui
     * a été accepté : si le texte change, on saura qui doit se prononcer à
     * nouveau. Éditable en admin via VitrineSetting.
     */
    public static function versionPolitique(): string
    {
        return (string) (VitrineSetting::where('cle', 'politique_version')->value('valeur')['version'] ?? '1.0');
    }

    /** Enregistre le consentement à la politique, horodaté et versionné. */
    public function accepterPolitique(): void
    {
        $this->update([
            'privacy_policy_accepted'    => true,
            'privacy_policy_accepted_at' => now(),
            'privacy_policy_version'     => self::versionPolitique(),
        ]);
    }

    /**
     * Lien de désinscription à placer dans CHAQUE e-mail d'actualités.
     * Signé et valable 90 jours : un lien qui expire avant l'ouverture du
     * message piégerait la personne dans une liste qu'elle veut quitter.
     */
    public function lienDesinscription(): string
    {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'vitrine.desinscription',
            now()->addDays(90),
            ['client' => $this->id],
        );
    }

    /** Bascule l'inscription aux actualités (désinscription = date effacée). */
    public function definirNewsletter(bool $optIn): void
    {
        $this->update([
            'newsletter_opt_in'    => $optIn,
            'newsletter_opt_in_at' => $optIn ? now() : null,
        ]);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(GxtConsent::class, 'client_id');
    }

    /** Dernier consentement enregistré (source de vérité pour l'interrupteur tracking). */
    public function dernierConsentement(): HasOne
    {
        // PAS latestOfMany() : il ajoute un tie-breaker MAX(id), or `id` est un
        // UUID et PostgreSQL n'a pas de fonction max(uuid) → 500 dès qu'on lit ce
        // consentement (connexion, /me). Même contournement que Atelier::abonnement :
        // un simple tri décroissant sur created_at, sans agrégat.
        return $this->hasOne(GxtConsent::class, 'client_id')->latest();
    }
}
