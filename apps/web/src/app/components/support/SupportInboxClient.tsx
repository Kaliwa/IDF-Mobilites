"use client";

import { type FormEvent, useCallback, useEffect, useRef, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { btnGhost, btnPrimary, chip, field, glass, sectionAccent } from "../../lib/ui";
import { useAuth } from "../../lib/auth-context";
import { formatMessagingDate } from "../../lib/messaging-api";
import { hasSupportAccess } from "../../lib/auth";
import { subscribeToMercure, supportConversationTopic } from "../../lib/mercure";
import {
  SupportDeskConversation,
  fetchSupportConversations,
  sendSupportReply,
  updateSupportConversationStatus,
} from "../../lib/support-api";
import { MessageSquareIcon } from "../home/icons";

export function SupportInboxClient() {
  const router = useRouter();
  const { user, loading: authLoading, logout } = useAuth();
  const [conversations, setConversations] = useState<SupportDeskConversation[]>([]);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [draft, setDraft] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const selectedIdRef = useRef<number | null>(null);
  const messagesContainerRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    selectedIdRef.current = selectedId;
  }, [selectedId]);

  useEffect(() => {
    if (!authLoading && (!user || !hasSupportAccess(user.roles))) {
      router.replace("/support/login");
    }
  }, [authLoading, user, router]);

  const load = useCallback(async (options?: { silent?: boolean }) => {
    if (!options?.silent) {
      setError("");
    }

    const result = await fetchSupportConversations();

    if (!options?.silent) {
      setLoading(false);
    }

    if (result.error) {
      if (!options?.silent) {
        setError(result.error);
      }
      return;
    }

    const items = result.data?.conversations ?? [];
    setConversations(items);
    setSelectedId((current) => {
      const preservedId = current ?? selectedIdRef.current;
      const nextSelected =
        preservedId && items.some((item) => item.id === preservedId)
          ? preservedId
          : (items[0]?.id ?? null);

      return nextSelected;
    });
  }, []);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      void load();
    }, 0);

    return () => {
      window.clearTimeout(timer);
    };
  }, [load]);

  useEffect(() => {
    return subscribeToMercure([supportConversationTopic()], (event) => {
      const updated = event.supportConversation as SupportDeskConversation | undefined;
      if (!updated) {
        return;
      }

      setConversations((current) => {
        const existing = current.some((item) => item.id === updated.id);
        const next = existing
          ? current.map((item) => (item.id === updated.id ? updated : item))
          : [updated, ...current];

        next.sort(
          (left, right) =>
            new Date(right.updatedAt ?? 0).getTime() - new Date(left.updatedAt ?? 0).getTime(),
        );

        return next;
      });

      setSelectedId((current) => current ?? updated.id);
    });
  }, []);

  const selected = conversations.find((item) => item.id === selectedId) ?? null;
  const canAccessSupport = !!user && hasSupportAccess(user.roles);
  const selectedMessagesCount = selected?.messages.length ?? 0;

  useEffect(() => {
    if (!selected || !messagesContainerRef.current) {
      return;
    }

    messagesContainerRef.current.scrollTop = messagesContainerRef.current.scrollHeight;
  }, [selected, selectedId, selectedMessagesCount]);

  async function handleLogout() {
    await logout();
    router.push("/support/login");
    router.refresh();
  }

  async function handleReply(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!selected || !draft.trim()) {
      return;
    }

    setSending(true);
    const result = await sendSupportReply(selected.id, draft.trim());
    setSending(false);

    if (result.error) {
      setError(result.error);
      return;
    }

    const updated = result.data?.conversation;
    if (!updated) {
      return;
    }

    setDraft("");
    setConversations((current) => {
      const next = current.map((item) => (item.id === updated.id ? updated : item));
      next.sort(
        (left, right) =>
          new Date(right.updatedAt ?? 0).getTime() - new Date(left.updatedAt ?? 0).getTime(),
      );
      return next;
    });
  }

  async function handleStatusChange(nextStatus: "open" | "resolved") {
    if (!selected) {
      return;
    }

    const result = await updateSupportConversationStatus(selected.id, nextStatus);

    if (result.error) {
      setError(result.error);
      return;
    }

    const updated = result.data?.conversation;
    if (!updated) {
      return;
    }

    setConversations((current) => {
      const next = current.map((item) => (item.id === updated.id ? updated : item));
      next.sort(
        (left, right) =>
          new Date(right.updatedAt ?? 0).getTime() - new Date(left.updatedAt ?? 0).getTime(),
      );
      return next;
    });
  }

  if (authLoading || loading) {
    return (
      <section className={`${glass} p-6 sm:p-8`}>
        <p className="text-sm text-muted">Chargement de l&apos;espace support…</p>
      </section>
    );
  }

  if (!canAccessSupport) {
    return (
      <section className={`${glass} p-6 sm:p-8`}>
        <p className="text-sm text-danger">
          Cet espace est réservé aux comptes support et administrateurs.
        </p>
      </section>
    );
  }

  return (
    <section className="space-y-8">
      <div className={`${glass} p-6 sm:p-8`}>
        <span className={sectionAccent} aria-hidden="true" />
        <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div className="space-y-3">
            <div className="inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1 text-xs font-semibold tracking-wide text-idf-focus">
              <MessageSquareIcon width={16} height={16} />
              Espace support
            </div>
            <h2 className="text-3xl font-bold tracking-tight text-anthracite sm:text-4xl">
              Gérez les demandes clientes dans une interface dédiée
            </h2>
            <p className="max-w-2xl text-sm leading-relaxed text-muted sm:text-base">
              Consultez toutes les conversations, répondez aux usagers et mettez à jour le
              statut des demandes sans passer par le front client.
            </p>
          </div>
          <div className="flex flex-wrap gap-3">
            <Link href="/" className={btnGhost}>
              Voir le site usager
            </Link>
            <button type="button" onClick={() => void handleLogout()} className={btnPrimary}>
              Déconnexion support
            </button>
          </div>
        </div>
      </div>

      {error ? (
        <div className="rounded-2xl border border-danger/20 bg-danger/8 px-4 py-3 text-sm text-danger">
          {error}
        </div>
      ) : null}

      <div className="grid gap-8 xl:grid-cols-[22rem_minmax(0,1fr)]">
        <section className={`${glass} p-5`}>
          <h3 className="text-xl font-bold text-anthracite">Demandes</h3>
          <div className="mt-5 space-y-3">
            {conversations.map((conversation) => (
              <button
                key={conversation.id}
                type="button"
                onClick={() => {
                  setSelectedId(conversation.id);
                }}
                className={`w-full rounded-2xl border px-4 py-4 text-left ${
                  selectedId === conversation.id
                    ? "border-idf-interaction/35 bg-idf-interaction/10"
                    : "border-white/60 bg-white/45"
                }`}
              >
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <p className="font-semibold text-anthracite">{conversation.subject}</p>
                    <p className="mt-1 text-sm text-muted">
                      {conversation.customer.email ?? "Client inconnu"}
                    </p>
                  </div>
                  <span className={chip}>{supportStatusLabel(conversation.status)}</span>
                </div>
                <p className="mt-2 text-xs text-muted">
                  {formatMessagingDate(conversation.updatedAt)}
                </p>
              </button>
            ))}
          </div>
        </section>

        <section className={`${glass} p-6 sm:p-8`}>
          {selected ? (
            <>
              <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <h3 className="text-2xl font-bold text-anthracite">{selected.subject}</h3>
                  <p className="mt-1 text-sm text-muted">
                    Client : {selected.customer.email ?? "inconnu"}
                  </p>
                </div>
                <span className={chip}>{supportStatusLabel(selected.status)}</span>
              </div>

              <div
                ref={messagesContainerRef}
                className="mt-6 max-h-[28rem] space-y-3 overflow-y-auto pr-2"
              >
                {selected.messages.map((message) => (
                  <div
                    key={message.id}
                    className={`rounded-2xl px-4 py-3 text-sm ${
                      message.author === "service"
                        ? "ml-auto max-w-2xl bg-idf-interaction/10 text-idf-focus"
                        : "bg-white/80 text-anthracite"
                    }`}
                  >
                    <p className="font-semibold">
                      {message.author === "service" ? "Support" : "Client"}
                    </p>
                    <p className="mt-1 leading-relaxed">{message.content}</p>
                    <p className="mt-2 text-xs text-muted">
                      {formatMessagingDate(message.sentAt)}
                    </p>
                  </div>
                ))}
              </div>

              <form onSubmit={(event) => void handleReply(event)} className="mt-6 space-y-4">
                <div className="space-y-2">
                  <label htmlFor="support-reply" className="text-sm font-medium text-anthracite">
                    Réponse support
                  </label>
                  <textarea
                    id="support-reply"
                    className={`${field} min-h-32 resize-y`}
                    placeholder="Réponse au client, action effectuée, demande de précision…"
                    value={draft}
                    onChange={(event) => setDraft(event.target.value)}
                  />
                </div>

                <div className="flex justify-end gap-3">
                  {selected.status === "resolved" ? (
                    <button
                      type="button"
                      className={btnGhost}
                      onClick={() => void handleStatusChange("open")}
                    >
                      Rouvrir la demande
                    </button>
                  ) : (
                    <button
                      type="button"
                      className={btnGhost}
                      onClick={() => void handleStatusChange("resolved")}
                    >
                      Marquer comme résolu
                    </button>
                  )}
                  <button type="button" className={btnGhost} onClick={() => setDraft("")}>
                    Effacer
                  </button>
                  <button type="submit" className={btnPrimary} disabled={sending}>
                    {sending ? "Envoi…" : "Répondre au client"}
                  </button>
                </div>
              </form>
            </>
          ) : (
            <p className="text-sm text-muted">Aucune conversation à afficher.</p>
          )}
        </section>
      </div>
    </section>
  );
}

function supportStatusLabel(status: string): string {
  switch (status) {
    case "open":
      return "En cours";
    case "waiting-user":
      return "En attente du client";
    case "resolved":
      return "Résolu";
    default:
      return "Suivi";
  }
}
