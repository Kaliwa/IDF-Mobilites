"use client";

import Link from "next/link";
import { AuthShell } from "../components/auth/AuthShell";
import { Field } from "../components/auth/Field";
import { Notice } from "../components/auth/Notice";
import { btnPrimary } from "../lib/ui";
import { useAuthForm } from "../lib/use-auth-form";

export default function RegisterPage() {
  const { email, setEmail, password, setPassword, error, isLoading, handleSubmit } =
    useAuthForm("register", "Création du compte impossible.");

  return (
    <AuthShell
      asideTitle="Rejoignez l'espace Comutitres."
      asideText="Créez votre compte une fois : il vous suivra tout au long de votre vie de voyageur, du forfait Imagine R au Navigo Senior."
      highlights={[
        { value: "Navigo", label: "annuel & senior" },
        { value: "Imagine R", label: "étudiant & scolaire" },
        { value: "TST", label: "tarif solidarité" },
      ]}
    >
      <div className="space-y-8">
        <header className="space-y-2">
          <h1 className="text-3xl font-bold tracking-tight text-anthracite">Créer un compte</h1>
          <p className="text-sm text-muted">
            Un compte unique pour souscrire et gérer tous vos titres.
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
            autoComplete="new-password"
            placeholder="8 caractères minimum"
          />

          <button type="submit" className={`${btnPrimary} w-full`} disabled={isLoading}>
            {isLoading ? "Création en cours…" : "Créer mon compte"}
          </button>

          {error ? <Notice tone="error">{error}</Notice> : null}
        </form>

        <p className="text-[0.7rem] leading-relaxed text-muted">
          En créant un compte, vous acceptez les conditions générales de vente et
          d&apos;utilisation et la politique de confidentialité d&apos;Île-de-France
          Mobilités. Vos données sont traitées dans le respect du RGPD.
        </p>

        <p className="text-sm text-muted">
          Vous avez déjà un compte ?{" "}
          <Link
            href="/login"
            className="font-semibold text-idf-interaction hover:text-idf-focus"
          >
            Se connecter
          </Link>
        </p>
      </div>
    </AuthShell>
  );
}
