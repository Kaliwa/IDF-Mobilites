"use client";

import { useEffect, useId, useRef, useState } from "react";
import { field } from "../../lib/ui";
import type { JourneyPoint } from "../../lib/use-journeys";

type Props = {
  label: string;
  value: string;
  placeholder: string;
  selected: JourneyPoint;
  onValueChange: (value: string) => void;
  onSelect: (point: JourneyPoint) => void;
};

export function AddressField({
  label,
  value,
  placeholder,
  selected,
  onValueChange,
  onSelect,
}: Props) {
  const listId = useId();
  const skipFetchRef = useRef(false);
  const [suggestions, setSuggestions] = useState<JourneyPoint[]>([]);
  const [loading, setLoading] = useState(false);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    if (skipFetchRef.current) {
      skipFetchRef.current = false;
      return;
    }

    const controller = new AbortController();

    const run = async () => {
      if (value.trim().length < 3) {
        setSuggestions([]);
        setOpen(false);
        return;
      }

      if (value.trim() === selected.name.trim()) {
        setSuggestions([]);
        setOpen(false);
        return;
      }

      setLoading(true);
      try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(value)}&limit=5&addressdetails=0`;
        const res = await fetch(url, {
          signal: controller.signal,
          headers: { "User-Agent": "IDF-Mobilites-Demo" },
        });
        if (!res.ok) return;
        const data = (await res.json()) as Array<{ lat: string; lon: string; display_name: string }>;
        const items = (data ?? []).slice(0, 5).map((item) => ({
          name: item.display_name,
          lat: parseFloat(item.lat),
          lng: parseFloat(item.lon),
        }));
        setSuggestions(items);
        setOpen(items.length > 0);
      } catch {
        // ignore aborted / network errors
      } finally {
        setLoading(false);
      }
    };

    const handle = window.setTimeout(run, 300);
    return () => {
      controller.abort();
      window.clearTimeout(handle);
    };
  }, [value, selected.name]);

  const pick = (point: JourneyPoint) => {
    skipFetchRef.current = true;
    setSuggestions([]);
    setOpen(false);
    onSelect(point);
    onValueChange(point.name);
  };

  return (
    <div className="relative z-30 space-y-1 text-sm text-anthracite/80">
      <label className="font-semibold" htmlFor={listId}>
        {label}
      </label>
      <div className="relative">
        <input
          id={listId}
          className={field}
          value={value}
          placeholder={placeholder}
          autoComplete="off"
          role="combobox"
          aria-expanded={open}
          aria-controls={`${listId}-listbox`}
          onChange={(event) => {
            onValueChange(event.target.value);
            setOpen(true);
          }}
          onFocus={() => {
            if (suggestions.length > 0) setOpen(true);
          }}
          onBlur={() => {
            window.setTimeout(() => setOpen(false), 150);
          }}
        />
        {open && suggestions.length > 0 ? (
          <ul
            id={`${listId}-listbox`}
            role="listbox"
            className="absolute left-0 right-0 top-full z-[1000] mt-1 max-h-52 overflow-auto rounded-2xl border border-white/70 bg-white shadow-xl ring-1 ring-black/5"
          >
            {suggestions.map((sugg) => (
              <li key={`${sugg.lat}-${sugg.lng}-${sugg.name}`}>
                <button
                  type="button"
                  className="w-full px-3 py-2 text-left text-sm hover:bg-idf-interaction/10"
                  onMouseDown={(event) => event.preventDefault()}
                  onClick={() => pick(sugg)}
                >
                  {sugg.name}
                </button>
              </li>
            ))}
          </ul>
        ) : null}
      </div>
      {loading ? <p className="text-xs text-muted">Recherche...</p> : null}
    </div>
  );
}
