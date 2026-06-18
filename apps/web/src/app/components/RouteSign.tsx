// Extension du type HTML pour l'attribut `route` utilisé par IDFMobility-Stylesheet.
declare module "react" {
  interface HTMLAttributes<T> {
    route?: string;
  }
}

type Props = {
  /** Code de ligne IDFM : "1"–"14" (métro), "A"–"E" (RER), "T1"–"T13" (tram), numéro de bus… */
  route: string;
  className?: string;
};

/**
 * Normalise les codes de lignes IDFM pour correspondre aux sélecteurs CSS de idfm-signs.css.
 * Ex: "a" → "A" (RER A), "t3a" → "T3a" (Tram T3a).
 */
function normalizeRouteCode(code: string): string {
  // Lettre seule minuscule → majuscule (RER : A-E, Transilien : H, J, K, L, N, P, R, U)
  if (/^[a-z]$/.test(code)) return code.toUpperCase();
  // Tram : commence par t/T suivi d'un chiffre → T majuscule + reste conservé
  if (/^t\d/i.test(code)) return "T" + code.slice(1);
  return code;
}

export function RouteSign({ route, className }: Props) {
  return <span route={normalizeRouteCode(route)} className={className} />;
}
