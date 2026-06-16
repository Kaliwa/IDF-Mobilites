import type { ReactNode } from "react";
import { Brand } from "./Brand";
import { glass } from "../../lib/ui";

type Highlight = {
  value: string;
  label: string;
};

type AuthShellProps = {
  asideTitle: string;
  asideText: string;
  highlights?: Highlight[];
  children: ReactNode;
};

export function AuthShell({
  asideTitle,
  asideText,
  highlights = [],
  children,
}: AuthShellProps) {
  return (
    <div className="auth-bg">
      <div
        className={`${glass} rise-in grid w-full max-w-[960px] overflow-hidden md:grid-cols-[0.95fr_1.05fr]`}
      >
        <aside className="relative hidden flex-col justify-between overflow-hidden p-10 text-white md:flex bg-[radial-gradient(circle_at_top_left,#0050aa,#1972d2_55%,#64b5f6)]">
          <div
            aria-hidden="true"
            className="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/20 blur-2xl"
          />
          <div
            aria-hidden="true"
            className="pointer-events-none absolute -bottom-12 -left-10 h-52 w-52 rounded-full bg-[#9185be]/40 blur-2xl"
          />

          <div className="relative z-10">
            <Brand tone="light" />
          </div>

          <div className="relative z-10 space-y-4">
            <h2 className="text-3xl font-bold leading-tight tracking-tight">
              {asideTitle}
            </h2>
            <p className="max-w-xs text-sm leading-relaxed text-white/80">{asideText}</p>
          </div>

          {highlights.length > 0 && (
            <dl className="relative z-10 grid grid-cols-3 gap-4 border-t border-white/20 pt-6">
              {highlights.map((item) => (
                <div key={item.label}>
                  <dt className="text-xl font-bold">{item.value}</dt>
                  <dd className="text-[0.7rem] uppercase tracking-wide text-white/70">
                    {item.label}
                  </dd>
                </div>
              ))}
            </dl>
          )}
        </aside>

        <section className="p-7 sm:p-10 md:p-12">
          <div className="mb-8 md:hidden">
            <Brand tone="dark" />
          </div>
          {children}
        </section>
      </div>
    </div>
  );
}
