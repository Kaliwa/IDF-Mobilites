"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { Brand } from "../auth/Brand";
import { useAuth } from "../../lib/auth-context";
import { btnGhost, btnPrimary, glassNav } from "../../lib/ui";
import { SearchIcon, UserIcon } from "./icons";

const NAV = [
  { href: "#forfaits", label: "Titres & tarifs" },
  { href: "#acces", label: "Services" },
  { href: "#reseau", label: "Le réseau" },
  { href: "#aide", label: "Aide" },
];

export function SiteHeader() {
  const router = useRouter();
  const { user, loading, logout } = useAuth();

  async function handleLogout() {
    await logout();
    router.refresh();
  }

  return (
    <header className={glassNav}>
      <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <Link href="/" aria-label="Accueil Comutitres">
          <Brand tone="dark" />
        </Link>

        <nav className="hidden items-center gap-7 lg:flex" aria-label="Navigation principale">
          {NAV.map((item) => (
            <a
              key={item.href}
              href={item.href}
              className="text-sm font-medium text-anthracite/80 transition-colors hover:text-idf-interaction"
            >
              {item.label}
            </a>
          ))}
        </nav>

        <div className="flex items-center gap-2 sm:gap-3">
          <button
            type="button"
            className="hidden h-10 w-10 items-center justify-center rounded-xl border border-anthracite/10 bg-white/50 text-anthracite/70 transition-colors hover:text-idf-interaction sm:inline-flex"
            aria-label="Rechercher"
          >
            <SearchIcon width={18} height={18} />
          </button>

          {loading ? (
            <span className="h-10 w-28 animate-pulse rounded-xl bg-white/50" aria-hidden="true" />
          ) : user ? (
            <div className="flex items-center gap-2">
              <Link href="/" className={`${btnGhost} px-4 py-2.5`}>
                <UserIcon width={18} height={18} />
                Mon espace
              </Link>
              <button
                type="button"
                onClick={() => void handleLogout()}
                className={`${btnGhost} px-4 py-2.5`}
              >
                Déconnexion
              </button>
            </div>
          ) : (
            <div className="flex items-center gap-2">
              <Link href="/login" className={`${btnGhost} px-4 py-2.5`}>
                Connexion
              </Link>
              <Link href="/register" className={`${btnPrimary} px-4 py-2.5`}>
                Créer un compte
              </Link>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
