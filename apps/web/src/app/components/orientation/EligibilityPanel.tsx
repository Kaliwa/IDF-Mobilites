"use client";

import { useState } from "react";
import { btnPrimary, field, glass, iconBadge } from "../../lib/ui";
import {
  type EligibilityResult,
  type OrientationVerification,
  type VerificationStatut,
  verifyViaDocument,
  verifyViaState,
} from "../../lib/orientation";
import { Notice } from "../auth/Notice";
import { CheckIcon, DocumentIcon } from "../home/icons";

type Props = {
  verification: OrientationVerification;
};

// Couleurs de badge par statut, alignées sur les tokens de la DA.
const STATUT_STYLE: Record<VerificationStatut, string> = {
  valide: "bg-success/10 text-success border-success/25",
  en_attente: "bg-warning/10 text-[var(--warning)] border-warning/25",
  refuse: "bg-danger/10 text-danger border-danger/25",
};

/**
 * Panneau de vérification d'éligibilité (approche hybride) affiché sous une recommandation.
 * 1) Vérification automatique via FranceConnect + API Particulier (simulée) ;
 * 2) dépôt d'un justificatif en repli si la donnée n'est pas disponible.
 */
export function EligibilityPanel({ verification }: Props) {
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<EligibilityResult | null>(null);
  const [showUpload, setShowUpload] = useState(false);
  const [fileName, setFileName] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const canFranceConnect = verification.methodes.includes("france_connect");
  const isVerified = result?.statut === "valide";

  async function handleFranceConnect() {
    setLoading(true);
    setError(null);
    try {
      const res = await verifyViaState(verification.aideCode);
      setResult(res);
      // Si la donnée n'est pas disponible à la source, on propose le justificatif.
      setShowUpload(res.statut !== "valide");
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : "La vérification a échoué.");
    } finally {
      setLoading(false);
    }
  }

  async function handleUpload(file: File) {
    setLoading(true);
    setError(null);
    setFileName(file.name);
    try {
      const res = await verifyViaDocument(verification.aideCode, file);
      setResult(res);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : "Le justificatif n'a pas pu être vérifié.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <section className={`${glass} mt-6 p-5 sm:p-6`} aria-labelledby="verif-titre">
      <div className="flex items-center gap-3">
        <span className={iconBadge}>
          <DocumentIcon width={22} height={22} />
        </span>
        <div>
          <h2 id="verif-titre" className="text-lg font-semibold text-anthracite">
            Vérifier mon éligibilité
          </h2>
          <p className="text-sm text-muted">
            Confirmez {verification.label} pour activer le tarif.
          </p>
        </div>
      </div>

      {/* Résultat de la vérification */}
      {result ? (
        <div className="mt-5">
          <span
            className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-semibold ${STATUT_STYLE[result.statut]}`}
            role="status"
          >
            {result.statut === "valide" ? <CheckIcon width={14} height={14} /> : null}
            {result.statutLabel}
          </span>
          <p className="mt-2 text-sm text-muted">{result.message}</p>
          {result.source ? (
            <p className="mt-1 text-xs text-muted">Source : {result.source}</p>
          ) : null}
        </div>
      ) : null}

      {error ? (
        <div className="mt-4">
          <Notice tone="error">{error}</Notice>
        </div>
      ) : null}

      {/* Voie 1 : FranceConnect (tant que non vérifié) */}
      {!isVerified ? (
        <div className="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
          {canFranceConnect && !showUpload ? (
            <button
              type="button"
              onClick={() => void handleFranceConnect()}
              className={btnPrimary}
              disabled={loading}
            >
              {loading ? "Vérification…" : "Vérifier avec FranceConnect"}
            </button>
          ) : null}

          {/* Voie 2 : repli justificatif */}
          {showUpload || !canFranceConnect ? (
            <div className="flex flex-col gap-2">
              <label htmlFor="justificatif" className="text-sm font-medium text-anthracite">
                Déposer un justificatif (PDF, JPEG ou PNG, 5 Mo max)
              </label>
              <input
                id="justificatif"
                type="file"
                accept="application/pdf,image/jpeg,image/png"
                disabled={loading}
                onChange={(event) => {
                  const file = event.target.files?.[0];
                  if (file) {
                    void handleUpload(file);
                  }
                }}
                className={`${field} cursor-pointer file:mr-3 file:rounded-lg file:border-0 file:bg-idf-interaction/10 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-idf-interaction`}
              />
              {fileName ? (
                <p className="text-xs text-muted">Fichier : {fileName}</p>
              ) : null}
            </div>
          ) : null}
        </div>
      ) : null}
    </section>
  );
}
