import { AuthProvider } from "./lib/auth-context";
import { SiteHeader } from "./components/home/SiteHeader";
import { Hero } from "./components/home/Hero";
import { QuickAccess } from "./components/home/QuickAccess";
import { Forfaits } from "./components/home/Forfaits";
import { NetworkStats } from "./components/home/NetworkStats";
import { SiteFooter } from "./components/home/SiteFooter";

export default function HomePage() {
  return (
    <AuthProvider>
      <div className="home flex min-h-dvh flex-col">
        <SiteHeader />
        <main className="flex-1">
          <Hero />
          <QuickAccess />
          <Forfaits />
          <NetworkStats />
        </main>
        <SiteFooter />
      </div>
    </AuthProvider>
  );
}
