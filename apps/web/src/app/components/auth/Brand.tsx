type BrandProps = {
  compact?: boolean;
  tone?: "light" | "dark";
};

export function Brand({ compact = false, tone = "dark" }: BrandProps) {
  const isLight = tone === "light";
  const filiale = isLight ? "rgba(255,255,255,0.8)" : "var(--idf-interaction)";
  const comu = isLight ? "#bfe0ff" : "var(--idf-blue)";
  const titres = isLight ? "#ffffff" : "var(--anthracite)";

  return (
    <span className="inline-flex min-w-0 flex-col leading-none" aria-label="Comutitres">
      <span
        className={`mb-1 font-semibold uppercase ${
          compact
            ? "hidden text-[6px] tracking-[0.16em] min-[390px]:block sm:text-[7px] sm:tracking-[0.22em]"
            : "text-[6px] tracking-[0.16em] sm:text-[7px] sm:tracking-[0.22em]"
        }`}
        style={{ color: filiale }}
      >
        Une filiale Île-de-France Mobilités
      </span>
      <span
        className={`font-bold leading-[0.95] tracking-tight ${
          compact ? "text-[1.2rem] sm:text-[1.45rem]" : "text-[1.2rem] sm:text-[1.5rem]"
        }`}
      >
        <span style={{ color: comu }}>comu</span>
        <span style={{ color: titres }}>titres</span>
      </span>
    </span>
  );
}
