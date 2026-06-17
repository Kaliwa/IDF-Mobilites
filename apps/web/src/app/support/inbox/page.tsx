import { SupportInboxClient } from "../../components/support/SupportInboxClient";
import { AuthProvider } from "../../lib/auth-context";

export default function SupportInboxPage() {
  return (
    <AuthProvider>
      <main className="home min-h-dvh px-4 py-8 sm:px-6 lg:px-8">
        <div className="mx-auto w-full max-w-7xl">
          <SupportInboxClient />
        </div>
      </main>
    </AuthProvider>
  );
}
