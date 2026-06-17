"use client";

import Link from "next/link";
import { useMemo } from "react";
import { SiteFooter } from "../components/home/SiteFooter";
import { SiteHeader } from "../components/home/SiteHeader";
import { LineBadgeList } from "../components/trips/LineBadge";
import { AuthProvider } from "../lib/auth-context";
import { journeyLinesToSegments } from "../lib/line-segments";
import { useJourneys, formatJourneyLines } from "../lib/use-journeys";
import { btnGhost, btnPrimary, glass, sectionAccent } from "../lib/ui";

function shortenAddress(value: string, max = 72): string {
  return value.length > max ? `${value.slice(0, max)}…` : value;
}

function JourneysPageInner() {
  const {
    user,
    journeys,
    loadDisruptions,
    disruptions,
    plannedDisruptions,
    canGenerateJustificatif,
    disruptionsJourneyId,
    downloadJustificatif,
    checking,
    downloading,
    error,
    loading,
  } = useJourneys();

  const sorted = useMemo(
    () =>
      [...journeys].sort(
        (a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime(),
      ),
    [journeys],
  );

  return (
    <div className="home flex min-h-dvh flex-col">
      <SiteHeader />
      <main className="flex-1">
        <section className="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
          <p className="text-sm text-muted">
            <Link href="/" className="transition-colors hover:text-idf-interaction">
              Retour à l&apos;accueil
            </Link>
          </p>

          <div className="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <span className={sectionAccent} aria-hidden="true" />
              <h1 className="text-3xl font-bold text-anthracite">Mes trajets</h1>
              <p className="mt-2 max-w-2xl text-muted">
                Vérifiez les incidents en cours sur vos lignes et générez un justificatif uniquement
                si une panne ou interruption est active au moment de la demande.
              </p>
            </div>
            <Link href="/#creer-trajet" className={`${btnPrimary} justify-center`}>
              Créer un trajet
            </Link>
          </div>

          {!user ? (
            <div className={`${glass} mt-8 p-6 text-center`}>
              <p className="text-muted">Connectez-vous pour voir vos trajets enregistrés.</p>
              <div className="mt-4 flex flex-wrap justify-center gap-3">
                <Link href="/login" className={btnGhost}>
                  Connexion
                </Link>
                <Link href="/register" className={btnPrimary}>
                  Créer un compte
                </Link>
              </div>
            </div>
          ) : null}

          {error ? <p className="mt-4 text-sm text-warning">{error}</p> : null}

          {user && loading ? (
            <p className="mt-8 text-sm text-muted">Chargement de vos trajets…</p>
          ) : null}

          {user && !loading && sorted.length === 0 ? (
            <div className={`${glass} mt-8 p-8 text-center`}>
              <p className="text-lg font-semibold text-anthracite">Aucun trajet enregistré</p>
              <p className="mt-2 text-muted">
                Créez d&apos;abord un trajet depuis l&apos;accueil pour pouvoir vérifier les
                perturbations.
              </p>
              <Link href="/#creer-trajet" className={`${btnPrimary} mt-5 inline-flex`}>
                Créer mon premier trajet
              </Link>
            </div>
          ) : null}

          <div className="mt-8 grid gap-4 lg:grid-cols-2">
            {sorted.map((journey) => {
              const segments = journeyLinesToSegments(journey.lines);

              return (
                <article key={journey.id} className={`${glass} p-5`}>
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <h2 className="text-lg font-semibold text-anthracite">{journey.label}</h2>
                      <p className="mt-1 text-xs text-muted">
                        MAJ : {new Date(journey.updatedAt).toLocaleString("fr-FR")}
                      </p>
                    </div>
                  </div>

                  <dl className="mt-4 space-y-2 text-sm">
                    <div>
                      <dt className="text-muted">Origine</dt>
                      <dd className="font-medium text-anthracite">
                        {shortenAddress(journey.origin.name)}
                      </dd>
                    </div>
                    <div>
                      <dt className="text-muted">Destination</dt>
                      <dd className="font-medium text-anthracite">
                        {shortenAddress(journey.destination.name)}
                      </dd>
                    </div>
                    <div>
                      <dt className="mb-1 text-muted">Lignes</dt>
                      <dd>
                        {segments.length > 0 ? (
                          <LineBadgeList segments={segments} />
                        ) : (
                          <span className="font-medium text-anthracite">
                            {formatJourneyLines(journey.lines)}
                          </span>
                        )}
                      </dd>
                    </div>
                  </dl>

                  {disruptionsJourneyId === journey.id ? (
                    <div className="mt-4 space-y-3 text-sm">
                      {disruptions.length > 0 ? (
                        <div className="rounded-xl border border-warning/30 bg-warning/10 p-3">
                          <p className="font-semibold text-anthracite">Incidents en cours</p>
                          <ul className="mt-2 space-y-2 text-muted">
                            {disruptions.map((d) => (
                              <li key={`${d.id ?? d.line}-${d.updatedAt}`}>
                                <p className="font-semibold text-anthracite">
                                  {d.lineName ?? `Ligne ${d.line}`} — {d.status}
                                </p>
                                <p>{d.message}</p>
                              </li>
                            ))}
                          </ul>
                        </div>
                      ) : (
                        <p className="text-muted">Aucun incident en cours sur ce trajet.</p>
                      )}
                      {plannedDisruptions.length > 0 ? (
                        <p className="text-xs text-muted">
                          {plannedDisruptions.length} information(s) travaux à venir (non
                          éligibles au justificatif).
                        </p>
                      ) : null}
                    </div>
                  ) : (
                    <p className="mt-4 text-xs text-muted">Perturbations non vérifiées.</p>
                  )}

                  <div className="mt-4 flex flex-wrap gap-2">
                    <button
                      type="button"
                      className={btnGhost}
                      onClick={() => void loadDisruptions(journey.id)}
                      disabled={checking}
                    >
                      {checking && disruptionsJourneyId === journey.id
                        ? "Vérification..."
                        : "Vérifier perturbations"}
                    </button>
                    <button
                      type="button"
                      className={btnPrimary}
                      onClick={() => void downloadJustificatif(journey.id)}
                      disabled={
                        downloading ||
                        !(disruptionsJourneyId === journey.id && canGenerateJustificatif)
                      }
                      title={
                        disruptionsJourneyId === journey.id && canGenerateJustificatif
                          ? "Générer un justificatif"
                          : "Disponible uniquement en cas d'incident en cours"
                      }
                    >
                      {downloading ? "Génération..." : "Générer un justificatif"}
                    </button>
                  </div>
                </article>
              );
            })}
          </div>
        </section>
      </main>
      <SiteFooter />
    </div>
  );
}

export default function JourneysPage() {
  return (
    <AuthProvider>
      <JourneysPageInner />
    </AuthProvider>
  );
}
