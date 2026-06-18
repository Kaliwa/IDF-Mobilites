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

- `idf-mobilites-db` : PostgreSQL (plan gratuit)
- `idf-mobilites-api` : Symfony (Dockerfile prod dans `apps/api`)
- `idf-mobilites-web` : Next.js (Dockerfile prod dans `apps/web`)
- `idf-mobilites-mercure` : hub temps reel

### Etapes

1. Pousse le repo sur GitHub ou GitLab.
2. Sur [Render](https://render.com), cree un **Blueprint** et pointe vers ce depot.
3. Lors de la creation, renseigne les secrets demandes :
   - `IDFM_API_KEY` (donnees IDFM / lignes)
   - `PRIM_API_TOKEN` (itineraires Navitia)
   - `ADMIN_EMAIL` et `ADMIN_PASSWORD` (compte backoffice Sonata)
4. Attends la fin du deploiement (migrations + seed orientation au demarrage de l'API).
5. Ouvre l'URL du service `idf-mobilites-web`.

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

- Frontend : URL du service `idf-mobilites-web`
- API : URL du service `idf-mobilites-api` (`/api` pour la doc)
- Admin : URL du service `idf-mobilites-api` + `/admin/login`
- Healthcheck API : `/api/health`

### Limites du plan gratuit Render

- Les services web s'endorment apres ~15 min d'inactivite (cold start au reveil).
- La base PostgreSQL gratuite expire au bout de 90 jours (a migrer ensuite).
- 3 services web gratuits : chacun a son quota d'heures mensuel.

Le developpement local continue d'utiliser `docker compose` avec `Dockerfile.dev`.
