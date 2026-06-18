import { glassTile, linkArrow, sectionAccent } from "../../lib/ui";
import { ArrowRightIcon } from "./icons";

type Access = {
  title: string;
  text: string;
  href: string;
  illu: string;
  illuAlt: string;
};

const ITEMS: Access[] = [
  {
    title: "Trouver mon forfait",
    text: "Un parcours guidé vous oriente vers le titre le plus adapté à vos trajets.",
    href: "#forfaits",
    illu: "/images/illustrations/illu-achetez-titre.svg",
    illuAlt: "",
  },
  {
    title: "Mes trajets",
    text: "Consultez vos trajets enregistrés, vérifiez les incidents et téléchargez vos justificatifs.",
    href: "/trajets",
    illu: "/images/illustrations/illu-app.svg",
    illuAlt: "",
  },
  {
    title: "Alertes trafic",
    text: "Consultez les notifications utiles sur les incidents, paiements et renouvellements.",
    href: "/notifications",
    illu: "/images/illustrations/illu-infos-trafic.svg",
    illuAlt: "",
  },
];

export function QuickAccess() {
  return (
    <section id="acces" className="mx-auto w-full max-w-6xl scroll-mt-20 px-4 py-14 sm:px-6">
      <span className={sectionAccent} aria-hidden="true" />
      <h2 className="text-2xl font-bold tracking-tight text-anthracite sm:text-3xl">
        Tout pour vos déplacements
      </h2>
      <p className="mt-2 max-w-xl text-muted">
        Les services essentiels de votre espace Comutitres, réunis au même endroit.
      </p>

      <div className="mt-8 grid gap-4 sm:grid-cols-3">
        {ITEMS.map(({ title, text, href, illu, illuAlt }) => (
          <a key={title} href={href} className={`${glassTile} group flex flex-col gap-3 p-5`}>
            <img
              src={illu}
              alt={illuAlt}
              aria-hidden="true"
              width={56}
              height={56}
              className="shrink-0"
              draggable={false}
            />
            <h3 className="text-lg font-semibold text-anthracite">{title}</h3>
            <p className="text-sm leading-relaxed text-muted">{text}</p>
            <span className={`${linkArrow} mt-auto pt-2 text-sm`}>
              Accéder
              <ArrowRightIcon
                width={15}
                height={15}
                className="transition-transform group-hover:translate-x-0.5"
              />
            </span>
          </a>
        ))}
      </div>
    </section>
  );
}
