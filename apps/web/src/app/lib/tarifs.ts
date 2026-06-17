export type Profil = "standard" | "etudiant" | "scolaire" | "junior" | "tst";
export type NiveauTst = "reduction50" | "reduction75" | "gratuite";

export const SEMAINES_PAR_MOIS = 52 / 12;
export const MOIS_PAR_AN = 12;

export const TARIFS = {
  ticketMetro: 2.55,
  liberte: 1.64,
  navigoMois: 90.8,
  navigoAnnuelAn: 11 * 90.8,
  imagineRAn: 392.3,
  imagineRJuniorAn: 24.8,
} as const;

const REDUCTION_TST: Record<NiveauTst, number> = {
  reduction50: 0.5,
  reduction75: 0.25,
  gratuite: 0,
};

export type Option = {
  id: string;
  nom: string;
  detail: string;
  coutMensuel: number;
  categorie: "trajet" | "abonnement";
};

export type Resultat = {
  options: Option[];
  reco: Option;
  baseline: Option;
  economieMois: number;
  economieAn: number;
  seuilSemaine: number | null;
};

function labelTst(niveau: NiveauTst): string {
  if (niveau === "reduction50") return "Réduction 50 %";
  if (niveau === "reduction75") return "Solidarité 75 %";
  return "Solidarité gratuité";
}

export function calculerOptions(
  profil: Profil,
  trajetsSemaine: number,
  employeur: boolean,
  niveauTst: NiveauTst,
): Option[] {
  const trajetsMois = trajetsSemaine * SEMAINES_PAR_MOIS;
  const partEmployeur = employeur ? 0.5 : 1;

  const options: Option[] = [
    {
      id: "tickets",
      nom: "Tickets à l'unité",
      detail: "Métro-Train-RER, 2,55 € / trajet",
      coutMensuel: trajetsMois * TARIFS.ticketMetro,
      categorie: "trajet",
    },
    {
      id: "liberte",
      nom: "Navigo Liberté+",
      detail: "1,64 € / trajet",
      coutMensuel: trajetsMois * TARIFS.liberte,
      categorie: "trajet",
    },
  ];

  if (profil === "tst") {
    options.push({
      id: "navigo-tst",
      nom: "Navigo Mois (TST)",
      detail: labelTst(niveauTst),
      coutMensuel: TARIFS.navigoMois * REDUCTION_TST[niveauTst] * partEmployeur,
      categorie: "abonnement",
    });
    return options;
  }

  options.push(
    {
      id: "navigo-mois",
      nom: "Navigo Mois",
      detail: "Illimité, 90,80 € / mois",
      coutMensuel: TARIFS.navigoMois * partEmployeur,
      categorie: "abonnement",
    },
    {
      id: "navigo-annuel",
      nom: "Navigo Annuel",
      detail: "12ᵉ mois offert",
      coutMensuel: (TARIFS.navigoAnnuelAn / MOIS_PAR_AN) * partEmployeur,
      categorie: "abonnement",
    },
  );

  if (profil === "etudiant" || profil === "scolaire") {
    options.push({
      id: "imagine-r",
      nom: profil === "etudiant" ? "Imagine R Étudiant" : "Imagine R Scolaire",
      detail: "392,30 € / an",
      coutMensuel: (TARIFS.imagineRAn / MOIS_PAR_AN) * partEmployeur,
      categorie: "abonnement",
    });
  }

  if (profil === "junior") {
    options.push({
      id: "imagine-r-junior",
      nom: "Imagine R Junior",
      detail: "24,80 € / an, -11 ans",
      coutMensuel: (TARIFS.imagineRJuniorAn / MOIS_PAR_AN) * partEmployeur,
      categorie: "abonnement",
    });
  }

  return options;
}

export function seuilRentabilite(
  profil: Profil,
  employeur: boolean,
  niveauTst: NiveauTst,
): number | null {
  const abonnements = calculerOptions(profil, 0, employeur, niveauTst).filter(
    (option) => option.categorie === "abonnement",
  );
  if (abonnements.length === 0) return null;

  const coutAbo = Math.min(...abonnements.map((option) => option.coutMensuel));
  if (coutAbo <= 0) return 0;

  const trajetsMois = coutAbo / TARIFS.liberte;
  return Math.ceil(trajetsMois / SEMAINES_PAR_MOIS);
}

export function calculer(
  profil: Profil,
  trajetsSemaine: number,
  employeur: boolean,
  niveauTst: NiveauTst,
): Resultat {
  const options = calculerOptions(profil, trajetsSemaine, employeur, niveauTst);
  const triees = [...options].sort((a, b) => a.coutMensuel - b.coutMensuel);
  const reco = triees[0];
  const baseline = options.find((option) => option.id === "tickets") ?? options[0];
  const economieMois = Math.max(0, baseline.coutMensuel - reco.coutMensuel);

  return {
    options: triees,
    reco,
    baseline,
    economieMois,
    economieAn: economieMois * MOIS_PAR_AN,
    seuilSemaine: seuilRentabilite(profil, employeur, niveauTst),
  };
}

export function euros(montant: number, decimales = 2): string {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: "EUR",
    minimumFractionDigits: decimales,
    maximumFractionDigits: decimales,
  }).format(montant);
}
