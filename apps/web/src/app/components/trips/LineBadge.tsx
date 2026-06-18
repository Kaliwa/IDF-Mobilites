import { RouteSign } from "../RouteSign";

type Segment = {
  mode: string;
  code: string;
  label: string;
  network?: string | null;
  color: string;
  textColor: string;
};

export function LineBadge({ segment }: { segment: Segment }) {
  return (
    <span
      className="text-[1.4rem] leading-none"
      title={`${segment.mode}${segment.network ? ` — ${segment.network}` : ""}`}
    >
      <RouteSign route={segment.code} />
    </span>
  );
}

export function LineBadgeList({ segments }: { segments: Segment[] }) {
  if (segments.length === 0) {
    return <span className="text-sm text-muted">—</span>;
  }

  return (
    <div className="flex flex-wrap items-center gap-3">
      {segments.map((segment, index) => (
        <LineBadge key={`${segment.mode}-${segment.code}-${index}`} segment={segment} />
      ))}
    </div>
  );
}
