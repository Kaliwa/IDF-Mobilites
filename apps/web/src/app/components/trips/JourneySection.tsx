"use client";

import Link from "next/link";
import { useCallback, useMemo, useState } from "react";
import { btnGhost, btnPrimary, field, glass, sectionAccent } from "../../lib/ui";
import { API_BASE_URL } from "../../lib/auth";
import { useJourneys, type JourneyPoint } from "../../lib/use-journeys";
import { AddressField } from "./AddressField";
import { LineBadgeList } from "./LineBadge";
import { TripMap } from "./TripMap";
import { TripSummary } from "./TripSummary";

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

const DEFAULT_ORIGIN: JourneyPoint = { name: "", lat: 48.8566, lng: 2.3522 };
const DEFAULT_DESTINATION: JourneyPoint = { name: "", lat: 48.8738, lng: 2.295 };

export function JourneySection() {
  const [origin, setOrigin] = useState<JourneyPoint>(DEFAULT_ORIGIN);
  const [destination, setDestination] = useState<JourneyPoint>(DEFAULT_DESTINATION);
  const [label, setLabel] = useState("Trajet bureau");
  const [linesInput, setLinesInput] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [originInput, setOriginInput] = useState(DEFAULT_ORIGIN.name);
  const [destinationInput, setDestinationInput] = useState(DEFAULT_DESTINATION.name);
  const [routes, setRoutes] = useState<RouteSuggestion[]>([]);
  const [routesLoading, setRoutesLoading] = useState(false);
  const [activePolylines, setActivePolylines] = useState<
    Array<{ coords: Array<[number, number]>; color?: string }>
  >([]);
  const [activeRoute, setActiveRoute] = useState<RouteSuggestion | null>(null);

  const { user, saveJourney, journeys, loading, error } = useJourneys();

  const tileUrl = process.env.NEXT_PUBLIC_MAP_TILE_URL;
  const lines = useMemo(
    () =>
      linesInput
        .split(",")
        .map((entry) => entry.trim())
        .filter(Boolean),
    [linesInput],
  );

  const handleRoutes = useCallback(
    async (from: JourneyPoint, to: JourneyPoint) => {
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
          setLinesInput((current) => current || first.lines.join(", "));
        } else {
          setMessage("Aucune suggestion trouvée.");
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
    },
    [],
  );

  const selectOrigin = (point: JourneyPoint) => {
    setOrigin(point);
    setOriginInput(point.name);
    setMessage("Origine sélectionnée.");
    if (destination.name.trim()) {
      void handleRoutes(point, destination);
    }
  };

  const selectDestination = (point: JourneyPoint) => {
    setDestination(point);
    setDestinationInput(point.name);
    setMessage("Destination sélectionnée.");
    if (origin.name.trim()) {
      void handleRoutes(origin, point);
    }
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
          : lines.length > 0
            ? lines
            : null;

      await saveJourney({
        label,
        origin,
        destination,
        lines: linePayload,
      });
      setMessage("Trajet enregistré. Consultez la page Mes trajets pour les perturbations.");
    } catch (e) {
      setMessage(e instanceof Error ? e.message : "Impossible d'enregistrer le trajet.");
    }
  };

  const applyRoute = (route: RouteSuggestion) => {
    setLinesInput(route.lines.join(", "));
    setActiveRoute(route);
    setActivePolylines(
      route.polylines.length > 0 ? route.polylines : [{ coords: route.coords, color: "#1972d2" }],
    );
  };

  return (
    <section id="creer-trajet" className="mx-auto max-w-6xl px-4 py-14 sm:px-6 scroll-mt-20">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <span className={sectionAccent} aria-hidden="true" />
          <h2 className="text-2xl font-bold tracking-tight text-anthracite sm:text-3xl">
            Créer un trajet
          </h2>
          <p className="mt-2 max-w-2xl text-muted">
            Saisissez votre trajet domicile ↔ bureau/école, choisissez un itinéraire et enregistrez-le.
            Les perturbations et justificatifs sont sur la page{" "}
            <Link href="/trajets" className="font-semibold text-idf-interaction hover:underline">
              Mes trajets
            </Link>
            .
          </p>
        </div>
        <div className="flex flex-col items-start gap-2 text-xs text-muted sm:items-end">
          {user ? <span>Connecté en tant que {user.email}</span> : null}
          {user && journeys.length > 0 ? (
            <Link href="/trajets" className={`${btnGhost} px-3 py-1.5 text-xs`}>
              Voir mes {journeys.length} trajet{journeys.length > 1 ? "s" : ""}
            </Link>
          ) : null}
        </div>
      </div>

      <div className="mt-8 grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
        <div className={`${glass} p-6`}>
          <form className="space-y-4" onSubmit={handleSubmit}>
            <div className="grid gap-3 sm:grid-cols-2">
              <label className="space-y-1 text-sm text-anthracite/80">
                <span className="font-semibold">Nom du trajet</span>
                <input
                  className={field}
                  value={label}
                  onChange={(e) => setLabel(e.target.value)}
                  placeholder="Trajet domicile ↔ bureau"
                />
              </label>
              <div className="space-y-1 text-sm text-anthracite/80">
                <span className="font-semibold">Lignes principales</span>
                <div className="min-h-[42px] rounded-2xl border border-white/70 bg-white/60 px-3 py-2">
                  {activeRoute?.segments.length ? (
                    <LineBadgeList segments={activeRoute.segments} />
                  ) : (
                    <p className="text-sm text-muted">Choisissez un itinéraire ci-dessous</p>
                  )}
                </div>
              </div>
            </div>

            <div className="relative z-30 grid gap-3 sm:grid-cols-2">
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

            <div className="relative z-0">
              <TripMap
                origin={origin}
                destination={destination}
                tileUrl={tileUrl}
                polylines={activePolylines.length > 0 ? activePolylines : undefined}
              />
            </div>

            <div className="flex flex-wrap gap-3">
              <button type="submit" className={btnPrimary} disabled={loading || !user}>
                {loading ? "Enregistrement..." : "Enregistrer le trajet"}
              </button>
              <button
                type="button"
                className={btnGhost}
                onClick={() => void handleRoutes(origin, destination)}
                disabled={routesLoading || !origin.name.trim() || !destination.name.trim()}
              >
                {routesLoading ? "Recherche..." : "Suggérer un trajet"}
              </button>
            </div>

            {message ? <p className="text-sm text-anthracite/80">{message}</p> : null}
            {error ? <p className="text-sm text-warning">{error}</p> : null}
          </form>
        </div>

        <TripSummary
          origin={origin}
          destination={destination}
          lines={lines}
          segments={activeRoute?.segments}
          routeDistanceKm={activeRoute?.distanceKm}
          routeDurationMin={activeRoute?.duration}
          showDisruptions={false}
        />
      </div>

      {routes.length > 0 ? (
        <div className="mt-6 grid gap-3 lg:grid-cols-3">
          {routes.map((route) => (
            <article key={route.label} className={`${glass} p-4`}>
              <p className="text-sm font-semibold text-anthracite">{route.label}</p>
              <p className="mt-1 text-xs text-muted">{route.summary}</p>
              <p className="mt-2 text-sm text-anthracite/80">
                Durée estimée : <span className="font-semibold">{route.duration} min</span>
              </p>
              <p className="text-sm text-anthracite/80">
                Distance : <span className="font-semibold">{route.distanceKm} km</span>
              </p>
              <div className="mt-3">
                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">
                  Lignes
                </p>
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
    </section>
  );
}
