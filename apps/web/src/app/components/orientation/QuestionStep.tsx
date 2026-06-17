"use client";

import { useState } from "react";
import { btnGhost, btnPrimary, sectionAccent } from "../../lib/ui";
import type { OrientationQuestion } from "../../lib/orientation";
import { ArrowRightIcon, CheckIcon } from "../home/icons";

type Props = {
  question: OrientationQuestion;
  /** Codes des réponses sélectionnées, validées par l'utilisateur. */
  onSubmit: (answers: string[]) => void;
  onBack: () => void;
  loading?: boolean;
};

/**
 * Étape de question du parcours d'orientation.
 * Gère les deux types : choix unique (radio) et choix multiple (cases à cocher).
 * Réinitialise sa sélection à chaque question grâce à la prop `key` posée par le parent.
 */
export function QuestionStep({ question, onSubmit, onBack, loading = false }: Props) {
  const [selected, setSelected] = useState<string[]>([]);
  const isMulti = question.type === "multi_choice";
  const progress = Math.min(
    100,
    Math.round((question.etape / Math.max(1, question.etapeMax)) * 100),
  );

  function toggle(code: string) {
    setSelected((current) => {
      if (isMulti) {
        return current.includes(code)
          ? current.filter((value) => value !== code)
          : [...current, code];
      }
      return [code];
    });
  }

  function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    if (selected.length === 0) {
      return;
    }
    onSubmit(selected);
  }

  return (
    <form onSubmit={handleSubmit} className="rise-in">
      {/* Barre de progression */}
      <div className="mb-6">
        <div className="mb-2 flex items-center justify-between text-sm text-muted">
          <span className={sectionAccent} aria-hidden="true" />
          <span aria-hidden="true">
            Étape {question.etape} sur {question.etapeMax}
          </span>
        </div>
        <div
          className="h-2 w-full overflow-hidden rounded-full bg-white/50"
          role="progressbar"
          aria-valuemin={0}
          aria-valuemax={100}
          aria-valuenow={progress}
          aria-label={`Progression : ${progress} %`}
        >
          <div
            className="h-full rounded-full bg-gradient-to-r from-idf-interaction to-idf-focus transition-[width] duration-300"
            style={{ width: `${progress}%` }}
          />
        </div>
      </div>

      <fieldset className="border-0 p-0">
        <legend className="mb-1 text-2xl font-bold tracking-tight text-anthracite sm:text-3xl">
          {question.libelle}
        </legend>
        <p className="mb-6 text-sm text-muted">
          {isMulti
            ? "Plusieurs réponses possibles."
            : "Sélectionnez une réponse pour continuer."}
        </p>

        <div className="grid gap-3" role={isMulti ? "group" : "radiogroup"}>
          {question.answers.map((answer) => {
            const checked = selected.includes(answer.code);
            return (
              <label
                key={answer.code}
                className={`flex cursor-pointer items-center gap-3 rounded-2xl border bg-white/55 px-4 py-4 text-base backdrop-blur-lg transition focus-within:ring-4 focus-within:ring-[rgba(25,114,210,0.18)] hover:bg-white/75 ${
                  checked
                    ? "border-idf-interaction text-anthracite shadow-[0_16px_40px_-28px_rgba(0,80,170,0.55)]"
                    : "border-white/60 text-anthracite/90"
                }`}
              >
                <input
                  type={isMulti ? "checkbox" : "radio"}
                  name="orientation-answer"
                  value={answer.code}
                  checked={checked}
                  onChange={() => toggle(answer.code)}
                  className="sr-only"
                />
                <span
                  aria-hidden="true"
                  className={`flex h-6 w-6 shrink-0 items-center justify-center border-2 transition ${
                    isMulti ? "rounded-md" : "rounded-full"
                  } ${
                    checked
                      ? "border-idf-interaction bg-idf-interaction text-white"
                      : "border-anthracite/25 bg-white/60"
                  }`}
                >
                  {checked ? <CheckIcon width={14} height={14} /> : null}
                </span>
                <span className="font-medium">{answer.libelle}</span>
              </label>
            );
          })}
        </div>
      </fieldset>

      <div className="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
        <button type="button" onClick={onBack} className={btnGhost} disabled={loading}>
          Précédent
        </button>
        <button
          type="submit"
          className={btnPrimary}
          disabled={selected.length === 0 || loading}
        >
          {loading ? "Chargement…" : "Continuer"}
          <ArrowRightIcon width={16} height={16} />
        </button>
      </div>
    </form>
  );
}
