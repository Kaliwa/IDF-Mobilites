"use client";

import Link from "next/link";
import { useState } from "react";
import { glass, linkArrow, sectionAccent } from "../../lib/ui";
import { ArrowRightIcon } from "./icons";

type Forfait = {
  name: string;
  price: string;
  unit: string;
  desc: string;
  tag?: string;
};

type Group = {
  key: string;
  label: string;
  intro: string;
  forfaits: Forfait[];
};

const GROUPS: Group[] = [
  {
    key: "frequents",
    label: "Trajets fréquents",
    intro: "Je voyage souvent : abonnements en illimité, renouvelables chaque année.",
    forfaits: [
      {
        name: "Navigo Annuel",
        price: "90,80 €",
        unit: "/ mois",
        desc: "Illimité toute l'année, toutes zones. 12ᵉ mois consécutif offert.",
        tag: "Le plus choisi",
      },
      {
        name: "Imagine R Étudiant",
        price: "392,30 €",
        unit: "/ an",
        desc: "Étudiants d'Île-de-France, frais de dossier inclus.",
      },
      {
        name: "Imagine R Scolaire",
        price: "392,30 €",
        unit: "/ an",
        desc: "Primaire, secondaire et apprentis. Frais de dossier inclus.",
      },
      {
        name: "Navigo Mois",
        price: "90,80 €",
        unit: "/ mois",
        desc: "Illimité tout au long du mois, recharge via l'app.",
      },
    ],
  },
  {
    key: "occasionnels",
    label: "Trajets occasionnels",
    intro: "Je voyage de temps en temps : payez à l'usage, sans engagement.",
    forfaits: [
      {
        name: "Navigo Liberté +",
        price: "dès 1,64 €",
        unit: "/ trajet",
        desc: "Paiement au trajet sur passe ou téléphone, toutes zones.",
        tag: "Sans engagement",
      },
      {
        name: "Navigo Semaine",
        price: "32,40 €",
        unit: "/ semaine",
        desc: "Illimité toute la semaine, toutes zones.",
      },
      {
        name: "Navigo Jour",
        price: "12,30 €",
        unit: "/ jour",
        desc: "Illimité une journée entière, toutes zones (hors aéroports).",
      },
      {
        name: "Tickets à l'unité",
        price: "dès 2,05 €",
        unit: "/ ticket",
        desc: "Bus-Tram 2,05 € · Métro-Train-RER 2,55 €. Achat via l'app.",
      },
    ],
  },
  {
    key: "reduits",
    label: "Tarifs réduits & solidaires",
    intro: "Sur conditions et justificatifs : réductions selon profil et ressources.",
    forfaits: [
      {
        name: "Imagine R Junior",
        price: "24,80 €",
        unit: "/ an",
        desc: "Pour les moins de 11 ans. Frais de dossier inclus.",
      },
      {
        name: "Navigo Senior",
        price: "Sur conditions",
        unit: "",
        desc: "Tarif dédié aux voyageurs seniors, bascule automatique à 62 ans.",
      },
      {
        name: "Solidarité Transport (TST)",
        price: "Sur conditions",
        unit: "",
        desc: "Réduction 50 %, Solidarité 75 % ou gratuité selon les ressources.",
      },
      {
        name: "Améthyste",
        price: "Sur conditions",
        unit: "",
        desc: "Forfait selon le département de résidence.",
      },
    ],
  },
];

export function Forfaits() {
  const [active, setActive] = useState<string>(GROUPS[0].key);
  const group = GROUPS.find((item) => item.key === active) ?? GROUPS[0];

  return (
    <section id="forfaits" className="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6 scroll-mt-20">
      <div className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <span className={sectionAccent} aria-hidden="true" />
          <h2 className="text-2xl font-bold tracking-tight text-anthracite sm:text-3xl">
            Nos titres & tarifs
          </h2>
          <p className="mt-2 max-w-xl text-muted">{group.intro}</p>
        </div>

        <div
          className="grid grid-cols-1 gap-1 rounded-lg border border-border bg-surface p-1 sm:grid-cols-2 lg:inline-flex lg:flex-wrap"
          role="tablist"
          aria-label="Type de trajet"
        >
          {GROUPS.map((item) => {
            const isActive = item.key === active;
            return (
              <button
                key={item.key}
                type="button"
                role="tab"
                aria-selected={isActive}
                onClick={() => setActive(item.key)}
                className={`rounded-md px-4 py-2 text-sm font-semibold transition-colors ${
                  isActive
                    ? "bg-idf-interaction text-white"
                    : "text-gray-dark hover:text-idf-interaction"
                }`}
              >
                {item.label}
              </button>
            );
          })}
        </div>
      </div>

      <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {group.forfaits.map((forfait) => (
          <article key={forfait.name} className={`${glass} flex flex-col p-5`}>
            {forfait.tag ? (
              <span className="mb-3 inline-flex w-fit items-center rounded-full bg-[rgba(0,125,68,0.12)] px-2.5 py-1 text-xs font-semibold text-[var(--success)]">
                {forfait.tag}
              </span>
            ) : (
              <span className="mb-3 h-[1.625rem]" aria-hidden="true" />
            )}

            <h3 className="text-lg font-semibold text-anthracite">{forfait.name}</h3>
            <p className="mt-2">
              <span className="text-2xl font-bold text-anthracite">{forfait.price}</span>
              {forfait.unit ? (
                <span className="ml-1 text-sm text-muted">{forfait.unit}</span>
              ) : null}
            </p>
            <p className="mt-2 flex-1 text-sm leading-relaxed text-muted">{forfait.desc}</p>

            <Link href="/register" className={`${linkArrow} mt-4 text-sm`}>
              Souscrire
              <ArrowRightIcon width={15} height={15} />
            </Link>
          </article>
        ))}
      </div>

      <p className="mt-6 text-xs text-muted">
        Tarifs indicatifs en vigueur selon la présentation Comutitres. La souscription est
        soumise aux conditions générales de vente et d&apos;utilisation propres à chaque
        titre.
      </p>
    </section>
  );
}
