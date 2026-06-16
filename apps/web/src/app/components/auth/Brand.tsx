type BrandProps = {
  tone?: "light" | "dark";
};

export function Brand({ tone = "dark" }: BrandProps) {
  const isLight = tone === "light";
  const filiale = isLight ? "rgba(255,255,255,0.8)" : "var(--idf-interaction)";
  const comu = isLight ? "#bfe0ff" : "var(--idf-blue)";
  const titres = isLight ? "#ffffff" : "var(--anthracite)";

  return (
    <span className="inline-flex flex-col leading-none" aria-label="Comutitres">
      <span
        className="mb-1 text-[7px] font-semibold uppercase tracking-[0.22em]"
        style={{ color: filiale }}
      >
        Une filiale Île-de-France Mobilités
      </span>
      <span className="text-[1.5rem] font-bold leading-[0.95] tracking-tight">
        <span style={{ color: comu }}>comu</span>
        <span style={{ color: titres }}>titres</span>
      </span>
    </span>
  );
}
