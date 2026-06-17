import Link from "next/link";
import { SiteFooter } from "../components/home/SiteFooter";
import { SiteHeader } from "../components/home/SiteHeader";
import { MessagesClient } from "../components/messaging/MessagesClient";
import { AuthProvider } from "../lib/auth-context";

export default function MessagesPage() {
  return (
    <AuthProvider>
      <div className="home flex min-h-dvh flex-col">
        <SiteHeader />
        <main className="flex-1">
          <section className="w-full px-4 py-10 sm:px-6 sm:py-14 xl:px-10">
            <p className="mb-4 text-sm text-muted">
              <Link href="/" className="transition-colors hover:text-idf-interaction">
                Retour à l&apos;accueil
              </Link>
            </p>
            <MessagesClient />
          </section>
        </main>
        <SiteFooter />
      </div>
    </AuthProvider>
  );
}
