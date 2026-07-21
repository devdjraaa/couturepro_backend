# Faire tourner les tests

## Pourquoi PostgreSQL et pas SQLite

La suite était configurée sur SQLite en mémoire. Elle ne démarrait pas : plusieurs
migrations du projet suppriment des clés étrangères par nom, ce que le pilote SQLite
ne sait pas faire. Autrement dit, **aucun test avec base ne pouvait tourner**, et
personne ne s'en était aperçu parce que la suite ne contenait qu'un test d'exemple.

Le remplacement par PostgreSQL n'est pas qu'un dépannage. La **production tourne sous
PostgreSQL** : tester sous un autre moteur laisse passer les défauts propres au moteur.
Deux exemples rencontrés le 20/07, tous deux invisibles côté PostgreSQL et donc jamais
détectés en production :

- `fonctionnalites.ordre_affichage` déclarée `tinyInteger` (plafond 127) alors que le
  référentiel assigne déjà 133 ;
- une migration écrite en SQL brut `ALTER COLUMN … DROP NOT NULL`, syntaxe que seul
  PostgreSQL comprend.

## Mise en place (une fois)

PostgreSQL doit tourner :

```bash
sudo systemctl start postgresql
sudo systemctl enable postgresql   # PAS facultatif : sans cela le service ne
                                   # redémarre pas après un redémarrage de la
                                   # machine, et toute la suite échoue sur
                                   # « connection refused » — une erreur qui ne
                                   # dit rien de la vraie cause.
```

Créer le rôle et la base **dédiée** :

```bash
sudo -u postgres psql -c "CREATE ROLE couturepro_user LOGIN PASSWORD '<mot-de-passe>';"
sudo -u postgres createdb -O couturepro_user couturepro_test
```

Puis créer `.env.testing` (non versionné) **en partant d'une copie de `.env`** :

```bash
cp .env .env.testing
```

et n'y remplacer que ceci :

```
APP_ENV=testing
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=couturepro_test
DB_USERNAME=couturepro_user
DB_PASSWORD=<mot-de-passe>
MAIL_MAILER=array        # aucun e-mail ne doit sortir pendant les tests
QUEUE_CONNECTION=sync
CACHE_STORE=array
SESSION_DRIVER=array
```

> ⚠️ **Partir d'une copie, pas d'un fichier réduit.** Laravel **remplace** `.env`
> par `.env.testing`, il ne le complète pas. Un fichier ne contenant que les
> réglages de base laisse l'application sans `APP_KEY`, et tout test touchant au
> chiffrement, aux sessions ou aux cookies échoue sur `MissingAppKeyException` —
> une erreur qui ne dit rien de la vraie cause.

> ⚠️ La base doit être `couturepro_test`, **jamais** `couturepro`. `RefreshDatabase`
> repart d'une base vide : pointer la suite sur la base de travail l'effacerait.

## Lancer

```bash
php artisan test                              # tout
php artisan test --filter=NomDuTest           # un fichier
```

## Écrire un test

- Avec base : `use RefreshDatabase;`. Un compte admin de test doit porter
  `role => 'super_admin'` (les routes de modération sont derrière
  `admin.permission:…`) **et** `is_active => true` (`AdminAuth` refuse un compte
  inactif par un 403 — cause la plus fréquente d'un test qui échoue sans raison
  apparente).
- Sans base : certaines garanties se testent en instanciant le modèle sans
  l'enregistrer, ce qui rend le test instantané. Voir
  `ModerationRetouchePhotoTest`, dont la moitié des cas fonctionne ainsi.
- Fichiers : `Storage::fake('public')` avant tout envoi, sinon les tests écrivent
  dans le vrai `storage/`.
