import { AuthProvider } from "../lib/auth-context";
import { SiteHeader } from "../components/home/SiteHeader";
import { SiteFooter } from "../components/home/SiteFooter";
import { Simulator } from "../components/simulateur/Simulator";

export default function SimulateurPage() {
  return (
    <AuthProvider>
      <div className="home flex min-h-dvh flex-col">
        <SiteHeader />
        <main className="flex-1">
          <Simulator />
        </main>
        <SiteFooter />
      </div>
    </AuthProvider>
  );
}
