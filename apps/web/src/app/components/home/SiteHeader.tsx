"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { Brand } from "../auth/Brand";
import { useAuth } from "../../lib/auth-context";
import { isSupportUser } from "../../lib/auth";
import { useUnreadNotificationsCount } from "../../lib/notifications-live";
import { btnGhost, btnPrimary, glassNav } from "../../lib/ui";
import { BellIcon, MessageSquareIcon, SearchIcon, UserIcon } from "./icons";

const NAV = [
  { href: "/simulateur", label: "Simulateur" },
  { href: "/#forfaits", label: "Titres & tarifs" },
  { href: "/#acces", label: "Services" },
  { href: "/#aide", label: "Aide" },
  { href: "/trajets", label: "Mes trajets" },
];

export function SiteHeader() {
  const router = useRouter();
  const { user, loading, logout } = useAuth();
  const supportUser = isSupportUser(user);
  const unreadNotifications = useUnreadNotificationsCount(
    user && !supportUser ? user.id : null,
  );

  async function handleLogout() {
    await logout();
    router.refresh();
  }

  return (
    <header className={`${glassNav} overflow-x-clip`}>
      <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-2 px-4 py-3 sm:px-6 lg:gap-4">
        <Link href="/" aria-label="Accueil Comutitres" className="min-w-0 flex-1">
          <Brand tone="dark" compact />
        </Link>

        <nav
          className="hidden items-center gap-7 text-sm lg:flex lg:justify-center"
          aria-label="Navigation principale"
        >
          {NAV.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className="shrink-0 font-medium text-anthracite/80 transition-colors hover:text-idf-interaction"
            >
              {item.label}
            </Link>
          ))}
        </nav>

        <div className="flex items-center gap-2 sm:gap-3">
          {user && !supportUser ? (
            <>
              <Link
                href="/notifications"
                className="relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-anthracite/10 bg-white/50 text-anthracite/70 transition-colors hover:text-idf-interaction"
                aria-label="Ouvrir les notifications"
              >
                <BellIcon width={18} height={18} />
                {unreadNotifications > 0 ? (
                  <span className="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-danger px-1 text-[11px] font-bold leading-none text-white">
                    {unreadNotifications > 9 ? "9+" : unreadNotifications}
                  </span>
                ) : null}
              </Link>
              <Link
                href="/messages"
                className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-anthracite/10 bg-white/50 text-anthracite/70 transition-colors hover:text-idf-interaction"
                aria-label="Ouvrir les messages"
              >
                <MessageSquareIcon width={18} height={18} />
              </Link>
            </>
          ) : null}

          <button
            type="button"
            className="hidden h-10 w-10 items-center justify-center rounded-xl border border-anthracite/10 bg-white/50 text-anthracite/70 transition-colors hover:text-idf-interaction lg:inline-flex"
            aria-label="Rechercher"
          >
            <SearchIcon width={18} height={18} />
          </button>

          {loading ? (
            <span
              className="hidden h-10 w-28 animate-pulse rounded-xl bg-white/50 lg:block"
              aria-hidden="true"
            />
          ) : user ? (
            <div className="hidden items-center gap-2 lg:flex">
              {supportUser ? (
                <Link href="/support/inbox" className={`${btnPrimary} px-4 py-2.5`}>
                  <MessageSquareIcon width={18} height={18} />
                  <span className="truncate">Espace support</span>
                </Link>
              ) : (
                <Link href="/" className={`${btnGhost} px-4 py-2.5`}>
                  <UserIcon width={18} height={18} />
                  <span className="truncate">Mon espace</span>
                </Link>
              )}
              <button
                type="button"
                onClick={() => void handleLogout()}
                className={`${btnGhost} px-4 py-2.5`}
              >
                Déconnexion
              </button>
            </div>
          ) : (
            <div className="hidden items-center gap-2 lg:flex">
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
