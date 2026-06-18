"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { API_BASE_URL } from "../../lib/auth";
import { journeyLinesToSegments } from "../../lib/line-segments";
import { btnGhost, btnPrimary, field } from "../../lib/ui";
import type { Journey, JourneyPayload, JourneyPoint } from "../../lib/use-journeys";
import { AddressField } from "./AddressField";
import { LineBadgeList } from "./LineBadge";
import { TripMap } from "./TripMap";

type RouteSegment = {
  mode: string;
  code: string;
  label: string;
  network?: string | null;
  lineId?: string | null;
  primRef?: string | null;
  color: string;
  textColor: string;
};

type RouteSuggestion = {
  label: string;
  duration: number;
  distanceKm: number;
  lines: string[];
  segments: RouteSegment[];
  summary: string;
  coords: Array<[number, number]>;
  polylines: Array<{ coords: Array<[number, number]>; color?: string }>;
};

type Props = {
  journey: Journey;
  open: boolean;
  saving?: boolean;
  onClose: () => void;
  onSave: (payload: JourneyPayload) => Promise<void>;
};

export function EditJourneyModal({ journey, open, saving = false, onClose, onSave }: Props) {
  const [label, setLabel] = useState(journey.label);
  const [origin, setOrigin] = useState<JourneyPoint>(journey.origin);
  const [destination, setDestination] = useState<JourneyPoint>(journey.destination);
  const [originInput, setOriginInput] = useState(journey.origin.name);
  const [destinationInput, setDestinationInput] = useState(journey.destination.name);
  const [message, setMessage] = useState<string | null>(null);
  const [routes, setRoutes] = useState<RouteSuggestion[]>([]);
  const [routesLoading, setRoutesLoading] = useState(false);
  const [activePolylines, setActivePolylines] = useState<
    Array<{ coords: Array<[number, number]>; color?: string }>
  >([]);
  const [activeRoute, setActiveRoute] = useState<RouteSuggestion | null>(null);

  const tileUrl = process.env.NEXT_PUBLIC_MAP_TILE_URL;
  const initialSegments = useMemo(() => journeyLinesToSegments(journey.lines), [journey.lines]);

  useEffect(() => {
    if (!open) return;
    setLabel(journey.label);
    setOrigin(journey.origin);
    setDestination(journey.destination);
    setOriginInput(journey.origin.name);
    setDestinationInput(journey.destination.name);
    setMessage(null);
    setRoutes([]);
    setActiveRoute(null);
    setActivePolylines([]);
  }, [journey, open]);

  const handleRoutes = useCallback(async (from: JourneyPoint, to: JourneyPoint) => {
    if (!from.name.trim() || !to.name.trim()) {
      return;
    }

    setMessage(null);
    setRoutes([]);
    setRoutesLoading(true);

    try {
      const params = new URLSearchParams({
        originLat: from.lat.toString(),
        originLng: from.lng.toString(),
        destinationLat: to.lat.toString(),
        destinationLng: to.lng.toString(),
        originName: from.name,
        destinationName: to.name,
      });
      const res = await fetch(`${API_BASE_URL}/api/routes?${params.toString()}`);
      if (!res.ok) {
        throw new Error("Service itinéraires indisponible");
      }

      const data = (await res.json()) as { routes?: RouteSuggestion[] };
      const parsed = data.routes ?? [];

      setRoutes(parsed);
      if (parsed.length > 0) {
        const first = parsed[0];
        setActiveRoute(first);
        setActivePolylines(
          first.polylines.length > 0
            ? first.polylines
            : [{ coords: first.coords, color: "#1972d2" }],
        );
      } else {
        setMessage("Aucun itinéraire en transports en commun trouvé pour cette origine et destination.");
        setActiveRoute(null);
        setActivePolylines([]);
      }
    } catch {
      setMessage("Erreur lors de la récupération des itinéraires.");
      setRoutes([]);
      setActiveRoute(null);
      setActivePolylines([]);
    } finally {
      setRoutesLoading(false);
    }
  }, []);

  const selectOrigin = (point: JourneyPoint) => {
    setOrigin(point);
    setOriginInput(point.name);
    if (destination.name.trim()) {
      void handleRoutes(point, destination);
    }
  };

  const selectDestination = (point: JourneyPoint) => {
    setDestination(point);
    setDestinationInput(point.name);
    if (origin.name.trim()) {
      void handleRoutes(origin, point);
    }
  };

  const applyRoute = (route: RouteSuggestion) => {
    setActiveRoute(route);
    setActivePolylines(
      route.polylines.length > 0 ? route.polylines : [{ coords: route.coords, color: "#1972d2" }],
    );
  };

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setMessage(null);

    if (!origin.name.trim() || !destination.name.trim()) {
      setMessage("Sélectionnez une origine et une destination dans les suggestions.");
      return;
    }

    try {
      const linePayload =
        activeRoute?.segments && activeRoute.segments.length > 0
          ? activeRoute.segments.map((segment) => ({
              code: segment.code,
              mode: segment.mode,
              network: segment.network ?? null,
              lineId: segment.lineId ?? null,
              primRef: segment.primRef ?? null,
              color: segment.color,
              textColor: segment.textColor,
            }))
          : journey.lines;

      await onSave({
        label,
        origin,
        destination,
        lines: linePayload,
      });
    } catch (e) {
      setMessage(e instanceof Error ? e.message : "Impossible de modifier le trajet.");
    }
  };

  if (!open) {
    return null;
  }

  const displayedSegments =
    activeRoute?.segments && activeRoute.segments.length > 0
      ? activeRoute.segments
      : initialSegments;

  return (
    <div
      className="fixed inset-0 z-[2000] flex items-center justify-center bg-black/40 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="edit-journey-title"
      onClick={onClose}
    >
      <div
        className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl border border-border bg-surface p-6 shadow-xl"
        onClick={(event) => event.stopPropagation()}
      >
        <div className="flex items-start justify-between gap-4">
          <div>
            <h2 id="edit-journey-title" className="text-xl font-bold text-anthracite">
              Modifier le trajet
            </h2>
            <p className="mt-1 text-sm text-muted">
              Mettez à jour le nom, les adresses ou les lignes de votre trajet.
            </p>
          </div>
          <button
            type="button"
            className="rounded-lg px-2 py-1 text-sm text-muted hover:bg-idf-blue-light/20"
            onClick={onClose}
            aria-label="Fermer"
          >
            ✕
          </button>
        </div>

        <form className="mt-6 space-y-4" onSubmit={handleSubmit}>
          <label className="block space-y-1 text-sm text-anthracite/80">
            <span className="font-semibold">Nom du trajet</span>
            <input
              className={field}
              value={label}
              onChange={(e) => setLabel(e.target.value)}
              placeholder="Trajet domicile ↔ bureau"
            />
          </label>

          <div className="grid gap-3 sm:grid-cols-2">
            <AddressField
              label="Origine"
              value={originInput}
              placeholder="Saisissez l'adresse d'origine"
              selected={origin}
              onValueChange={setOriginInput}
              onSelect={selectOrigin}
            />
            <AddressField
              label="Destination"
              value={destinationInput}
              placeholder="Saisissez l'adresse de destination"
              selected={destination}
              onValueChange={setDestinationInput}
              onSelect={selectDestination}
            />
          </div>

          <div className="space-y-1 text-sm text-anthracite/80">
            <span className="font-semibold">Lignes principales</span>
            <div className="min-h-[42px] rounded-lg border border-border bg-surface px-3 py-2">
              {displayedSegments.length > 0 ? (
                <LineBadgeList segments={displayedSegments} />
              ) : (
                <p className="text-sm text-muted">Aucune ligne renseignée</p>
              )}
            </div>
          </div>

          <TripMap
            origin={origin}
            destination={destination}
            tileUrl={tileUrl}
            polylines={activePolylines.length > 0 ? activePolylines : undefined}
          />

          <div className="flex flex-wrap gap-3">
            <button type="submit" className={btnPrimary} disabled={saving}>
              {saving ? "Enregistrement..." : "Enregistrer les modifications"}
            </button>
            <button
              type="button"
              className={btnGhost}
              onClick={() => void handleRoutes(origin, destination)}
              disabled={routesLoading || !origin.name.trim() || !destination.name.trim()}
            >
              {routesLoading ? "Recherche..." : "Suggérer un trajet"}
            </button>
            <button type="button" className={btnGhost} onClick={onClose} disabled={saving}>
              Annuler
            </button>
          </div>

          {message ? <p className="text-sm text-anthracite/80">{message}</p> : null}
        </form>

        {routes.length > 0 ? (
          <div className="mt-6 grid gap-3 sm:grid-cols-2">
            {routes.map((route) => (
              <article key={route.label} className="rounded-lg border border-border p-4">
                <p className="text-sm font-semibold text-anthracite">{route.label}</p>
                <p className="mt-1 text-xs text-muted">{route.summary}</p>
                <div className="mt-3">
                  <LineBadgeList segments={route.segments} />
                </div>
                <button
                  type="button"
                  className={`${btnPrimary} mt-3 w-full text-sm`}
                  onClick={() => applyRoute(route)}
                >
                  Utiliser ces lignes
                </button>
              </article>
            ))}
          </div>
        ) : null}
      </div>
    </div>
  );
}
