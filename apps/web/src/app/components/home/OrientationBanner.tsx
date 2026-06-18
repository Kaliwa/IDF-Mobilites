import Link from "next/link";
import { btnPrimary, chip, glass, sectionAccent } from "../../lib/ui";
import { ArrowRightIcon } from "./icons";

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
    <section id="orientation" className="mx-auto w-full max-w-6xl scroll-mt-20 px-4 pb-6 pt-12 sm:px-6 sm:pt-16">
      <div className={`${glass} relative overflow-hidden p-8 sm:p-12`}>
        <div className="grid items-center gap-10 md:grid-cols-[1.2fr_0.8fr]">
          <div>
            <span className={sectionAccent} aria-hidden="true" />
            <div className="flex items-center gap-3">
              <img
                src="/images/illustrations/illu-achetez-titre.svg"
                alt=""
                aria-hidden="true"
                width={40}
                height={40}
                className="shrink-0"
              />
              <p className="text-sm font-semibold uppercase tracking-wide text-idf-interaction">
                Parcours guidé
              </p>
            </div>

            <h2 className="mt-4 text-3xl font-bold leading-tight tracking-tight text-anthracite sm:text-4xl">
              Un changement dans votre vie&nbsp;?{" "}
              <span className="text-idf-interaction">Trouvez votre offre Navigo.</span>
            </h2>
            <p className="mt-4 max-w-xl text-base leading-relaxed text-muted">
              Plutôt que de comparer tous les forfaits, partez de votre situation. En quelques
              questions, on vous oriente vers le titre adapté et les aides auxquelles vous avez
              droit.
            </p>

            <div className="mt-7">
              <Link href="/orientation" className={`${btnPrimary} px-8 py-4 text-base sm:px-9`}>
                Trouver mon offre en quelques questions
                <ArrowRightIcon width={18} height={18} />
              </Link>
            </div>
          </div>

          <div className="md:justify-self-end">
            <p className="mb-3 text-sm text-muted">Votre situation, par exemple :</p>
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
