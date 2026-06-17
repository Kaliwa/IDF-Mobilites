import type { SVGProps } from "react";

const base = {
  width: 24,
  height: 24,
  viewBox: "0 0 24 24",
  fill: "none",
  stroke: "currentColor",
  strokeWidth: 1.8,
  strokeLinecap: "round" as const,
  strokeLinejoin: "round" as const,
};

export function CompassIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg {...base} {...props} aria-hidden="true">
      <circle cx="12" cy="12" r="9" />
      <path d="m15.5 8.5-2 5-5 2 2-5z" />
    </svg>
  );
}

export function DocumentIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg {...base} {...props} aria-hidden="true">
      <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
      <path d="M14 3v5h5M9 13h6M9 17h6" />
    </svg>
  );
}

export function ClockIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg {...base} {...props} aria-hidden="true">
      <circle cx="12" cy="12" r="9" />
      <path d="M12 7v5l3 2" />
    </svg>
  );
}

export function LifeBuoyIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg {...base} {...props} aria-hidden="true">
      <circle cx="12" cy="12" r="9" />
      <circle cx="12" cy="12" r="3.5" />
      <path d="m4.9 4.9 4.6 4.6m5 5 4.6 4.6m0-14.2-4.6 4.6m-5 5-4.6 4.6" />
    </svg>
  );
}

export function CardIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg {...base} {...props} aria-hidden="true">
      <rect x="3" y="5" width="18" height="14" rx="2.5" />
      <path d="M3 10h18M7 15h4" />
    </svg>
  );
}

export function SearchIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg {...base} {...props} aria-hidden="true">
      <circle cx="11" cy="11" r="7" />
      <path d="m20 20-3.5-3.5" />
    </svg>
  );
}

export function UserIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg {...base} {...props} aria-hidden="true">
      <circle cx="12" cy="8" r="4" />
      <path d="M5 20c0-3.3 3.1-6 7-6s7 2.7 7 6" />
    </svg>
  );
}

export function ArrowRightIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg {...base} {...props} aria-hidden="true">
      <path d="M5 12h14M13 6l6 6-6 6" />
    </svg>
  );
}

export function CheckIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg {...base} {...props} aria-hidden="true">
      <path d="m5 12 5 5L20 7" />
    </svg>
  );
}

export function BellIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg {...base} {...props} aria-hidden="true">
      <path d="M15 17H5.5a1.5 1.5 0 0 1-1.2-2.4l.7-.9c.7-.9 1-2 1-3.1V9a6 6 0 1 1 12 0v1.6c0 1.1.4 2.2 1 3.1l.7.9a1.5 1.5 0 0 1-1.2 2.4H15" />
      <path d="M9.5 17a2.5 2.5 0 0 0 5 0" />
    </svg>
  );
}

export function MessageSquareIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg {...base} {...props} aria-hidden="true">
      <path d="M7 18.5c-1.7 0-3-1.3-3-3v-8c0-1.7 1.3-3 3-3h10c1.7 0 3 1.3 3 3v8c0 1.7-1.3 3-3 3H11l-4 2z" />
      <path d="M8.5 10.5h7M8.5 14h5" />
    </svg>
  );
}

export function MenuIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg {...base} {...props} aria-hidden="true">
      <path d="M4 7h16M4 12h16M4 17h16" />
    </svg>
  );
}

export function CloseIcon(props: SVGProps<SVGSVGElement>) {
  return (
    <svg {...base} {...props} aria-hidden="true">
      <path d="M6 6l12 12M18 6 6 18" />
    </svg>
  );
}
