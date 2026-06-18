"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import {
  calculer,
  euros,
  type NiveauTst,
  type Profil,
} from "../../lib/tarifs";
import { btnPrimary, glass, sectionAccent } from "../../lib/ui";
import { ArrowRightIcon, CheckIcon } from "../home/icons";

const PROFILS: { id: Profil; label: string }[] = [
  { id: "standard", label: "Standard" },
  { id: "etudiant", label: "Étudiant" },
  { id: "scolaire", label: "Scolaire" },
  { id: "junior", label: "Junior" },
  { id: "tst", label: "Solidarité (TST)" },
];

const NIVEAUX_TST: { id: NiveauTst; label: string }[] = [
  { id: "reduction50", label: "Réduction 50 %" },
  { id: "reduction75", label: "Solidarité 75 %" },
  { id: "gratuite", label: "Gratuité" },
];

const MAX_TRAJETS = 40;

function pill(active: boolean): string {
  return `rounded-full px-3.5 py-1.5 text-sm font-semibold transition ${
    active
      ? "bg-gradient-to-br from-idf-interaction to-idf-focus text-white shadow-[0_10px_22px_-12px_rgba(0,80,170,0.7)]"
      : "text-gray-dark hover:text-idf-interaction"
  }`;
}

export function Simulator() {
  const [trajets, setTrajets] = useState<number>(10);
  const [profil, setProfil] = useState<Profil>("standard");
  const [niveauTst, setNiveauTst] = useState<NiveauTst>("reduction75");
  const [employeur, setEmployeur] = useState<boolean>(false);

  const resultat = useMemo(
    () => calculer(profil, trajets, employeur, niveauTst),
    [profil, trajets, employeur, niveauTst],
  );

  return (
    <section className="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
      <header className="max-w-2xl">
        <span className={sectionAccent} aria-hidden="true" />
        <h1 className="text-3xl font-bold tracking-tight text-anthracite sm:text-4xl">
          Simulateur d&apos;économies
        </h1>
        <p className="mt-3 text-muted">
          Indiquez votre rythme de trajets : nous calculons le titre le moins cher pour
          vous, votre économie et le seuil à partir duquel l&apos;abonnement devient
          rentable.
        </p>
      </header>

      <div className="mt-8 grid gap-5 lg:grid-cols-[1fr_1.1fr]">
        <div className={`${glass} rise-in p-6 sm:p-8`}>
          <div className="space-y-7">
            <div>
              <div className="flex items-baseline justify-between">
                <label htmlFor="trajets" className="text-sm font-medium text-anthracite/80">
                  Trajets par semaine
                </label>
                <span className="text-2xl font-bold text-anthracite">{trajets}</span>
              </div>
              <input
                id="trajets"
                type="range"
                min={0}
                max={MAX_TRAJETS}
                value={trajets}
                onChange={(event) => setTrajets(Number(event.target.value))}
                className="mt-3 w-full accent-idf-interaction"
                aria-valuetext={`${trajets} trajets par semaine`}
              />
              <p className="mt-1 text-xs text-muted">
                Un aller-retour quotidien sur 5 jours = 10 trajets.
              </p>
            </div>

            <fieldset>
              <legend className="text-sm font-medium text-anthracite/80">Votre profil</legend>
              <div className="mt-2 flex flex-wrap gap-1.5">
                {PROFILS.map((item) => (
                  <button
                    key={item.id}
                    type="button"
                    onClick={() => setProfil(item.id)}
                    aria-pressed={profil === item.id}
                    className={pill(profil === item.id)}
                  >
                    {item.label}
                  </button>
                ))}
              </div>
            </fieldset>

            {profil === "tst" && (
              <fieldset>
                <legend className="text-sm font-medium text-anthracite/80">
                  Niveau de solidarité
                </legend>
                <div className="mt-2 flex flex-wrap gap-1.5">
                  {NIVEAUX_TST.map((item) => (
                    <button
                      key={item.id}
                      type="button"
                      onClick={() => setNiveauTst(item.id)}
                      aria-pressed={niveauTst === item.id}
                      className={pill(niveauTst === item.id)}
                    >
                      {item.label}
                    </button>
                  ))}
                </div>
              </fieldset>
            )}

            <label className="flex items-center justify-between gap-4 rounded-2xl border border-white/60 bg-white/40 px-4 py-3">
              <span className="text-sm font-medium text-anthracite/85">
                Remboursé à 50 % par mon employeur
              </span>
              <input
                type="checkbox"
                role="switch"
                checked={employeur}
                onChange={(event) => setEmployeur(event.target.checked)}
                className="h-5 w-5 shrink-0 accent-idf-interaction"
              />
            </label>
          </div>
        </div>

        <div className={`${glass} rise-in p-6 sm:p-8`} style={{ animationDelay: "0.1s" }}>
          <div className="flex items-start gap-3">
            <img
              src="/images/illustrations/illu-achetez-titre.svg"
              width={44}
              height={44}
              aria-hidden="true"
              className="shrink-0"
            />
            <div>
              <p className="text-xs uppercase tracking-wide text-muted">
                Le moins cher pour vous
              </p>
              <p className="text-2xl font-bold text-anthracite">{resultat.reco.nom}</p>
              <p className="text-sm text-muted">{resultat.reco.detail}</p>
            </div>
            <p className="ml-auto whitespace-nowrap text-right">
              <span className="text-2xl font-bold text-anthracite">
                {euros(resultat.reco.coutMensuel)}
              </span>
              <span className="block text-xs text-muted">/ mois</span>
            </p>
          </div>

          {resultat.economieMois > 0 && (
            <div className="mt-5 rounded-2xl border border-[rgba(0,125,68,0.25)] bg-[rgba(0,125,68,0.1)] px-4 py-3 text-success">
              <p className="text-sm font-semibold">
                Vous économisez {euros(resultat.economieMois)} / mois
              </p>
              <p className="text-xs">
                soit {euros(resultat.economieAn, 0)} / an par rapport aux tickets à l&apos;unité.
              </p>
            </div>
          )}

          {resultat.seuilSemaine !== null && (
            <p className="mt-4 text-sm text-muted">
              {resultat.seuilSemaine === 0
                ? "Rentable dès le premier trajet."
                : `L'abonnement devient rentable à partir de ${resultat.seuilSemaine} trajets / semaine.`}
            </p>
          )}

          <ul className="mt-5 space-y-2">
            {resultat.options.map((option) => {
              const estReco = option.id === resultat.reco.id;
              return (
                <li
                  key={option.id}
                  className={`flex items-center justify-between rounded-xl px-3 py-2.5 text-sm ${
                    estReco
                      ? "bg-idf-interaction/10 font-semibold text-anthracite"
                      : "text-anthracite/75"
                  }`}
                >
                  <span className="flex items-center gap-2">
                    {estReco && <CheckIcon width={16} height={16} className="text-success" />}
                    {option.nom}
                  </span>
                  <span>{euros(option.coutMensuel)} / mois</span>
                </li>
              );
            })}
          </ul>

          <Link href="/register" className={`${btnPrimary} mt-6 w-full`}>
            Souscrire {resultat.reco.nom}
            <ArrowRightIcon width={16} height={16} />
          </Link>

          <p className="mt-4 text-xs text-muted">
            Tarifs indicatifs issus de la présentation Comutitres. Estimation hors frais de
            dossier ; la souscription reste soumise aux conditions de chaque titre.
          </p>
        </div>
      </div>
    </section>
  );
}
