import { glass } from "../../lib/ui";

const STATS = [
  { value: "1 900", unit: "lignes", label: "de bus" },
  { value: "2 149", unit: "km", label: "de réseau ferré" },
  { value: "9,4 M", unit: "", label: "déplacements / jour" },
  { value: "6,7 M", unit: "", label: "clients franciliens" },
];

export function NetworkStats() {
  return (
    <section id="reseau" className="mx-auto w-full max-w-6xl px-4 py-14 sm:px-6 scroll-mt-20">
      <div className={`${glass} overflow-hidden p-8 sm:p-10`}>
        <p className="max-w-2xl text-lg font-medium text-anthracite">
          Le réseau de transport francilien est le 2ᵉ plus dense et fréquenté au monde,
          1ᵉʳ en Europe.
        </p>

        <dl className="mt-8 grid grid-cols-2 gap-6 lg:grid-cols-4">
          {STATS.map((stat) => (
            <div key={stat.label}>
              <dt className="flex items-baseline gap-1">
                <span className="text-3xl font-bold text-idf-interaction sm:text-4xl">
                  {stat.value}
                </span>
                {stat.unit ? (
                  <span className="text-lg font-semibold text-anthracite/70">{stat.unit}</span>
                ) : null}
              </dt>
              <dd className="mt-1 text-sm text-muted">{stat.label}</dd>
            </div>
          ))}
        </dl>
      </div>
    </section>
  );
}
