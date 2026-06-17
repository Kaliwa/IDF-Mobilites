# IDF Mobilites

Monorepo mobile-first avec :

- `apps/web` : Next.js 16 + TypeScript + Tailwind CSS
- `apps/api` : Symfony 7 API-only avec API Platform
- `docker-compose.yml` : stack complète `web` + `api` + `postgres`

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
