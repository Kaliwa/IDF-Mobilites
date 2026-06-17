"use client";

import Link from "next/link";
import { useAuth } from "../../lib/auth-context";
import { btnGhost, btnPrimary, glass, iconBadge, linkArrow } from "../../lib/ui";
import { ArrowRightIcon, CardIcon, CheckIcon } from "./icons";

export function AccountCard() {
  const { user, loading } = useAuth();

  if (loading) {
    return <div className={`${glass} h-full min-h-[16rem] animate-pulse p-6`} aria-hidden="true" />;
  }

  if (user) {
    const initial = user.email.charAt(0).toUpperCase();
    return (
      <div className={`${glass} min-w-0 p-6 sm:p-7`}>
        <div className="flex items-center gap-3">
          <span className="flex h-12 w-12 items-center justify-center rounded-lg bg-idf-interaction text-lg font-bold text-white">
            {initial}
          </span>
          <div className="min-w-0">
            <p className="text-xs uppercase tracking-wide text-muted">Bonjour</p>
            <p className="truncate font-semibold text-anthracite">{user.email}</p>
          </div>
        </div>

        <div className="mt-5 rounded-lg border border-border bg-background p-4">
          <div className="flex items-center justify-between">
            <span className="text-sm font-medium text-anthracite/80">
              Statut de l&apos;abonnement
            </span>
            <span className="inline-flex items-center gap-1 rounded-full bg-warning/15 px-2.5 py-1 text-xs font-semibold text-warning">
              Aucun titre actif
            </span>
          </div>
          <p className="mt-2 text-xs leading-relaxed text-muted">
            Souscrivez à un forfait pour activer votre titre Navigo et profiter du
            renouvellement automatique.
          </p>
        </div>

        <div className="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
          <Link href="#forfaits" className={`${btnPrimary} text-sm`}>
            Souscrire un titre
          </Link>
          <Link href="#acces" className={`${btnGhost} text-sm`}>
            Mes attestations
          </Link>
        </div>
      </div>
    );
  }

  return (
      <div className={`${glass} min-w-0 p-6 sm:p-7`}>
      <span className={iconBadge}>
        <CardIcon width={22} height={22} />
      </span>
      <h2 className="mt-4 text-xl font-bold text-anthracite">Je gère ma carte Navigo</h2>
      <p className="mt-1.5 text-sm leading-relaxed text-muted">
        Accédez à votre espace pour souscrire, renouveler vos forfaits et obtenir vos
        attestations.
      </p>

      <ul className="mt-4 space-y-2 text-sm text-anthracite/80">
        {[
          "Souscription 100 % en ligne",
          "Justificatifs et attestations",
          "Suivi de dossier et SAV",
        ].map((item) => (
          <li key={item} className="flex items-center gap-2">
            <CheckIcon width={16} height={16} className="text-success" />
            {item}
          </li>
        ))}
      </ul>

      <div className="mt-5 flex flex-col gap-3 sm:flex-row">
        <Link href="/login" className={`${btnPrimary} text-sm`}>
          Mon espace
        </Link>
        <Link href="/register" className={`${linkArrow} justify-center self-center text-sm`}>
          Créer un compte
          <ArrowRightIcon width={16} height={16} />
        </Link>
      </div>
    </div>
  );
}
