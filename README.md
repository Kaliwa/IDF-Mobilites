# IDF Mobilites

Monorepo mobile-first avec :

- `apps/web` : Next.js 16 + TypeScript + Tailwind CSS
- `apps/api` : Symfony 7 API-only avec API Platform
- `docker-compose.yml` : stack complète `web` + `api` + `postgres`

## Gestion de projet

Suivi Kanban et User Stories : [GitHub Projects — IDF Mobilités](https://github.com/users/Kaliwa/projects/8)

## Prerequis

- Docker
- Docker Compose

## Structure

```text
apps/
  api/
  web/
docker-compose.yml
package.json
pnpm-workspace.yaml
README.md
```

## Demarrage local

Lancer toute la stack :

```bash
docker compose up --build
```

Ou via le script racine :

```bash
pnpm docker:up
```

Arreter la stack :

```bash
docker compose down
```

Consulter les logs :

```bash
docker compose logs -f
```

## URLs utiles

- Frontend : http://localhost:3000
- API : http://localhost:8000
- API docs : http://localhost:8000/api
- Admin : http://localhost:8000/admin/login
- Umami : http://localhost:3002 (`admin` / `umami`)
- GlitchTip : http://localhost:8080
- PostgreSQL : localhost:5433

## Fonctionnalites

- [Orientation par evenements de vie](docs/ORIENTATION.md) : parcours guide
  (arbre de decision configurable) vers une offre + aides, avec verification
  d'eligibilite hybride (FranceConnect / API Particulier ou justificatif).

## Notes

- Le conteneur `api` installe les dépendances Composer puis cree la base si besoin au demarrage.
- Le conteneur `web` installe les dependances pnpm puis lance Next.js en mode developpement.
- Les volumes `api_vendor`, `api_var`, `web_node_modules` et `web_next` evitent de reinstaller a chaque redemarrage.

## Deploiement Render

Le fichier `render.yaml` a la racine decrit toute la stack :

- `idf-mobilites-db` : **une seule** PostgreSQL (limite plan gratuit Render)
- `idf-mobilites-api` : Symfony → base `idf_mobilites`
- `idf-mobilites-web` : Next.js
- `idf-mobilites-mercure` : temps reel
- `idf-mobilites-umami` : analytics → base `umami` (meme serveur Postgres)
- `idf-mobilites-glitchtip` + Redis : erreurs → base `glitchtip` (meme serveur Postgres)

### Une seule base Postgres (plan gratuit)

Render n'autorise qu'**une** base gratuite. Umami et GlitchTip utilisent le **meme serveur**
`idf-mobilites-db` avec des bases logiques separees (`umami`, `glitchtip`).

**Etape obligatoire avant Umami/GlitchTip :**

1. Render → `idf-mobilites-db` → **Connect** → ouvre PSQL
2. Execute le contenu de `scripts/render-init-databases.sql` :
   ```sql
   CREATE DATABASE umami;
   CREATE DATABASE glitchtip;
   ```
3. Copie l'**Internal Database URL** de `idf-mobilites-db` (format :
   `postgresql://idf_mobilites:PASS@dpg-xxx-a/idf_mobilites`)
4. Derive deux URLs en changeant uniquement le nom de base :
   - Umami : `.../umami` (meme host, user, mot de passe)
   - GlitchTip : `.../glitchtip`
5. Render → `idf-mobilites-umami` → Environment → `DATABASE_URL` = URL umami
6. Render → `idf-mobilites-glitchtip` → Environment → `DATABASE_URL` = URL glitchtip
7. Redeploie umami et glitchtip

### Etapes initiales

1. Pousse le repo sur GitHub.
2. Render → **Sync Blueprint** pour creer/mettre a jour les services.
3. Renseigne les secrets au sync (ou apres dans Environment) :
   - `IDFM_API_KEY`, `PRIM_API_TOKEN`
   - `ADMIN_EMAIL`, `ADMIN_PASSWORD`
   - `DATABASE_URL` sur **umami** et **glitchtip** (voir ci-dessus)
4. Attends que les services soient `Live`.

### Configurer Umami (analytics)

1. Ouvre `https://idf-mobilites-umami.onrender.com`
2. Login : `admin` / `umami` → change le mot de passe
3. **Settings → Websites → Add website**
   - Domain : `idf-mobilites-web.onrender.com`
4. Copie le **Website ID** (UUID)
5. Render → `idf-mobilites-web` → Environment :
   - `NEXT_PUBLIC_UMAMI_WEBSITE_ID` = l'UUID
6. **Manual Deploy** sur `idf-mobilites-web` (rebuild obligatoire)

`NEXT_PUBLIC_UMAMI_URL` est deja lie a l'instance Umami Render via le Blueprint.

### Configurer GlitchTip (erreurs)

1. Ouvre `https://idf-mobilites-glitchtip.onrender.com`
2. Cree un compte (inscription activee au 1er deploy)
3. Cree une **organisation** + un **projet**
4. Copie le **DSN** du projet (format Sentry, ex. `https://key@idf-mobilites-glitchtip.onrender.com/1`)
5. Render → `idf-mobilites-web` → Environment :
   - `NEXT_PUBLIC_SENTRY_DSN` = le DSN GlitchTip
6. (Optionnel) Render → `idf-mobilites-api` → `SENTRY_DSN` = meme DSN pour l'API Symfony plus tard
7. **Manual Deploy** sur `idf-mobilites-web`

Page de test : `/glitchtip-test` sur le frontend.

### Backoffice admin (Sonata)

L'admin n'est pas un service separe : il est servi par l'API Symfony.

- URL prod : `https://idf-mobilites-api.onrender.com/admin/login`
- URL locale : http://localhost:8000/admin/login
- Compte cree au deploy si `ADMIN_EMAIL` / `ADMIN_PASSWORD` sont definis sur Render

En local, creer un compte admin :

```bash
docker compose exec api php bin/console app:users:create-support admin@example.com 'Motdepasse12' --admin
```

### URLs utiles en prod

- Frontend : `idf-mobilites-web`
- API : `idf-mobilites-api`
- Admin : `idf-mobilites-api` + `/admin/login`
- Umami : `idf-mobilites-umami`
- GlitchTip : `idf-mobilites-glitchtip`

### Limites du plan gratuit Render

- Les services web s'endorment apres ~15 min d'inactivite (cold start).
- **1 seule** base PostgreSQL gratuite (expire au bout de 90 jours).
- **6 services web** partagent le quota gratuit (~750 h/mois au total).
- GlitchTip : worker Celery dans le meme conteneur web (pas de background worker gratuit).

Le developpement local continue d'utiliser `docker compose` avec `Dockerfile.dev`.
