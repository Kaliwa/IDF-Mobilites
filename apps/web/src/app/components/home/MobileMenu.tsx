"use client";

import {
  useCallback,
  useEffect,
  useRef,
  useState,
  useSyncExternalStore,
} from "react";
import { createPortal } from "react-dom";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useAuth } from "../../lib/auth-context";
import { btnGhost, btnPrimary } from "../../lib/ui";
import { ArrowRightIcon, CloseIcon, MenuIcon, UserIcon } from "./icons";

type NavItem = { href: string; label: string };

const FOCUSABLE =
  'a[href], button:not([disabled]), input, [tabindex]:not([tabindex="-1"])';

export function MobileMenu({ nav }: { nav: NavItem[] }) {
  const [open, setOpen] = useState(false);
  const router = useRouter();
  const { user, loading, logout } = useAuth();

  const panelRef = useRef<HTMLDivElement>(null);
  const triggerRef = useRef<HTMLButtonElement>(null);
  const wasOpen = useRef(false);

  // Le portail cible `document.body` : indisponible au rendu serveur.
  // `useSyncExternalStore` renvoie false côté serveur, true une fois monté.
  const mounted = useSyncExternalStore(
    () => () => {},
    () => true,
    () => false,
  );

  const close = useCallback(() => setOpen(false), []);

  // Verrouillage du défilement + fermeture par la touche Échap pendant l'ouverture.
  useEffect(() => {
    if (!open) return;

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") close();
    };
    document.addEventListener("keydown", onKeyDown);

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";

    return () => {
      document.removeEventListener("keydown", onKeyDown);
      document.body.style.overflow = previousOverflow;
    };
  }, [open, close]);

  // Déplacement du focus à l'ouverture, retour sur le déclencheur à la fermeture.
  useEffect(() => {
    if (open) {
      panelRef.current?.querySelector<HTMLElement>(FOCUSABLE)?.focus();
    } else if (wasOpen.current) {
      triggerRef.current?.focus();
    }
    wasOpen.current = open;
  }, [open]);

  // Piège de focus : maintient la tabulation à l'intérieur du panneau ouvert.
  function trapFocus(event: React.KeyboardEvent) {
    if (event.key !== "Tab" || !panelRef.current) return;

    const items = Array.from(
      panelRef.current.querySelectorAll<HTMLElement>(FOCUSABLE),
    );
    if (items.length === 0) return;

    const first = items[0];
    const last = items[items.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  async function handleLogout() {
    close();
    await logout();
    router.refresh();
  }

  return (
    <>
      <button
        ref={triggerRef}
        type="button"
        onClick={() => setOpen(true)}
        className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-anthracite/10 bg-white/50 text-anthracite/80 transition-colors hover:text-idf-interaction focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-idf-focus lg:hidden"
        aria-label="Ouvrir le menu"
        aria-haspopup="dialog"
        aria-expanded={open}
        aria-controls="mobile-menu"
      >
        <MenuIcon width={22} height={22} />
      </button>

      {/* Rendu via portail dans `document.body` : le header porte un
          `backdrop-filter`, qui ferait de lui le bloc conteneur d'un descendant
          `fixed`. Le portail replace le drawer au niveau du viewport, au-dessus
          du reste de la page. Conteneur plein écran qui clippe le panneau
          hors-champ pour éviter le débordement horizontal. */}
      {mounted &&
        createPortal(
          <div
            className={`fixed inset-0 z-[60] overflow-hidden lg:hidden ${
              open ? "" : "pointer-events-none"
            }`}
          >
            {/* Voile : ferme au clic, masqué tant que le menu est fermé. */}
            <div
              onClick={close}
              aria-hidden="true"
              className={`absolute inset-0 bg-idf-focus/25 backdrop-blur-sm transition-opacity duration-300 motion-reduce:transition-none ${
                open ? "opacity-100" : "opacity-0"
              }`}
            />

            <div
              ref={panelRef}
              id="mobile-menu"
              role="dialog"
              aria-modal="true"
              aria-label="Menu principal"
              inert={!open}
              onKeyDown={trapFocus}
              className={`absolute inset-y-0 right-0 flex w-[min(20rem,85vw)] flex-col border-l border-white/50 bg-white/85 shadow-[0_24px_60px_-20px_rgba(0,80,170,0.55)] backdrop-blur-2xl backdrop-saturate-150 transition-transform duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:transition-none ${
                open ? "translate-x-0" : "translate-x-full"
              }`}
            >
              <div className="flex items-center justify-between border-b border-anthracite/10 px-5 py-4">
                <span className="text-sm font-semibold tracking-wide text-anthracite/70 uppercase">
                  Menu
                </span>
                <button
                  type="button"
                  onClick={close}
                  className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-anthracite/10 bg-white/60 text-anthracite/80 transition-colors hover:text-idf-interaction focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-idf-focus"
                  aria-label="Fermer le menu"
                >
                  <CloseIcon width={20} height={20} />
                </button>
              </div>

              <nav
                className="flex flex-col gap-1 px-3 py-4"
                aria-label="Navigation principale"
              >
                {nav.map((item) => (
                  <a
                    key={item.href}
                    href={item.href}
                    onClick={close}
                    className="flex items-center justify-between rounded-xl px-3 py-3 text-base font-medium text-anthracite/85 transition-colors hover:bg-idf-interaction/10 hover:text-idf-interaction focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-idf-focus"
                  >
                    {item.label}
                    <ArrowRightIcon
                      width={18}
                      height={18}
                      className="text-anthracite/30"
                    />
                  </a>
                ))}
              </nav>

              <div className="mt-auto border-t border-anthracite/10 px-5 py-5">
                {loading ? (
                  <span
                    className="block h-12 w-full animate-pulse rounded-xl bg-white/60"
                    aria-hidden="true"
                  />
                ) : user ? (
                  <div className="flex flex-col gap-2">
                    <Link
                      href="/"
                      onClick={close}
                      className={`${btnGhost} w-full px-4 py-3`}
                    >
                      <UserIcon width={18} height={18} />
                      Mon espace
                    </Link>
                    <button
                      type="button"
                      onClick={() => void handleLogout()}
                      className={`${btnGhost} w-full px-4 py-3`}
                    >
                      Déconnexion
                    </button>
                  </div>
                ) : (
                  <div className="flex flex-col gap-2">
                    <Link
                      href="/register"
                      onClick={close}
                      className={`${btnPrimary} w-full px-4 py-3`}
                    >
                      Créer un compte
                    </Link>
                    <Link
                      href="/login"
                      onClick={close}
                      className={`${btnGhost} w-full px-4 py-3`}
                    >
                      Connexion
                    </Link>
                  </div>
                )}
              </div>
            </div>
          </div>,
          document.body,
        )}
    </>
  );
}
