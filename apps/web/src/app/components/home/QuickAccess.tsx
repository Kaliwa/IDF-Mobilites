import type { ComponentType, SVGProps } from "react";
import { glassTile, iconBadge, linkArrow, sectionAccent } from "../../lib/ui";
import {
  ArrowRightIcon,
  ClockIcon,
  CompassIcon,
  DocumentIcon,
  LifeBuoyIcon,
} from "./icons";

type Access = {
  title: string;
  text: string;
  href: string;
  Icon: ComponentType<SVGProps<SVGSVGElement>>;
};

const ITEMS: Access[] = [
  {
    title: "Trouver mon forfait",
    text: "Un parcours guidé vous oriente vers le titre le plus adapté à vos trajets.",
    href: "#forfaits",
    Icon: CompassIcon,
  },
  {
    title: "Mes trajets",
    text: "Consultez vos trajets enregistrés, vérifiez les incidents et téléchargez vos justificatifs.",
    href: "/trajets",
    Icon: DocumentIcon,
  },
  {
    title: "Lignes suivies",
    text: "Retrouvez vos lignes favorites et choisissez vos canaux d'alerte.",
    href: "/notifications",
    Icon: ClockIcon,
  },
  {
    title: "Alertes trafic",
    text: "Consultez les notifications utiles sur les incidents, paiements et renouvellements.",
    href: "/notifications",
    Icon: LifeBuoyIcon,
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

      <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {ITEMS.map(({ title, text, href, Icon }) => (
          <a key={title} href={href} className={`${glassTile} group flex flex-col gap-3 p-5`}>
            <span className={iconBadge}>
              <Icon width={22} height={22} />
            </span>
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
