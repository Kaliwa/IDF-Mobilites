"use client";

import Link from "next/link";
import { AuthShell } from "../components/auth/AuthShell";
import { Field } from "../components/auth/Field";
import { Notice } from "../components/auth/Notice";
import { btnPrimary } from "../lib/ui";
import { useAuthForm } from "../lib/use-auth-form";

export default function LoginPage() {
  const { email, setEmail, password, setPassword, error, isLoading, handleSubmit } =
    useAuthForm("login", "Connexion impossible.");

  return (
    <AuthShell
      asideTitle="Vos titres Navigo, simplement."
      asideText="Souscrivez, renouvelez et gérez vos forfaits Île-de-France Mobilités depuis un espace unique, sécurisé et accessible à tous."
      highlights={[
        { value: "9,4 M", label: "déplacements / jour" },
        { value: "6,7 M", label: "clients franciliens" },
        { value: "100 %", label: "en ligne" },
      ]}
    >
      <div className="space-y-8">
        <header className="space-y-2">
          <h1 className="text-3xl font-bold tracking-tight text-anthracite">Connexion</h1>
          <p className="text-sm text-muted">
            Accédez à votre espace de souscription Comutitres.
          </p>
        </header>

        <form onSubmit={handleSubmit} className="space-y-5">
          <Field
            id="email"
            label="Adresse e-mail"
            type="email"
            value={email}
            onChange={setEmail}
            autoComplete="email"
            placeholder="prenom.nom@exemple.fr"
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
            {isLoading ? "Connexion en cours…" : "Se connecter"}
          </button>

          {error ? <Notice tone="error">{error}</Notice> : null}
        </form>

        <p className="text-sm text-muted">
          Pas encore de compte ?{" "}
          <Link
            href="/register"
            className="font-semibold text-idf-interaction hover:text-idf-focus"
          >
            Créer un compte
          </Link>
        </p>
      </div>
    </AuthShell>
  );
}
