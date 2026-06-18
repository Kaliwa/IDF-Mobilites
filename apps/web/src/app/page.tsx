import { AuthProvider } from "./lib/auth-context";
import { SiteHeader } from "./components/home/SiteHeader";
import { Hero } from "./components/home/Hero";
import { OrientationBanner } from "./components/home/OrientationBanner";
import { QuickAccess } from "./components/home/QuickAccess";
import { SiteFooter } from "./components/home/SiteFooter";
import { JourneySection } from "./components/trips/JourneySection";

export default function HomePage() {
  return (
    <AuthProvider>
      <div className="home flex min-h-dvh flex-col">
        <SiteHeader />
        <main className="flex-1">
          <OrientationBanner />
          <Hero />
          <JourneySection />
          <QuickAccess />
        </main>
        <SiteFooter />
      </div>
    </AuthProvider>
  );
}
