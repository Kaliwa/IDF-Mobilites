import { AuthStatus } from "./components/AuthStatus";
import { LogoutButton } from "./components/LogoutButton";
import { NavLinks } from "./components/NavLinks";

export default function HomePage() {
  return (
    <main>
      <h1>Auth status</h1>
      <NavLinks />
      <AuthStatus />
      <LogoutButton />
    </main>
  );
}
