"use client";

import { useEffect } from "react";
import { useMap } from "react-leaflet";

type Props = {
  points: Array<[number, number]>;
};

export function MapFitBounds({ points }: Props) {
  const map = useMap();

  useEffect(() => {
    if (points.length < 2) return;

    void import("leaflet").then((L) => {
      const bounds = L.latLngBounds(points.map(([lat, lng]) => [lat, lng]));
      map.fitBounds(bounds, { padding: [36, 36] });
    });
  }, [map, points]);

  return null;
}
