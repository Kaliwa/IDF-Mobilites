"use client";

import { glass } from "../../lib/ui";
import type { Disruption, JourneyPoint } from "../../lib/use-journeys";
import { LineBadgeList } from "./LineBadge";

type Segment = {
  mode: string;
  code: string;
  label: string;
  network?: string | null;
  color: string;
  textColor: string;
};

type Props = {
  origin: JourneyPoint;
  destination: JourneyPoint;
  lines: string[];
  segments?: Segment[];
  routeDistanceKm?: number | null;
  routeDurationMin?: number | null;
  showDisruptions?: boolean;
  disruptions?: Disruption[];
  plannedDisruptions?: Disruption[];
  canGenerateJustificatif?: boolean;
};

function DisruptionList({ items, variant }: { items: Disruption[]; variant: "current" | "planned" }) {
  if (items.length === 0) return null;

  return (
    <ul className="mt-2 space-y-3 text-muted">
      {items.map((item) => (
        <li key={`${item.id ?? item.line}-${item.updatedAt}`} className="flex items-start gap-2">
          <span
            className={`mt-0.5 h-2 w-2 rounded-full ${variant === "current" ? "bg-warning" : "bg-idf-interaction/60"}`}
            aria-hidden="true"
          />
          <div className="space-y-1">
            <p className="font-semibold text-anthracite">
              {item.lineName ? `${item.lineName}` : `Ligne ${item.line}`}
              {item.mode ? ` · ${item.mode}` : ""}
              {" — "}
              {item.status}
            </p>
            <p className="text-sm text-anthracite/90">{item.message}</p>
            {item.detail && item.detail !== item.message ? (
              <p className="text-sm">{item.detail}</p>
            ) : null}
            <p className="text-xs text-muted">
              {[
                item.cause,
                item.disruptionStatus,
                item.source ? `Source ${item.source.toUpperCase()}` : null,
                item.updatedAt ? `MAJ ${new Date(item.updatedAt).toLocaleString()}` : null,
              ]
                .filter(Boolean)
                .join(" · ")}
            </p>
          </div>
        </li>
      ))}
    </ul>
  );
}

export function TripSummary({
  origin,
  destination,
  lines,
  segments = [],
  routeDistanceKm,
  routeDurationMin,
  showDisruptions = true,
  disruptions = [],
  plannedDisruptions = [],
  canGenerateJustificatif = false,
}: Props) {
  const straightKm = haversine(origin.lat, origin.lng, destination.lat, destination.lng);
  const distanceKm = routeDistanceKm && routeDistanceKm > 0 ? routeDistanceKm : straightKm;
  const hasCurrent = disruptions.length > 0;
  const hasPlanned = plannedDisruptions.length > 0;

  return (
    <div className={`${glass} h-full p-6`}>
      <h3 className="text-lg font-semibold text-anthracite">Récapitulatif</h3>
      <dl className="mt-4 space-y-2 text-sm text-anthracite/80">
        <div className="flex justify-between gap-3">
          <dt className="text-muted">Origine</dt>
          <dd className="text-right font-semibold">{origin.name}</dd>
        </div>
        <div className="flex justify-between gap-3">
          <dt className="text-muted">Destination</dt>
          <dd className="text-right font-semibold">{destination.name}</dd>
        </div>
        <div className="flex justify-between gap-3">
          <dt className="text-muted">Distance estimée</dt>
          <dd className="text-right font-semibold">{distanceKm.toFixed(1)} km</dd>
        </div>
        {routeDurationMin != null && routeDurationMin > 0 ? (
          <div className="flex justify-between gap-3">
            <dt className="text-muted">Durée estimée</dt>
            <dd className="text-right font-semibold">{routeDurationMin} min</dd>
          </div>
        ) : null}
        <div className="space-y-2">
          <dt className="text-muted">Lignes</dt>
          <dd>
            {segments.length > 0 ? (
              <LineBadgeList segments={segments} />
            ) : (
              <span className="font-semibold">
                {lines.length > 0 ? lines.join(" · ") : "Sélectionnez un itinéraire"}
              </span>
            )}
          </dd>
        </div>
      </dl>

      {showDisruptions ? (
        <div className="mt-6 space-y-3">
          <div className="rounded-2xl border border-white/60 bg-white/50 p-3 text-sm">
            <p className="font-semibold text-anthracite">Incidents en cours</p>
            {hasCurrent ? (
              <DisruptionList items={disruptions} variant="current" />
            ) : (
              <p className="mt-2 text-muted">Aucun incident actif sur vos lignes.</p>
            )}
            {canGenerateJustificatif ? (
              <p className="mt-2 text-xs font-medium text-anthracite">
                Justificatif disponible pour l&apos;incident en cours.
              </p>
            ) : null}
          </div>

          {hasPlanned ? (
            <div className="rounded-2xl border border-white/60 bg-white/40 p-3 text-sm">
              <p className="font-semibold text-anthracite">Travaux / infos à venir</p>
              <p className="mt-1 text-xs text-muted">
                Ces informations ne permettent pas de générer un justificatif.
              </p>
              <DisruptionList items={plannedDisruptions} variant="planned" />
            </div>
          ) : null}
        </div>
      ) : null}
    </div>
  );
}

function haversine(lat1: number, lon1: number, lat2: number, lon2: number): number {
  const toRad = (value: number) => (value * Math.PI) / 180;
  const R = 6371;
  const dLat = toRad(lat2 - lat1);
  const dLon = toRad(lon2 - lon1);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
}
