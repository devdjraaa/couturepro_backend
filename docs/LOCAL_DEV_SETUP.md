# Guide — Environnement de développement local

> **Objectif** : faire tourner CouturePro entièrement en local sans toucher aux données Railway.

---

## 🏗️ Prérequis

| Outil | Version minimale | Commande de vérification |
|-------|-----------------|--------------------------|
| PHP | 8.3+ | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 18+ | `node -v` |
| npm / yarn | — | `npm -v` |

**SQLite** est inclus dans PHP — pas besoin d'installer MySQL pour commencer.

---

## 📦 1. Backend (Laravel)

```bash
cd couturepro_backend

# Installer les dépendances
composer install

# Créer le fichier d'environnement local
cp .env.local.example .env

# Générer la clé applicative
php artisan key:generate

# Créer la base SQLite
touch database/database.sqlite

# Migrations + données de base
php artisan migrate --seed

# Lancer le serveur
php artisan serve
# → http://localhost:8000
```

> **Note mail** : par défaut (`MAIL_MAILER=log`), les OTP s'affichent dans `storage/logs/laravel.log`.  
> Cherche `"otp"` dans ce fichier pour récupérer le code.

---

## 🎨 2. Frontend

```bash
cd couturepro_frontend

# Installer les dépendances
npm install   # ou yarn

# Créer l'env local (pointe vers ton backend local, pas Railway)
cp .env.local.example .env.local

# Lancer en développement
npm run dev   # ou yarn dev
# → http://localhost:5173
```

> `.env.local` est ignoré par git → tes modifications ne partiront jamais sur Railway.

---

## 🔒 Pourquoi Railway n'est PAS affecté

| Mécanisme | Explication |
|-----------|-------------|
| **Variables Railway** | Configurées dans le dashboard Railway, jamais lues depuis le repo |
| **Backend `.env`** | Ignoré par git — chaque dev a le sien |
| **Frontend `.env.local`** | Pattern `*.local` est dans `.gitignore` — impossible de committer |
| **CORS** | `allowed_origins: ['*']` → localhost autorisé sans configuration |
| **Sanctum** | `localhost` et `127.0.0.1` inclus dans les domaines stateful par défaut |

---

## 🧪 Trouver l'OTP en local

Avec `MAIL_MAILER=log` :

```bash
grep -i "otp\|code\|token" storage/logs/laravel.log | tail -5
```

Ou ouvrir `storage/logs/laravel.log` et chercher le dernier email envoyé.

---

## 📱 Tester avec le mobile (optionnel)

Si tu veux tester l'APK sur un téléphone avec ton backend local :

1. Exposer le backend avec ngrok :
   ```bash
   ngrok http 8000
   # → https://xxxx.ngrok-free.app
   ```
2. Dans `couturepro_frontend/.env.local` :
   ```
   VITE_API_BASE_URL=https://xxxx.ngrok-free.app/api
   ```
3. Rebuilder l'APK avec `scripts/build-android.sh`

---

## ⚡ Démarrage rapide (tout en une fois)

```bash
# Terminal 1 — Backend
cd couturepro_backend && php artisan serve

# Terminal 2 — Frontend
cd couturepro_frontend && npm run dev
```

Ouvre http://localhost:5173 → l'app tourne 100% en local.
