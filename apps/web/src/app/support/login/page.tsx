"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";
import { AuthShell } from "../../components/auth/AuthShell";
import { Field } from "../../components/auth/Field";
import { Notice } from "../../components/auth/Notice";
import { btnGhost, btnPrimary } from "../../lib/ui";
import {
  API_BASE_URL,
  ApiError,
  MeResponse,
  getErrorMessage,
  hasSupportAccess,
  readJson,
  setStoredToken,
} from "../../lib/auth";

export default function SupportLoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [isLoading, setIsLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setIsLoading(true);
    setError("");

    try {
      const loginResponse = await fetch(`${API_BASE_URL}/api/login`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
      });

      const loginData = await readJson<{ token: string } | ApiError>(loginResponse);

      if (!loginResponse.ok) {
        setError(getErrorMessage(loginData as ApiError | null, "Connexion support impossible."));
        return;
      }

      const token = (loginData as { token: string }).token;
      setStoredToken(token);

      const meResponse = await fetch(`${API_BASE_URL}/api/me`, {
        headers: { Authorization: `Bearer ${token}` },
        cache: "no-store",
      });

      const meData = await readJson<MeResponse | ApiError>(meResponse);

      if (!meResponse.ok) {
        setStoredToken("");
        setError(getErrorMessage(meData as ApiError | null, "Impossible de charger le profil support."));
        return;
      }

      const roles = (meData as MeResponse).user.roles;
      const canAccessSupport = hasSupportAccess(roles);

      if (!canAccessSupport) {
        setStoredToken("");
        setError("Ce compte n'a pas accès à l'espace support.");
        return;
      }

      router.push("/support/inbox");
      router.refresh();
    } catch {
      setError("Le service support est momentanément injoignable.");
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <AuthShell
      asideTitle="Espace support Comutitres."
      asideText="Accédez aux demandes clients, répondez aux conversations et suivez les incidents depuis un espace dédié aux équipes support."
      highlights={[
        { value: "SAV", label: "dédié" },
        { value: "24/7", label: "suivi" },
        { value: "1 vue", label: "clients" },
      ]}
    >
      <div className="space-y-8">
        <header className="space-y-2">
          <h1 className="text-3xl font-bold tracking-tight text-anthracite">Connexion support</h1>
          <p className="text-sm text-muted">
            Réservé aux équipes support et aux administrateurs.
          </p>
        </header>

        <form onSubmit={handleSubmit} className="space-y-5">
          <Field
            id="email"
            label="Adresse e-mail support"
            type="email"
            value={email}
            onChange={setEmail}
            autoComplete="email"
            placeholder="support@comutitres.fr"
          />
          <Field
            id="password"
            label="Mot de passe"
            type="password"
            value={password}
            onChange={setPassword}
            autoComplete="current-password"
            placeholder="••••••••"
          />

          <button type="submit" className={`${btnPrimary} w-full`} disabled={isLoading}>
            {isLoading ? "Connexion en cours…" : "Accéder à l'espace support"}
          </button>

          {error ? <Notice tone="error">{error}</Notice> : null}
        </form>

        <div className="rounded-2xl border border-white/60 bg-white/45 p-4 text-sm text-muted">
          <p className="font-semibold text-anthracite">Accès de démonstration</p>
          <p className="mt-1">
            Créez d&apos;abord le compte <strong>support@comutitres.fr</strong> via l&apos;inscription
            classique si vous voulez tester le rôle support dans cet environnement.
          </p>
        </div>

        <Link href="/login" className={`${btnGhost} w-full justify-center`}>
          Retour à la connexion usager
        </Link>
      </div>
    </AuthShell>
  );
}
