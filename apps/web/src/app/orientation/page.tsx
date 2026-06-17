import type { Metadata } from "next";
import { AuthProvider } from "../lib/auth-context";
import { SiteHeader } from "../components/home/SiteHeader";
import { SiteFooter } from "../components/home/SiteFooter";
import { OrientationWizard } from "../components/orientation/OrientationWizard";

export const metadata: Metadata = {
  title: "Trouvez votre offre — Comutitres",
  description:
    "Un parcours guidé par événement de vie pour trouver l'offre de transport et les aides adaptées à votre situation.",
};

export default function OrientationPage() {
  return (
    <AuthProvider>
      <div className="home flex min-h-dvh flex-col">
        <SiteHeader />
        <main className="flex-1">
          <OrientationWizard />
        </main>
        <SiteFooter />
      </div>
    </AuthProvider>
  );
}
