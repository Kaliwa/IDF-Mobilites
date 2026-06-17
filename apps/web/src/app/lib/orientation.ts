import { API_BASE_URL, getErrorMessage, readJson, type ApiError } from "./auth";

// --- Types miroir de l'API d'orientation (apps/api OrientationEngine) ---

export type OrientationEvent = {
  code: string;
  label: string;
  description: string | null;
  icone: string | null;
};

export type OrientationAnswer = {
  code: string;
  libelle: string;
};

export type QuestionType = "single_choice" | "multi_choice";

export type OrientationQuestion = {
  code: string;
  libelle: string;
  type: QuestionType;
  answers: OrientationAnswer[];
  etape: number;
  etapeMax: number;
};

export type OrientationOffer = {
  code: string;
  label: string;
  description?: string;
};

export type OrientationVerification = {
  aideCode: string;
  label: string;
  methodes: string[];
};

export type OrientationRecommendation = {
  code: string;
  titre: string;
  description: string | null;
  offres: OrientationOffer[];
  aides: OrientationOffer[];
  verification: OrientationVerification | null;
  ctaLabel: string | null;
  ctaUrl: string | null;
};

export type VerificationStatut = "valide" | "en_attente" | "refuse";

export type EligibilityResult = {
  statut: VerificationStatut;
  statutLabel: string;
  message: string;
  source: string | null;
  donnees: Record<string, unknown>;
  fallbackRequis: boolean;
};

export type OrientationStep =
  | { type: "question"; question: OrientationQuestion }
  | { type: "recommendation"; recommendation: OrientationRecommendation };

type EventsResponse = {
  events: OrientationEvent[];
};

export type NextPayload = {
  scenario: string;
  currentQuestion?: string | null;
  answers?: string[];
};

/**
 * Récupère la liste des événements de vie proposés en entrée de parcours.
 */
export async function fetchEvents(): Promise<OrientationEvent[]> {
  const response = await fetch(`${API_BASE_URL}/api/orientation/events`, {
    headers: { Accept: "application/json" },
  });

  if (!response.ok) {
    throw new Error("Impossible de charger les événements de vie.");
  }

  const data = await readJson<EventsResponse>(response);

  return data?.events ?? [];
}

/**
 * Envoie l'état courant du parcours et renvoie l'étape suivante
 * (question suivante ou recommandation finale).
 */
export async function fetchNextStep(payload: NextPayload): Promise<OrientationStep> {
  const response = await fetch(`${API_BASE_URL}/api/orientation/next`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    const error = await readJson<ApiError>(response);
    throw new Error(getErrorMessage(error, "Une erreur est survenue. Réessayez."));
  }

  const step = await readJson<OrientationStep>(response);

  if (!step) {
    throw new Error("Réponse inattendue du serveur.");
  }

  return step;
}

/**
 * Voie 1 : vérification automatique de l'éligibilité via les services de l'État
 * (FranceConnect + API Particulier).
 */
export async function verifyViaState(aideCode: string): Promise<EligibilityResult> {
  const response = await fetch(`${API_BASE_URL}/api/orientation/eligibility/etat`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({ aideCode }),
  });

  if (!response.ok) {
    const error = await readJson<ApiError>(response);
    throw new Error(getErrorMessage(error, "La vérification a échoué. Réessayez."));
  }

  const result = await readJson<EligibilityResult>(response);

  if (!result) {
    throw new Error("Réponse inattendue du serveur.");
  }

  return result;
}

/**
 * Voie 2 (repli) : dépôt d'un justificatif contrôlé (2D-Doc / OCR).
 */
export async function verifyViaDocument(
  aideCode: string,
  fichier: File,
): Promise<EligibilityResult> {
  const formData = new FormData();
  formData.append("aideCode", aideCode);
  formData.append("document", fichier);

  // Pas d'en-tête Content-Type : le navigateur fixe lui-même la frontière multipart.
  const response = await fetch(`${API_BASE_URL}/api/orientation/eligibility/justificatif`, {
    method: "POST",
    headers: { Accept: "application/json" },
    body: formData,
  });

  if (!response.ok) {
    const error = await readJson<ApiError>(response);
    throw new Error(getErrorMessage(error, "Le justificatif n'a pas pu être vérifié."));
  }

  const result = await readJson<EligibilityResult>(response);

  if (!result) {
    throw new Error("Réponse inattendue du serveur.");
  }

  return result;
}
