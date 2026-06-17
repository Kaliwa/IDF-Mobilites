import { AccountCard } from "./AccountCard";
import { btnGhost, btnPrimary, chip } from "../../lib/ui";
import { ArrowRightIcon } from "./icons";

export function Hero() {
  return (
    <section className="mx-auto w-full max-w-6xl px-4 pb-6 pt-12 sm:px-6 sm:pt-16">
      <div className="grid items-center gap-8 md:grid-cols-[1.1fr_0.9fr]">
        <div className="rise-in min-w-0">
          <span className="inline-flex max-w-full flex-wrap items-center gap-2 rounded-full border border-white/70 bg-white/50 px-3 py-1 text-xs font-semibold text-idf-interaction backdrop-blur">
            Comutitres · Filiale Île-de-France Mobilités
          </span>

          <h1 className="mt-5 max-w-full text-[2rem] font-bold leading-[1.05] tracking-tight text-anthracite min-[420px]:text-4xl sm:text-5xl">
            Souscrivez et gérez vos titres&nbsp;
            <span className="bg-gradient-to-r from-idf-interaction to-idf-focus bg-clip-text text-transparent">
              Navigo
            </span>
            , simplement.
          </h1>

          <p className="mt-4 max-w-md break-words text-base leading-relaxed text-muted">
            Un parcours unifié pour trouver le forfait le plus adapté à vos trajets,
            réunir vos justificatifs et suivre vos démarches — accessible à tous.
          </p>

          <div className="mt-7 flex flex-col gap-3 sm:flex-row">
            <a href="#forfaits" className={`${btnPrimary} justify-center sm:px-7`}>
              Trouver mon forfait
            </a>
            <a href="#acces" className={`${btnGhost} justify-center`}>
              Découvrir les services
              <ArrowRightIcon width={16} height={16} />
            </a>
          </div>

          <div className="mt-7">
            <p className="mb-2.5 text-sm text-muted">Quelle est votre fréquence de trajet&nbsp;?</p>
            <div className="flex flex-wrap gap-2">
              <a href="#forfaits" className={chip}>
                Trajets fréquents
              </a>
              <a href="#forfaits" className={chip}>
                Trajets occasionnels
              </a>
            </div>
          </div>
        </div>

        <div className="rise-in min-w-0" style={{ animationDelay: "0.12s" }}>
          <AccountCard />
        </div>
      </div>
    </section>
  );
}
