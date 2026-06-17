import Link from "next/link";
import { btnGhost, chip, glass, iconBadge, sectionAccent } from "../../lib/ui";
import { ArrowRightIcon, CompassIcon } from "./icons";

// Situations mises en avant dans la banderole (renvoient toutes vers le parcours guidé).
const SITUATIONS = [
  "Je deviens étudiant",
  "Mon enfant entre au collège",
  "Je change de travail",
  "Je pars à la retraite",
  "J'arrive en Île-de-France",
  "J'ai droit à une réduction ?",
];

/**
 * Banderole d'accroche vers le parcours d'orientation par événement de vie.
 */
export function OrientationBanner() {
  return (
    <section id="orientation" className="mx-auto max-w-6xl scroll-mt-20 px-4 py-14 sm:px-6">
      <div className={`${glass} relative overflow-hidden p-7 sm:p-10`}>
        <div className="grid items-center gap-8 md:grid-cols-[1.2fr_0.8fr]">
          <div>
            <span className={sectionAccent} aria-hidden="true" />
            <div className="flex items-center gap-3">
              <span className={iconBadge}>
                <CompassIcon width={22} height={22} />
              </span>
              <p className="text-sm font-semibold uppercase tracking-wide text-idf-interaction">
                Nouveau · Parcours guidé
              </p>
            </div>

            <h2 className="mt-4 text-2xl font-bold leading-tight tracking-tight text-anthracite sm:text-3xl">
              Un changement dans votre vie&nbsp;?{" "}
              <span className="text-idf-interaction">On trouve votre offre.</span>
            </h2>
            <p className="mt-3 max-w-xl text-base leading-relaxed text-muted">
              Plutôt que de comparer tous les forfaits, partez de votre situation. En quelques
              questions, on vous oriente vers le titre adapté et les aides auxquelles vous avez
              droit.
            </p>

            <div className="mt-6">
              <Link href="/orientation" className={`${btnGhost} sm:px-7`}>
                Trouver mon offre en quelques questions
                <ArrowRightIcon width={16} height={16} />
              </Link>
            </div>
          </div>

          <div className="md:justify-self-end">
            <p className="mb-3 text-sm text-muted">Quelques situations possibles :</p>
            <ul className="flex flex-wrap gap-2">
              {SITUATIONS.map((situation) => (
                <li key={situation}>
                  <Link href="/orientation" className={chip}>
                    {situation}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </div>
    </section>
  );
}
