import type { JourneyLine } from "./use-journeys";

export type LineSegment = {
  mode: string;
  code: string;
  label: string;
  network?: string | null;
  color: string;
  textColor: string;
};

const METRO_COLORS: Record<string, { bg: string; fg: string }> = {
  "1": { bg: "#FFCA03", fg: "#000000" },
  "2": { bg: "#0064B0", fg: "#FFFFFF" },
  "3": { bg: "#9B9836", fg: "#FFFFFF" },
  "4": { bg: "#BE418D", fg: "#FFFFFF" },
  "5": { bg: "#FF7E2E", fg: "#000000" },
  "6": { bg: "#78C696", fg: "#000000" },
  "7": { bg: "#FA9AB4", fg: "#000000" },
  "8": { bg: "#CEADD2", fg: "#000000" },
  "9": { bg: "#D5C900", fg: "#000000" },
  "10": { bg: "#E6199B", fg: "#FFFFFF" },
  "11": { bg: "#837902", fg: "#FFFFFF" },
  "12": { bg: "#00643C", fg: "#FFFFFF" },
  "13": { bg: "#82C8E6", fg: "#000000" },
  "14": { bg: "#62259D", fg: "#FFFFFF" },
};

const RER_COLORS: Record<string, { bg: string; fg: string }> = {
  A: { bg: "#EB2132", fg: "#FFFFFF" },
  B: { bg: "#549633", fg: "#FFFFFF" },
  C: { bg: "#F7C600", fg: "#000000" },
  D: { bg: "#008B5B", fg: "#FFFFFF" },
  E: { bg: "#CEADD2", fg: "#000000" },
};

function normalizeHex(color: string | null | undefined, fallback: string): string {
  if (!color || color.trim() === "") {
    return fallback;
  }

  return color.startsWith("#") ? color : `#${color}`;
}

function fallbackColors(mode: string, code: string): { color: string; textColor: string } {
  const normalizedMode = mode.toLowerCase();
  const normalizedCode = code.trim().toUpperCase();

  if (normalizedMode.includes("métro") || normalizedMode.includes("metro")) {
    const palette = METRO_COLORS[code.trim()] ?? METRO_COLORS[normalizedCode];
    if (palette) {
      return { color: palette.bg, textColor: palette.fg };
    }
  }

  if (normalizedMode.includes("rer")) {
    const palette = RER_COLORS[normalizedCode];
    if (palette) {
      return { color: palette.bg, textColor: palette.fg };
    }
  }

  if (normalizedMode.includes("bus")) {
    return { color: "#00643C", textColor: "#FFFFFF" };
  }

  if (normalizedMode.includes("tram")) {
    return { color: "#6EC4E8", textColor: "#000000" };
  }

  if (normalizedMode.includes("transilien")) {
    return { color: "#6B9820", textColor: "#FFFFFF" };
  }

  return { color: "#1972d2", textColor: "#FFFFFF" };
}

function inferMode(code: string, mode?: string | null): string {
  if (mode && mode.trim() !== "") {
    return mode;
  }

  const trimmed = code.trim();
  if (/^(1[0-4]|[1-9])$/.test(trimmed)) {
    return "Métro";
  }

  if (/^[A-E]$/i.test(trimmed)) {
    return "RER";
  }

  if (/^\d+$/.test(trimmed)) {
    return "Bus";
  }

  return "Ligne";
}

export function journeyLinesToSegments(lines: JourneyLine[] | null | undefined): LineSegment[] {
  if (!lines || lines.length === 0) {
    return [];
  }

  return lines.map((line) => {
    if (typeof line === "string") {
      const mode = inferMode(line);
      const colors = fallbackColors(mode, line);

      return {
        mode,
        code: line,
        label: line,
        color: colors.color,
        textColor: colors.textColor,
      };
    }

    const mode = inferMode(line.code, line.mode);
    const colors = fallbackColors(mode, line.code);

    return {
      mode,
      code: line.code,
      label: line.code,
      network: line.network,
      color: line.color ? normalizeHex(line.color, colors.color) : colors.color,
      textColor: line.textColor ? normalizeHex(line.textColor, colors.textColor) : colors.textColor,
    };
  });
}
