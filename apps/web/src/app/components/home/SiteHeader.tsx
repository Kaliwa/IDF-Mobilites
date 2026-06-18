"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { Brand } from "../auth/Brand";
import { useAuth } from "../../lib/auth-context";
import { isSupportUser } from "../../lib/auth";
import { useUnreadNotificationsCount } from "../../lib/notifications-live";
import { btnGhost, btnPrimary, glassNav } from "../../lib/ui";
import { BellIcon, MessageSquareIcon, SearchIcon, UserIcon } from "./icons";
import { MobileMenu } from "./MobileMenu";

const NAV = [
  { href: "/simulateur", label: "Simulateur" },
  { href: "/#acces", label: "Services" },
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
      <div className="mx-auto flex w-full max-w-6xl items-center gap-2 px-4 py-3 sm:px-6 xl:gap-4">
        <Link
          href="/"
          aria-label="Accueil Comutitres"
          className="min-w-0 flex-1 xl:flex-none"
        >
          <Brand tone="dark" compact />
        </Link>

        <nav
          className="mx-auto hidden items-center gap-0.5 text-sm xl:flex"
          aria-label="Navigation principale"
        >
          {NAV.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className="shrink-0 whitespace-nowrap rounded-lg px-3 py-2 font-medium text-anthracite/80 transition-colors hover:bg-idf-blue-light/40 hover:text-idf-interaction focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-idf-focus"
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
                className="relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-anthracite/70 transition-colors hover:border-idf-interaction hover:text-idf-interaction"
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
                className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-anthracite/70 transition-colors hover:border-idf-interaction hover:text-idf-interaction"
                aria-label="Ouvrir les messages"
              >
                <MessageSquareIcon width={18} height={18} />
              </Link>
            </>
          ) : null}

          <button
            type="button"
            className="hidden h-10 w-10 items-center justify-center rounded-lg border border-border bg-surface text-anthracite/70 transition-colors hover:border-idf-interaction hover:text-idf-interaction xl:inline-flex"
            aria-label="Rechercher"
          >
            <SearchIcon width={18} height={18} />
          </button>

          {loading ? (
            <span
              className="hidden h-10 w-28 animate-pulse rounded-lg bg-border/60 xl:block"
              aria-hidden="true"
            />
          ) : user ? (
            <div className="hidden items-center gap-2 xl:flex">
              {supportUser ? (
                <Link href="/support/inbox" className={`${btnPrimary} whitespace-nowrap px-3.5 py-2`}>
                  <MessageSquareIcon width={18} height={18} />
                  <span>Espace support</span>
                </Link>
              ) : (
                <Link href="/" className={`${btnGhost} whitespace-nowrap px-3.5 py-2`}>
                  <UserIcon width={18} height={18} />
                  <span>Mon espace</span>
                </Link>
              )}
              <button
                type="button"
                onClick={() => void handleLogout()}
                className={`${btnGhost} whitespace-nowrap px-3.5 py-2`}
              >
                Déconnexion
              </button>
            </div>
          ) : (
            <div className="hidden items-center gap-2 xl:flex">
              <Link href="/login" className={`${btnGhost} whitespace-nowrap px-4 py-2`}>
                Connexion
              </Link>
              <Link href="/register" className={`${btnPrimary} whitespace-nowrap px-4 py-2`}>
                Créer un compte
              </Link>
            </div>
          )}

          <MobileMenu nav={NAV} />
        </div>
      </div>
    </header>
  );
}
