# Orientation par événements de vie

Fonctionnalité d'aide au choix d'un titre de transport : au lieu de demander
« quel forfait voulez-vous ? », on part d'une **situation de vie** (« je deviens
étudiant », « je pars à la retraite »…). Un **arbre de décision** pose quelques
questions sur-mesure puis recommande **une ou plusieurs offres + les aides
applicables**, avec une **vérification d'éligibilité hybride** (FranceConnect /
API Particulier, ou dépôt de justificatif).

---

## Sommaire

1. [Vue d'ensemble](#vue-densemble)
2. [Modèle de données](#1-modèle-de-données-backend)
3. [Moteur d'orientation](#2-moteur-dorientation--api)
4. [Vérification d'éligibilité (hybride)](#3-vérification-déligibilité-hybride)
5. [Frontend](#4-frontend)
6. [Lancer & tester](#lancer--tester)
7. [Étendre l'arbre](#étendre-larbre-de-décision)
8. [Brancher les vrais services de l'État](#brancher-les-vrais-services-de-létat)

---

## Vue d'ensemble

```
Home (banderole)  ──►  /orientation
                         │
                         ▼
        ┌──────────────────────────────────────────────┐
        │ 1. Choix de l'événement de vie (cartes)        │
        │ 2. Questions pas-à-pas (barre de progression)  │
        │ 3. Recommandation : offres + aides + CTA       │
        │ 4. Vérification d'éligibilité (optionnelle)    │
        └──────────────────────────────────────────────┘
                         │  fetch (JSON / multipart)
                         ▼
   API Symfony  /api/orientation/*  (routes publiques)
                         │
        OrientationEngine ──► arbre en base (Doctrine)
        EligibilityService ─► State gateway + Document checker (simulés)
```

**Stack** : backend Symfony 7.4 (Doctrine ORM, PostgreSQL), frontend Next.js 16
(App Router, React 19, TypeScript strict, Tailwind 4). Tout tourne via
`docker-compose` (`postgres` + `api` + `web`).

**Principe clé** : l'arbre de décision est **stocké en base** (configurable) et le
moteur est **sans état** — le front renvoie à chaque étape l'état du parcours, le
moteur en déduit l'étape suivante.

---

## 1. Modèle de données (backend)

### Entités métier

| Entité | Rôle | Relations |
|---|---|---|
| `User` | Bénéficiaire (complétée : `prenom`, `nom`, `dateNaissance`, `situation`, `zoneGeo`, `statut`) | `ManyToOne` → `Payeur` (nullable), `OneToMany` → `Abonnement` |
| `Payeur` | Personne qui règle l'abonnement (peut différer du bénéficiaire) | `OneToMany` → `User` (bénéficiaires) |
| `Abonnement` | Souscription d'une offre | `ManyToOne` → `User` (bénéficiaire), `ManyToOne` → `Payeur` |

**Choix de relations**
- `User.payeur` *nullable*, `onDelete SET NULL` : un user a **0 ou 1** payeur ;
  s'il se paie lui-même, `payeur = null` (pas de doublon de ses données).
- `Abonnement.beneficiaire` *non nullable*, `onDelete CASCADE` : l'abonnement
  n'existe pas sans bénéficiaire.
- `Abonnement.payeur` *nullable*, `onDelete SET NULL` : conserve l'historique si
  le payeur est supprimé.

### Entités de l'arbre de décision

| Entité | Rôle |
|---|---|
| `EventScenario` | Événement de vie (code, label, description, icône, question initiale) |
| `Question` | Question d'un scénario (choix unique / multiple, ordre) |
| `Answer` | Réponse possible — porte la **transition** (question suivante **ou** recommandation) |
| `Recommendation` | Offres (`json`) + aides (`json`) + CTA + bloc `verification` (`json`) |

**Transitions** : pour une question à **choix unique**, c'est la **réponse** qui
décide de la suite ; pour un **choix multiple**, c'est la **question** (les
réponses servent alors de profil).

### Entité de vérification

| Entité | Rôle |
|---|---|
| `EligibilityCheck` | Trace d'une vérification : aide, méthode, statut, source, fichier, données certifiées (anonyme possible) |

### Enums (`src/Enum/`)
`SituationUtilisateur`, `StatutUtilisateur`, `StatutAbonnement`, `Periodicite`,
`MoyenPaiement`, `LienBeneficiaire`, `TypeQuestion`, `MethodeVerification`,
`StatutVerification` — tous des *backed enums* `string` mappés via `enumType:`,
avec une méthode `label()` réutilisable.

### Migrations
3 migrations Doctrine générées et appliquées :
1. champs métier `user` + tables `payeur` / `abonnement` ;
2. arbre : `event_scenario` / `question` / `answer` / `recommendation` ;
3. `eligibility_check` + colonne `recommendation.verification`.

---

## 2. Moteur d'orientation & API

La logique vit dans un **service dédié** `App\Service\OrientationEngine`
(jamais dans le contrôleur).

| Endpoint | Méthode | Entrée | Sortie |
|---|---|---|---|
| `/api/orientation/events` | `GET` | — | `{ events: [{code,label,description,icone}] }` |
| `/api/orientation/next` | `POST` | `{ scenario, currentQuestion?, answers[] }` | `{ type:"question", question }` **ou** `{ type:"recommendation", recommendation }` |

- `currentQuestion` absent ⇒ le moteur renvoie la **question initiale** du scénario.
- Sinon il résout la transition (réponse pour un choix unique, question pour un
  choix multiple) et renvoie la question suivante **ou** la recommandation finale.
- Les questions renvoient `etape` / `etapeMax` pour la **barre de progression**.

**Validation & erreurs** : DTO `OrientationNextRequest` + `ValidatorInterface`
(comme `AuthController`). Erreurs métier via `OrientationException`
→ `400` (entrée invalide), `404` (scénario/question inconnu), `422` (validation).

**Données seedées** (commande `app:orientation:seed`, idempotente) — 6 parcours :
`devenir_etudiant`, `enfant_scolaire`, `changer_travail`, `partir_retraite`,
`arriver_idf`, `tarifs_reduits`. Aides couvertes : Imagine R, tarif jeune, aide
bourse, solidarité (50/75 %/gratuité), prise en charge employeur 50 %, Liberté+,
Améthyste, familles nombreuses, invalidité (+ accompagnant), gratuité < 4 ans,
tarif enfant 4-10 ans, groupes / sorties scolaires.

> **Pourquoi la base plutôt qu'un fichier de config ?** L'arbre en base est
> requêtable et ouvre la voie à une future UI d'admin. Pour garder la souplesse
> d'édition d'un hackathon, l'arbre reste décrit dans un **tableau PHP lisible**
> (`SeedOrientationCommand::getTree()`) chargé par un builder générique en deux
> passes (création puis câblage des transitions).

---

## 3. Vérification d'éligibilité (hybride)

Quand une recommandation porte un bloc `verification`, le parcours propose de
confirmer l'éligibilité, selon **deux voies** :

1. **Voie État** — `FranceConnect` + `API Particulier` : récupère la donnée
   **certifiée à la source** (CAF, CROUS…) sans document.
2. **Voie justificatif** (repli) — dépôt d'un fichier contrôlé (`2D-Doc` / OCR)
   quand la donnée n'est pas exposée par l'API (ex. MDPH pour l'invalidité).

| Endpoint | Méthode | Entrée |
|---|---|---|
| `/api/orientation/eligibility/etat` | `POST` | `{ aideCode }` |
| `/api/orientation/eligibility/justificatif` | `POST` | `multipart` : `aideCode` + `document` |

**Architecture (couture remplaçable)**

```
EligibilityController
        │
        ▼
EligibilityService ──► StateEligibilityGateway (interface)  ──► SimulatedStateGateway
        │          └─► DocumentChecker        (interface)  ──► SimulatedDocumentChecker
        ▼
EligibilityCheck (persistance de la trace)
```

Les interfaces sont liées à leur implémentation simulée via `#[AsAlias]`.
`EligibilityService` valide le fichier (PDF/JPEG/PNG ≤ 5 Mo), le stocke dans
`var/uploads/justificatifs`, puis persiste un `EligibilityCheck`.

> **Simulation assumée** : FranceConnect, API Particulier et la vérification
> 2D-Doc officielle nécessitent une **habilitation administrative**. Les
> implémentations actuelles sont donc simulées (déterministes : un fichier nommé
> `faux*` / `invalide*` est refusé), mais l'architecture permet de brancher les
> vrais clients **sans toucher au reste du code**.

---

## 4. Frontend

| Fichier | Rôle |
|---|---|
| `lib/orientation.ts` | Client API typé (`fetchEvents`, `fetchNextStep`, `verifyViaState`, `verifyViaDocument`) + types |
| `components/home/OrientationBanner.tsx` | Banderole d'accroche sur la home (CTA → `/orientation`) |
| `app/orientation/page.tsx` | Route dédiée (même enveloppe que la home) |
| `components/orientation/OrientationWizard.tsx` | Orchestrateur du parcours (`useReducer`) |
| `components/orientation/QuestionStep.tsx` | Composant **réutilisable** question (choix unique/multiple) + barre de progression |
| `components/orientation/RecommendationView.tsx` | Écran recommandation (offres + aides + CTA) |
| `components/orientation/EligibilityPanel.tsx` | Vérification hybride (FranceConnect → repli upload) |

**Respect strict de la DA existante** : aucun nouveau design system — réutilise
les tokens de `lib/ui.ts` (`glass`, `glassTile`, `btnPrimary`, `chip`,
`field`, `iconBadge`, `sectionAccent`…), la palette CSS, l'animation `rise-in`
et la **librairie d'icônes existante** (mapping `icone → composant`).

**Qualité** : TypeScript strict, état local via `useReducer` (pas de store lourd),
commentaires en français, **mobile-first**. **Accessibilité** : inputs natifs
`radio`/`checkbox` (navigation clavier), `fieldset`/`legend`, `role="progressbar"`,
libellés `<label>` cliquables, statuts en `role="status"`/`alert`.

---

## Lancer & tester

```bash
# Démarrer toute la stack
docker compose up -d        # postgres:5433 · api:8000 · web:3000

# (Re)charger l'arbre de décision en base
docker compose exec api php bin/console app:orientation:seed

# Vérifier la cohérence du schéma
docker compose exec api php bin/console doctrine:schema:validate
```

**Démo** : http://localhost:3000 → banderole « Un changement dans votre vie ? »
→ `/orientation`.

Exemples d'API :

```bash
# Liste des événements de vie
curl -s http://localhost:8000/api/orientation/events

# Démarrer un parcours
curl -s -X POST http://localhost:8000/api/orientation/next \
  -H 'Content-Type: application/json' -d '{"scenario":"devenir_etudiant"}'

# Répondre à une question
curl -s -X POST http://localhost:8000/api/orientation/next \
  -H 'Content-Type: application/json' \
  -d '{"scenario":"devenir_etudiant","currentQuestion":"age","answers":["moins_26"]}'

# Vérifier l'éligibilité via l'État (auto)
curl -s -X POST http://localhost:8000/api/orientation/eligibility/etat \
  -H 'Content-Type: application/json' -d '{"aideCode":"solidarite_transport"}'

# Déposer un justificatif (repli)
curl -s -X POST http://localhost:8000/api/orientation/eligibility/justificatif \
  -F 'aideCode=famille_nombreuse' -F 'document=@justif.pdf;type=application/pdf'
```

Vérifications front (dans le conteneur `web`) :

```bash
docker compose exec web npx tsc --noEmit          # types
docker compose exec web npx eslint src            # lint
```

---

## Étendre l'arbre de décision

Tout se passe dans `apps/api/src/Command/SeedOrientationCommand.php`,
méthode `getTree()` :

```php
[
  'code' => 'mon_scenario',
  'label' => 'Mon événement de vie',
  'icone' => 'compass',            // doit exister dans le mapping ICONS du front
  'ordre' => 7,
  'questionInitiale' => 'q1',
  'questions' => [
    ['code' => 'q1', 'type' => 'single_choice', 'libelle' => '…', 'answers' => [
      ['code' => 'a', 'libelle' => '…', 'next' => 'q2'],   // → question suivante
      ['code' => 'b', 'libelle' => '…', 'reco' => 'reco1'], // → recommandation
    ]],
  ],
  'recommendations' => [
    ['code' => 'reco1', 'titre' => '…', 'offres' => [...], 'aides' => [...],
     // bloc optionnel pour activer la vérification d'éligibilité :
     'verification' => ['aideCode' => 'solidarite_transport', 'label' => '…',
                        'methodes' => ['france_connect', 'justificatif']]],
  ],
],
```

Puis relancer `app:orientation:seed` (purge + recharge, idempotent).

---

## Brancher les vrais services de l'État

Remplacer les implémentations simulées par des clients réels, **sans rien changer
d'autre** :

- `App\Service\Eligibility\StateEligibilityGateway` → client HTTP
  **API Particulier** (après habilitation DINUM), identité via **FranceConnect**.
- `App\Service\Eligibility\DocumentChecker` → vérification de la signature
  **2D-Doc** (ANTS) ou service d'analyse documentaire.

Le `#[AsAlias]` posé sur l'implémentation détermine celle injectée : il suffit de
le déplacer sur la nouvelle classe.
```
