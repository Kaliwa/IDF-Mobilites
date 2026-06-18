"use client";

import * as Sentry from "@sentry/nextjs";

export default function GlitchTipTestPage() {
  return (
    <main className="mx-auto flex min-h-screen max-w-lg flex-col justify-center gap-4 p-6">
      <h1 className="text-2xl font-semibold">Test GlitchTip</h1>
      <p className="text-sm text-neutral-600">
        Envoie une erreur de test vers ton instance GlitchTip (SDK Sentry).
      </p>
      <button
        type="button"
        className="rounded-md bg-black px-4 py-2 text-white"
        onClick={() => {
          Sentry.captureException(
            new Error("GlitchTip test error from IDF Mobilites"),
          );
        }}
      >
        Envoyer une erreur test
      </button>
    </main>
  );
}
