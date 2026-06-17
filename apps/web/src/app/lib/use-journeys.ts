"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { API_BASE_URL, getStoredToken, readJson } from "./auth";
import { useAuth } from "./auth-context";

export type JourneyPoint = {
  name: string;
  lat: number;
  lng: number;
};

export type JourneyLine =
  | string
  | {
      code: string;
      mode?: string | null;
      network?: string | null;
      lineId?: string | null;
      primRef?: string | null;
      color?: string | null;
      textColor?: string | null;
    };

export type Journey = {
  id: number;
  label: string;
  origin: JourneyPoint;
  destination: JourneyPoint;
  lines: JourneyLine[] | null;
  createdAt: string;
  updatedAt: string;
};

export type Disruption = {
  id?: string;
  line: string;
  lineName?: string | null;
  mode?: string | null;
  network?: string | null;
  status: string;
  severity?: string | null;
  effect?: string | null;
  message: string;
  detail?: string | null;
  cause?: string | null;
  category?: string | null;
  disruptionStatus?: string | null;
  eligibleForJustificatif?: boolean;
  source?: string;
  updatedAt: string;
  validFrom?: string | null;
  validUntil?: string | null;
};

export type CheckedLine = {
  code: string;
  lineName?: string | null;
  mode?: string | null;
  network?: string | null;
  primRef?: string | null;
  lineId?: string | null;
  resolved: boolean;
  error?: string | null;
};

export type DisruptionCheckResult = {
  journeyId: number;
  checkedAt: string;
  checkedLines: CheckedLine[];
  currentDisruptions?: Disruption[];
  plannedDisruptions?: Disruption[];
  disruptions: Disruption[];
  summary?: {
    linesChecked: number;
    linesResolved: number;
    currentDisruptionsFound?: number;
    plannedDisruptionsFound?: number;
    canGenerateJustificatif?: boolean;
    disruptionsFound?: number;
  };
};

export function formatJourneyLines(lines: JourneyLine[] | null | undefined): string {
  if (!lines || lines.length === 0) return "—";
  return lines
    .map((line) => (typeof line === "string" ? line : `${line.mode ?? "Ligne"} ${line.code}`))
    .join(" · ");
}

type JourneyPayload = {
  label: string;
  origin: JourneyPoint;
  destination: JourneyPoint;
  lines: JourneyLine[] | null;
};

export function useJourneys() {
  const { user } = useAuth();
  const [journeys, setJourneys] = useState<Journey[]>([]);
  const [loading, setLoading] = useState(false);
  const [disruptions, setDisruptions] = useState<Disruption[]>([]);
  const [plannedDisruptions, setPlannedDisruptions] = useState<Disruption[]>([]);
  const [canGenerateJustificatif, setCanGenerateJustificatif] = useState(false);
  const [checkedLines, setCheckedLines] = useState<CheckedLine[]>([]);
  const [disruptionsJourneyId, setDisruptionsJourneyId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [downloading, setDownloading] = useState(false);
  const [checking, setChecking] = useState(false);

  const token = useMemo(() => getStoredToken(), []);

  useEffect(() => {
    let cancelled = false;
    async function load() {
      if (!token || !user) {
        setJourneys([]);
        return;
      }
      setLoading(true);
      setError(null);
      try {
        const response = await fetch(`${API_BASE_URL}/api/journeys`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
          cache: "no-store",
        });
        if (!response.ok) {
          setError("Impossible de charger vos trajets.");
          return;
        }
        const data = await readJson<{ items: Journey[] }>(response);
        if (!cancelled) {
          setJourneys(data?.items ?? []);
        }
      } catch {
        if (!cancelled) {
          setError("Erreur réseau lors du chargement des trajets.");
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }

    void load();
    return () => {
      cancelled = true;
    };
  }, [token, user]);

  const saveJourney = useCallback(
    async (payload: JourneyPayload) => {
      if (!token || !user) {
        throw new Error("Authentification requise pour enregistrer un trajet.");
      }
      setLoading(true);
      setError(null);
      try {
        const response = await fetch(`${API_BASE_URL}/api/journeys`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            label: payload.label,
            originName: payload.origin.name,
            originLat: payload.origin.lat,
            originLng: payload.origin.lng,
            destinationName: payload.destination.name,
            destinationLat: payload.destination.lat,
            destinationLng: payload.destination.lng,
            lines: payload.lines,
          }),
        });
        if (!response.ok) {
          const data = await readJson<{ message?: string }>(response);
          throw new Error(data?.message ?? "Impossible d'enregistrer le trajet.");
        }
        const data = await readJson<{ journey: Journey }>(response);
        if (data?.journey) {
          setJourneys((current) => [data.journey, ...current]);
        }
        return data?.journey;
      } finally {
        setLoading(false);
      }
    },
    [token, user],
  );

  const loadDisruptions = useCallback(
    async (journeyId: number) => {
      if (!token || !user) {
        throw new Error("Authentification requise pour vérifier les perturbations.");
      }
      setChecking(true);
      setError(null);
      try {
        const response = await fetch(`${API_BASE_URL}/api/disruptions?journeyId=${journeyId}`, {
          headers: { Authorization: `Bearer ${token}` },
          cache: "no-store",
        });
        if (!response.ok) {
          const data = await readJson<{ message?: string }>(response);
          throw new Error(data?.message ?? "Impossible de vérifier les perturbations.");
        }
        const data = await readJson<DisruptionCheckResult>(response);
        const current = data?.currentDisruptions ?? data?.disruptions ?? [];
        setDisruptions(current);
        setPlannedDisruptions(data?.plannedDisruptions ?? []);
        setCanGenerateJustificatif(Boolean(data?.summary?.canGenerateJustificatif ?? current.length > 0));
        setCheckedLines(data?.checkedLines ?? []);
        setDisruptionsJourneyId(journeyId);
        return data;
      } finally {
        setChecking(false);
      }
    },
    [token, user],
  );

  const downloadJustificatif = useCallback(
    async (journeyId: number) => {
      if (!token || !user) {
        throw new Error("Authentification requise pour générer un justificatif.");
      }
      setDownloading(true);
      setError(null);
      try {
        const response = await fetch(`${API_BASE_URL}/api/journeys/${journeyId}/justificatif`, {
          method: "POST",
          headers: { Authorization: `Bearer ${token}` },
        });
        if (!response.ok) {
          const data = await readJson<{ message?: string }>(response);
          throw new Error(data?.message ?? "Impossible de générer le justificatif.");
        }
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `justificatif-${journeyId}.pdf`;
        link.click();
        window.URL.revokeObjectURL(url);
      } finally {
        setDownloading(false);
      }
    },
    [token, user],
  );

  return {
    user,
    journeys,
    disruptions,
    plannedDisruptions,
    canGenerateJustificatif,
    checkedLines,
    disruptionsJourneyId,
    loading,
    checking,
    downloading,
    error,
    saveJourney,
    loadDisruptions,
    downloadJustificatif,
  };
}

