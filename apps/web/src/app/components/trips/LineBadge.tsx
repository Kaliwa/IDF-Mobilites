type Segment = {
  mode: string;
  code: string;
  label: string;
  network?: string | null;
  color: string;
  textColor: string;
};

function modePrefix(mode: string, code: string): string {
  const m = mode.toLowerCase();
  if (m.includes("rer")) return code;
  if (m.includes("métro") || m.includes("metro")) return code;
  if (m.includes("tram")) return code;
  if (m.includes("bus")) return code;
  if (m.includes("transilien")) return code;
  return code;
}

function ModeIcon({ mode }: { mode: string }) {
  const m = mode.toLowerCase();
  if (m.includes("bus")) {
    return (
      <svg viewBox="0 0 24 24" className="h-3 w-3" fill="currentColor" aria-hidden="true">
        <path d="M4 16c0 .88.39 1.67 1 2.22V20a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h8v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-3.5-3.58-4-8-4s-8 .5-8 4v10zm3.5 1c-.83 0-1.5-.67-1.5-1.5S6.67 14 7.5 14s1.5.67 1.5 1.5S8.33 17 7.5 17zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM8 6V4.5C8 3.12 10.24 2 12 2s4 1.12 4 2.5V6H8z" />
      </svg>
    );
  }
  if (m.includes("tram")) {
    return (
      <svg viewBox="0 0 24 24" className="h-3 w-3" fill="currentColor" aria-hidden="true">
        <path d="M19 16.94V8.5c0-.59-.47-1.07-1.05-1.07H6.05C5.47 7.43 5 7.91 5 8.5v8.44c0 .59.47 1.06 1.05 1.06H7v1.07c0 .59.47 1.06 1.05 1.06h1.9c.58 0 1.05-.47 1.05-1.06V18h4v1.07c0 .59.47 1.06 1.05 1.06h1.9c.58 0 1.05-.47 1.05-1.06V18h.95c.58 0 1.05-.47 1.05-1.06zM7.5 15.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM18 11H6V9h12v2z" />
      </svg>
    );
  }
  return (
    <svg viewBox="0 0 24 24" className="h-3 w-3" fill="currentColor" aria-hidden="true">
      <path d="M12 2C8 2 5 5.58 5 10c0 5.25 7 12 7 12s7-6.75 7-12c0-4.42-3-8-7-8zm0 10.5A2.5 2.5 0 1 1 12 7.5a2.5 2.5 0 0 1 0 5z" />
    </svg>
  );
}

export function LineBadge({ segment }: { segment: Segment }) {
  const display = modePrefix(segment.mode, segment.code);
  const isRer = segment.mode.toLowerCase().includes("rer");

  return (
    <span
      className="inline-flex flex-col items-center gap-0.5"
      title={`${segment.mode}${segment.network ? ` — ${segment.network}` : ""}`}
    >
      <span
        className="inline-flex min-w-[2.5rem] items-center justify-center gap-1 rounded-md px-2 py-1 text-xs font-bold shadow-sm ring-1 ring-black/10"
        style={{
          backgroundColor: segment.color,
          color: segment.textColor,
        }}
      >
        {isRer ? <span className="text-[9px] font-extrabold tracking-tight">RER</span> : null}
        <span>{display}</span>
      </span>
      <span className="inline-flex items-center gap-1 text-[10px] font-medium uppercase tracking-wide text-muted">
        <ModeIcon mode={segment.mode} />
        {segment.mode}
      </span>
    </span>
  );
}

export function LineBadgeList({ segments }: { segments: Segment[] }) {
  if (segments.length === 0) {
    return <span className="text-sm text-muted">—</span>;
  }

  return (
    <div className="flex flex-wrap items-end gap-3">
      {segments.map((segment, index) => (
        <LineBadge key={`${segment.mode}-${segment.code}-${index}`} segment={segment} />
      ))}
    </div>
  );
}
