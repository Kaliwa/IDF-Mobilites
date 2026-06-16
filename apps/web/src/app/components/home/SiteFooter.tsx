import Link from "next/link";
import { Brand } from "../auth/Brand";

const COLUMNS = [
  {
    title: "Titres & tarifs",
    links: [
      { label: "Tous les titres", href: "#forfaits" },
      { label: "Tarifs réduits & solidarité", href: "#forfaits" },
      { label: "Attestations", href: "#acces" },
    ],
  },
  {
    title: "Mon compte",
    links: [
      { label: "Connexion", href: "/login" },
      { label: "Créer un compte", href: "/register" },
      { label: "Suivi de dossier", href: "#acces" },
    ],
  },
  {
    title: "Aide",
    links: [
      { label: "Aide et contacts", href: "https://www.iledefrance-mobilites.fr/" },
      { label: "Foire aux questions", href: "https://www.iledefrance-mobilites.fr/" },
      { label: "Service après-vente", href: "#aide" },
    ],
  },
];

const LEGAL = [
  { label: "Mentions légales", href: "https://www.iledefrance-mobilites.fr/" },
  { label: "CGV / CGVU", href: "https://www.iledefrance-mobilites.fr/" },
  { label: "Données personnelles", href: "https://www.iledefrance-mobilites.fr/" },
  { label: "Cookies", href: "https://www.iledefrance-mobilites.fr/" },
  { label: "Accessibilité", href: "https://www.iledefrance-mobilites.fr/" },
];

export function SiteFooter() {
  return (
    <footer id="aide" className="mt-8 scroll-mt-20 border-t border-white/60 bg-white/35 backdrop-blur">
      <div className="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <div className="grid gap-10 md:grid-cols-[1.2fr_repeat(3,1fr)]">
          <div>
            <Brand tone="dark" />
            <p className="mt-4 max-w-xs text-sm leading-relaxed text-muted">
              Opérateur de services mutualisés Navigo pour le compte d&apos;Île-de-France
              Mobilités et des transporteurs franciliens.
            </p>
          </div>

          {COLUMNS.map((column) => (
            <nav key={column.title} aria-label={column.title}>
              <h3 className="text-sm font-semibold text-anthracite">{column.title}</h3>
              <ul className="mt-3 space-y-2">
                {column.links.map((link) => (
                  <li key={link.label}>
                    <Link
                      href={link.href}
                      className="text-sm text-muted transition-colors hover:text-idf-interaction"
                    >
                      {link.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </nav>
          ))}
        </div>

        <div className="mt-10 flex flex-col gap-4 border-t border-white/60 pt-6 text-xs text-muted sm:flex-row sm:items-center sm:justify-between">
          <ul className="flex flex-wrap gap-x-5 gap-y-2">
            {LEGAL.map((item) => (
              <li key={item.label}>
                <Link href={item.href} className="transition-colors hover:text-idf-interaction">
                  {item.label}
                </Link>
              </li>
            ))}
          </ul>
          <p>© {2026} Comutitres — Données traitées dans le respect du RGPD.</p>
        </div>
      </div>
    </footer>
  );
}
