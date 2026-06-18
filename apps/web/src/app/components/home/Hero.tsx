import { btnGhost } from "../../lib/ui";
import { ArrowRightIcon } from "./icons";

export function Hero() {
  return (
    <section className="mx-auto w-full max-w-6xl overflow-hidden px-4 pb-6 pt-10 sm:px-6 sm:pt-12">
      <div className="grid items-center gap-8 md:grid-cols-[1.15fr_0.85fr]">
        <div className="rise-in min-w-0">
          <span className="inline-flex items-center gap-2 rounded-full border border-border bg-surface px-3 py-1 text-xs font-semibold text-idf-interaction">
            Comutitres · Filiale Île-de-France Mobilités
          </span>

          <h1 className="mt-5 text-[2rem] font-bold leading-[1.05] tracking-tight text-anthracite min-[420px]:text-4xl sm:text-5xl">
            Souscrivez et gérez vos titres{" "}
            <span className="text-idf-interaction">Navigo</span>, simplement.
          </h1>

          <p className="mt-4 max-w-md text-base leading-relaxed text-muted">
            Un espace unifié pour réunir vos justificatifs, suivre vos démarches
            et renouveler votre abonnement — accessible à tous les Franciliens.
          </p>

          <div className="mt-7 flex flex-wrap gap-3">
            <a href="/simulateur" className={`${btnGhost} px-7 py-3`}>
              Simuler mon abonnement
              <ArrowRightIcon width={16} height={16} />
            </a>
            <a href="#acces" className={`${btnGhost} px-7 py-3`}>
              Découvrir les services
            </a>
          </div>
        </div>

        <div
          className="rise-in hidden min-w-0 items-end justify-center md:flex"
          style={{ animationDelay: "0.12s" }}
        >
          <img
            src="/images/illustrations/illu-passe-navigo.svg"
            alt="Passe Navigo"
            className="max-h-72 w-auto object-contain drop-shadow-sm lg:max-h-80"
            draggable={false}
          />
        </div>
      </div>
    </section>
  );
}
