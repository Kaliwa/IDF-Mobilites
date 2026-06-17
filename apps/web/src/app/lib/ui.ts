// Tokens d'interface — direction artistique Île-de-France Mobilités.
// Style institutionnel : surfaces blanches pleines, bordures nettes, bleu IDFM
// en aplat, angles sobres. Aucun effet « verre » (blur), aucun dégradé.

export const glass =
  "rounded-xl border border-border bg-surface shadow-sm";

export const glassTile =
  "rounded-xl border border-border bg-surface shadow-sm transition-colors duration-150 hover:border-idf-interaction hover:bg-idf-blue-light/15";

export const glassNav =
  "sticky top-0 z-50 border-b border-border bg-surface shadow-sm";

export const btnPrimary =
  "inline-flex items-center justify-center gap-2 rounded-lg bg-idf-interaction px-5 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-idf-focus active:bg-idf-focus disabled:cursor-not-allowed disabled:opacity-60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-idf-focus";

export const btnGhost =
  "inline-flex items-center justify-center gap-2 rounded-lg border border-idf-interaction bg-surface px-5 py-3 text-sm font-semibold text-idf-interaction transition-colors duration-150 hover:bg-idf-blue-light/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-idf-focus";

export const field =
  "w-full rounded-lg border border-border bg-surface px-4 py-3.5 text-base text-anthracite transition placeholder:text-muted/70 focus:border-idf-interaction focus:outline-none focus:ring-2 focus:ring-[rgba(0,100,176,0.25)]";

export const iconBadge =
  "inline-flex h-11 w-11 items-center justify-center rounded-lg bg-idf-interaction/10 text-idf-interaction";

export const linkArrow =
  "inline-flex items-center gap-1.5 font-semibold text-idf-interaction transition-colors hover:text-idf-focus";

export const chip =
  "inline-flex items-center gap-1.5 rounded-full border border-border bg-surface px-3.5 py-1.5 text-sm font-medium text-anthracite transition-colors hover:border-idf-interaction hover:text-idf-interaction";

export const sectionAccent =
  "mb-3 block h-1 w-10 rounded-full bg-idf-interaction";
