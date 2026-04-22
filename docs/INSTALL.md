# SPRINT 1 — Instructions d'installation

## Étape 1 : Supprimer les fichiers Laravel par défaut

```bash
rm database/migrations/0001_01_01_000000_create_users_table.php
rm app/Models/User.php
```

---

## Étape 2 : Installer Sanctum

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

---

## Étape 3 : Copier les fichiers générés

Copier le contenu de ce sprint dans votre projet :

```
sprint1/
├── app/
│   ├── Enums/
│   │   └── AdminPermission.php          → app/Enums/AdminPermission.php
│   └── Models/
│       ├── NiveauConfig.php             → app/Models/NiveauConfig.php
│       ├── Proprietaire.php             → app/Models/Proprietaire.php
│       ├── Atelier.php                  → app/Models/Atelier.php
│       ├── Abonnement.php               → app/Models/Abonnement.php
│       ├── Paiement.php                 → app/Models/Paiement.php
│       ├── TransactionAbonnement.php    → app/Models/TransactionAbonnement.php
│       ├── EquipeMembre.php             → app/Models/EquipeMembre.php
│       ├── ParametresAtelier.php        → app/Models/ParametresAtelier.php
│       ├── CommunicationsConfig.php     → app/Models/CommunicationsConfig.php
│       ├── PointsFidelite.php           → app/Models/PointsFidelite.php
│       ├── PointsHistorique.php         → app/Models/PointsHistorique.php
│       ├── NotificationSysteme.php      → app/Models/NotificationSysteme.php
│       ├── Vetement.php                 → app/Models/Vetement.php
│       ├── Client.php                   → app/Models/Client.php
│       ├── Mesure.php                   → app/Models/Mesure.php
│       ├── Commande.php                 → app/Models/Commande.php
│       ├── QuotaMensuel.php             → app/Models/QuotaMensuel.php
│       ├── PhotoVip.php                 → app/Models/PhotoVip.php
│       ├── OtpToken.php                 → app/Models/OtpToken.php
│       ├── DemandeRecuperation.php      → app/Models/DemandeRecuperation.php
│       ├── Fonctionnalite.php           → app/Models/Fonctionnalite.php
│       ├── Admin.php                    → app/Models/Admin.php
│       ├── TicketSupport.php            → app/Models/TicketSupport.php
│       ├── TicketMessage.php            → app/Models/TicketMessage.php
│       ├── AdminAuditLog.php            → app/Models/AdminAuditLog.php
│       ├── NiveauConfigChangelog.php    → app/Models/NiveauConfigChangelog.php
│       ├── OffreSpeciale.php            → app/Models/OffreSpeciale.php
│       └── ListeNoire.php               → app/Models/ListeNoire.php
├── config/
│   └── auth.php                         → config/auth.php  (REMPLACER)
└── database/
    ├── migrations/
    │   └── 2026_04_21_000001_*.php ... 2026_04_21_000030_*.php
    └── seeders/
        ├── DatabaseSeeder.php           → database/seeders/DatabaseSeeder.php  (REMPLACER)
        ├── FonctionnalitesSeeder.php    → database/seeders/FonctionnalitesSeeder.php
        ├── NiveauxConfigSeeder.php      → database/seeders/NiveauxConfigSeeder.php
        ├── VetementsSeeder.php          → database/seeders/VetementsSeeder.php
        └── AdminSeeder.php              → database/seeders/AdminSeeder.php
```

---

## Étape 4 : Modifier bootstrap/app.php

Ajouter le middleware Sanctum dans `bootstrap/app.php` (Laravel 11+) :

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sanctum stateful domains (si besoin SPA)
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
```

---

## Étape 5 : Vérifier .env

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=couturepro
DB_USERNAME=root
DB_PASSWORD=votre_password
```

---

## Étape 6 : Lancer les migrations et seeders

```bash
php artisan migrate --seed
```

**Résultat attendu :**
```
Running migrations...
  2026_04_21_000001 ✓  create_niveaux_config_table
  2026_04_21_000002 ✓  create_proprietaires_table
  ...
  2026_04_21_000030 ✓  create_liste_noire_table

Running seeders...
  ✅ FonctionnalitesSeeder : 14 features insérées
  ✅ NiveauxConfigSeeder : 6 plans insérés
  ✅ VetementsSeeder : 20 templates système insérés
  ✅ AdminSeeder : super_admin créé
```

---

## Étape 7 : Vérification en Tinker

```bash
php artisan tinker
```

```php
// Vérifications critiques
App\Models\NiveauConfig::count();    // → 6
App\Models\Fonctionnalite::count();  // → 14
App\Models\Vetement::count();        // → 20
App\Models\Admin::count();           // → 1

// Test config JSON
App\Models\NiveauConfig::where('cle', 'premium_mensuel')->first()->config['max_clients_par_mois'];
// → 100

// Test relations
App\Models\Admin::first()->isSuperAdmin();  // → true

// Test ListeNoire helper
App\Models\ListeNoire::estBloque('email', 'test@example.com');  // → false
```

---

## ✅ Sprint 1 terminé → Passer au Sprint 2 (Auth)

Prochaine conversation Claude Code — coller le contenu du Sprint 2 de SPRINTS.md.
