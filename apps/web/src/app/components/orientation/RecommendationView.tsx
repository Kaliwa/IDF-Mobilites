"use client";

import Link from "next/link";
import { btnGhost, btnPrimary, glass, glassTile, iconBadge, sectionAccent } from "../../lib/ui";
import type { OrientationRecommendation } from "../../lib/orientation";
import { ArrowRightIcon, CardIcon, CheckIcon } from "../home/icons";
import { EligibilityPanel } from "./EligibilityPanel";

type Props = {
  recommendation: OrientationRecommendation;
  onBack: () => void;
  onRestart: () => void;
};

/**
 * Écran final : offre(s) conseillée(s), aides applicables et appel à la souscription.
 */
export function RecommendationView({ recommendation, onBack, onRestart }: Props) {
  const ctaUrl = recommendation.ctaUrl ?? "/register";
  const ctaLabel = recommendation.ctaLabel ?? "Souscrire cette offre";

  return (
    <div className="rise-in">
      <span className={sectionAccent} aria-hidden="true" />
      <p className="text-sm font-semibold uppercase tracking-wide text-idf-interaction">
        Notre recommandation
      </p>
      <h1 className="mt-2 text-3xl font-bold tracking-tight text-anthracite sm:text-4xl">
        {recommendation.titre}
      </h1>
      {recommendation.description ? (
        <p className="mt-3 max-w-2xl text-base leading-relaxed text-muted">
          {recommendation.description}
        </p>
      ) : null}

      {/* Offres conseillées */}
      {recommendation.offres.length > 0 ? (
        <section className="mt-8" aria-labelledby="reco-offres">
          <h2 id="reco-offres" className="text-lg font-semibold text-anthracite">
            {recommendation.offres.length > 1 ? "Offres conseillées" : "Offre conseillée"}
          </h2>
          <div className="mt-4 grid gap-4 sm:grid-cols-2">
            {recommendation.offres.map((offre) => (
              <article key={offre.code} className={`${glassTile} flex flex-col gap-3 p-5`}>
                <span className={iconBadge}>
                  <CardIcon width={22} height={22} />
                </span>
                <h3 className="text-lg font-semibold text-anthracite">{offre.label}</h3>
                {offre.description ? (
                  <p className="text-sm leading-relaxed text-muted">{offre.description}</p>
                ) : null}
              </article>
            ))}
          </div>
        </section>
      ) : null}

      {/* Aides applicables */}
      {recommendation.aides.length > 0 ? (
        <section className={`${glass} mt-6 p-5 sm:p-6`} aria-labelledby="reco-aides">
          <h2 id="reco-aides" className="text-lg font-semibold text-anthracite">
            Aides applicables
          </h2>
          <ul className="mt-4 grid gap-4">
            {recommendation.aides.map((aide) => (
              <li key={aide.code} className="flex items-start gap-3">
                <span
                  aria-hidden="true"
                  className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[rgba(0,125,68,0.12)] text-[var(--success)]"
                >
                  <CheckIcon width={14} height={14} />
                </span>
                <div>
                  <p className="font-semibold text-anthracite">{aide.label}</p>
                  {aide.description ? (
                    <p className="text-sm leading-relaxed text-muted">{aide.description}</p>
                  ) : null}
                </div>
              </li>
            ))}
          </ul>
        </section>
      ) : null}

      {/* Vérification d'éligibilité (si l'offre le requiert) */}
      {recommendation.verification ? (
        <EligibilityPanel verification={recommendation.verification} />
      ) : null}

      {/* Actions */}
      <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
        <Link href={ctaUrl} className={`${btnPrimary} sm:px-7`}>
          {ctaLabel}
          <ArrowRightIcon width={16} height={16} />
        </Link>
        <button type="button" onClick={onBack} className={btnGhost}>
          Précédent
        </button>
        <button
          type="button"
          onClick={onRestart}
          className="text-sm font-semibold text-idf-interaction transition hover:text-idf-focus sm:ml-auto"
        >
          Recommencer le parcours
        </button>
      </div>
    </div>
  );
}
