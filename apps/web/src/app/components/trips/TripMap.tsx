"use client";

import dynamic from "next/dynamic";
import { useEffect, useMemo } from "react";
import type { JourneyPoint } from "../../lib/use-journeys";

const loading = () => (
  <div className="h-[360px] w-full animate-pulse rounded-3xl border border-white/45 bg-white/40" />
);

const MapContainer = dynamic(
  () => import("react-leaflet").then((mod) => mod.MapContainer),
  { ssr: false, loading },
);
const TileLayer = dynamic(
  () => import("react-leaflet").then((mod) => mod.TileLayer),
  { ssr: false, loading },
);
const Marker = dynamic(
  () => import("react-leaflet").then((mod) => mod.Marker),
  { ssr: false, loading },
);
const Popup = dynamic(() => import("react-leaflet").then((mod) => mod.Popup), {
  ssr: false,
  loading,
});
const Polyline = dynamic(
  () => import("react-leaflet").then((mod) => mod.Polyline),
  { ssr: false, loading },
);
const MapFitBounds = dynamic(
  () => import("./MapFitBounds").then((mod) => mod.MapFitBounds),
  { ssr: false },
);

type Props = {
  origin: JourneyPoint;
  destination: JourneyPoint;
  tileUrl?: string;
  polylines?: Array<{ coords: Array<[number, number]>; color?: string }>;
};

export function TripMap({ origin, destination, tileUrl, polylines }: Props) {
  useEffect(() => {
    let cancelled = false;
    import("leaflet").then((L) => {
      if (cancelled) return;
      delete (L.Icon.Default.prototype as { _getIconUrl?: unknown })._getIconUrl;
      L.Icon.Default.mergeOptions({
        iconRetinaUrl:
          "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png",
        iconUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png",
        shadowUrl:
          "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
      });
    });
    return () => {
      cancelled = true;
    };
  }, []);

  const center = useMemo(
    () => ({
      lat: (origin.lat + destination.lat) / 2,
      lng: (origin.lng + destination.lng) / 2,
    }),
    [origin, destination],
  );

  const fitPoints = useMemo(() => {
    const points: Array<[number, number]> = [
      [origin.lat, origin.lng],
      [destination.lat, destination.lng],
    ];
    polylines?.forEach((line) => {
      line.coords.forEach((coord) => points.push(coord));
    });
    return points;
  }, [origin, destination, polylines]);

  return (
    <MapContainer
      center={[center.lat, center.lng]}
      zoom={13}
      scrollWheelZoom={false}
      className="trip-map relative z-0 h-[360px] w-full overflow-hidden rounded-3xl border border-white/45 shadow-[0_18px_42px_-26px_rgba(0,80,170,0.6)]"
    >
      <TileLayer
        attribution="© OpenStreetMap"
        url={tileUrl ?? "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"}
      />

      <MapFitBounds points={fitPoints} />

      {polylines?.map((line, idx) =>
        line.coords.length > 1 ? (
          <Polyline
            key={`${idx}-${line.color ?? "route"}`}
            positions={line.coords.map(([lat, lng]) => [lat, lng])}
            pathOptions={{
              color: line.color ?? "#1972d2",
              weight: 6,
              opacity: 0.85,
            }}
          />
        ) : null,
      )}

      <Marker position={[origin.lat, origin.lng]}>
        <Popup>
          Origine
          <br />
          {origin.name}
        </Popup>
      </Marker>

      <Marker position={[destination.lat, destination.lng]}>
        <Popup>
          Destination
          <br />
          {destination.name}
        </Popup>
      </Marker>
    </MapContainer>
  );
}
