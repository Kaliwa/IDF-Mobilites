"use client";

import Link from "next/link";
import { type FormEvent, useEffect, useRef, useState } from "react";
import { isSupportUser } from "../../lib/auth";
import { useAuth } from "../../lib/auth-context";
import { subscribeToMercure, userConversationTopic } from "../../lib/mercure";
import { btnPrimary, chip, field, glass, iconBadge, sectionAccent } from "../../lib/ui";
import {
  MessagingConversation,
  MessagingOverview,
  fetchMessagingOverview,
  formatMessagingDate,
  sendConversationMessage,
} from "../../lib/messaging-api";
import { MessageSquareIcon } from "../home/icons";

export function MessagesClient() {
  const { user, loading: authLoading } = useAuth();
  const [overview, setOverview] = useState<MessagingOverview | null>(null);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(true);
  const [sendingConversationId, setSendingConversationId] = useState<number | null>(null);
  const [drafts, setDrafts] = useState<Record<number, string>>({});
  const messageContainersRef = useRef<Record<number, HTMLDivElement | null>>({});

  useEffect(() => {
    void loadOverview();
  }, []);

  useEffect(() => {
    if (!user || isSupportUser(user)) {
      return;
    }

    return subscribeToMercure([userConversationTopic(user.id)], (event) => {
      const updated = event.conversation as MessagingConversation | undefined;
      if (!updated) {
        return;
      }

      setOverview((current) => {
        if (!current) {
          return current;
        }

        const existing = current.conversations.some((item) => item.id === updated.id);
        const conversations = existing
          ? current.conversations.map((item) => (item.id === updated.id ? updated : item))
          : [updated, ...current.conversations];

        conversations.sort(
          (left, right) =>
            new Date(right.updatedAt ?? 0).getTime() - new Date(left.updatedAt ?? 0).getTime(),
        );

        return {
          ...current,
          conversations,
        };
      });
    });
  }, [user]);

  useEffect(() => {
    if (!overview) {
      return;
    }

    overview.conversations.forEach((conversation) => {
      const container = messageContainersRef.current[conversation.id];
      if (!container) {
        return;
      }

      container.scrollTop = container.scrollHeight;
    });
  }, [overview]);

  async function loadOverview() {
    setLoading(true);
    const result = await fetchMessagingOverview();
    setOverview(result.data);
    setError(result.error);
    setLoading(false);
  }

  async function handleReply(
    event: FormEvent<HTMLFormElement>,
    conversation: MessagingConversation,
  ) {
    event.preventDefault();

    const content = drafts[conversation.id]?.trim() ?? "";
    if (!content) {
      return;
    }

    setSendingConversationId(conversation.id);
    const result = await sendConversationMessage(conversation.id, content);
    setSendingConversationId(null);

    if (result.error) {
      setError(result.error);
      return;
    }

    setDrafts((current) => ({ ...current, [conversation.id]: "" }));
    setOverview((current) =>
      current && result.data
        ? {
            ...current,
            conversations: current.conversations.map((item) =>
              item.id === conversation.id ? result.data!.conversation : item,
            ),
          }
        : current,
    );
  }

  if (authLoading || loading) {
    return (
      <section className={`${glass} p-6 sm:p-8`}>
        <p className="text-sm text-muted">Chargement des messages…</p>
      </section>
    );
  }

  if (isSupportUser(user)) {
    return (
      <section className={`${glass} p-6 sm:p-8`}>
        <h2 className="text-2xl font-bold text-anthracite">Espace réservé aux usagers</h2>
        <p className="mt-2 max-w-2xl text-sm text-muted">
          Ce compte utilise l&apos;interface support. Les réponses aux clients se gèrent dans la
          boîte support dédiée.
        </p>
        <div className="mt-5">
          <Link href="/support/inbox" className={btnPrimary}>
            Ouvrir l&apos;espace support
          </Link>
        </div>
      </section>
    );
  }

  if (error && !overview) {
    return (
      <section className={`${glass} p-6 sm:p-8`}>
        <p className="text-sm text-danger">{error}</p>
        <button type="button" onClick={() => void loadOverview()} className={`${btnPrimary} mt-4`}>
          Réessayer
        </button>
      </section>
    );
  }

  if (!overview) {
    return <p>Aucune donnée disponible.</p>;
  }

  return (
    <section className="space-y-8">
      <div className={`${glass} overflow-hidden p-6 sm:p-8`}>
        <span className={sectionAccent} aria-hidden="true" />
        <div className="flex flex-col gap-4">
          <div className="inline-flex self-start items-center gap-2 rounded-full bg-white/70 px-3 py-1 text-xs font-semibold tracking-wide text-idf-focus">
            <MessageSquareIcon width={16} height={16} />
            Messagerie support
          </div>
          <h2 className="text-3xl font-bold tracking-tight text-anthracite sm:text-4xl">
            Retrouvez vos échanges dans un espace dédié
          </h2>
          <p className="max-w-2xl text-sm leading-relaxed text-muted sm:text-base">
            Vos messages sont séparés des notifications pour suivre plus facilement
            vos demandes et les réponses du support.
          </p>
        </div>
      </div>

      {error ? (
        <div className="rounded-2xl border border-danger/20 bg-danger/8 px-4 py-3 text-sm text-danger">
          {error}
        </div>
      ) : null}

      <section className={`${glass} p-6 sm:p-8`}>
        <div className="flex items-center gap-3">
          <span className={iconBadge}>
            <MessageSquareIcon width={18} height={18} />
          </span>
          <div>
            <h3 className="text-xl font-bold text-anthracite">Messages</h3>
            <p className="mt-1 text-sm text-muted">
              Vos échanges avec le support et le suivi de vos demandes.
            </p>
          </div>
        </div>

        <div className="mt-6 space-y-5">
          {overview.conversations.map((conversation) => (
            <article key={conversation.id} className="rounded-2xl border border-white/60 bg-white/45 p-5">
              <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <h4 className="text-lg font-semibold text-anthracite">{conversation.subject}</h4>
                  <p className="text-sm text-muted">
                    Mis à jour le {formatMessagingDate(conversation.updatedAt)}
                  </p>
                  <p className="mt-1 text-sm font-medium text-idf-focus">
                    {conversationPresenceLabel(conversation.status)}
                  </p>
                </div>
                <span className={chip}>{conversationStatusLabel(conversation.status)}</span>
              </div>

              <div
                ref={(node) => {
                  messageContainersRef.current[conversation.id] = node;
                }}
                className="mt-4 max-h-[24rem] space-y-3 overflow-y-auto pr-2"
              >
                {conversation.messages.map((message) => (
                  <div
                    key={message.id}
                    className={`rounded-2xl px-4 py-3 text-sm ${
                      message.author === "service"
                        ? "bg-white/80 text-anthracite"
                        : "ml-auto max-w-xl bg-idf-interaction/10 text-idf-focus"
                    }`}
                  >
                    <p className="font-semibold">
                      {message.author === "service" ? "Support" : "Vous"}
                    </p>
                    <p className="mt-1 leading-relaxed">{message.content}</p>
                    <p className="mt-2 text-xs text-muted">
                      {formatMessagingDate(message.sentAt)}
                    </p>
                  </div>
                ))}
              </div>

              <form
                onSubmit={(event) => void handleReply(event, conversation)}
                className="mt-4 flex flex-col gap-3"
              >
                <label
                  htmlFor={`reply-${conversation.id}`}
                  className="text-sm font-medium text-anthracite"
                >
                  Répondre au message
                </label>
                <textarea
                  id={`reply-${conversation.id}`}
                  className={`${field} min-h-28 resize-y`}
                  placeholder="Ajoutez un message pour le support…"
                  value={drafts[conversation.id] ?? ""}
                  onChange={(event) =>
                    setDrafts((current) => ({
                      ...current,
                      [conversation.id]: event.target.value,
                    }))
                  }
                />
                <div className="flex justify-end">
                  <button
                    type="submit"
                    className={btnPrimary}
                    disabled={sendingConversationId === conversation.id}
                  >
                    <MessageSquareIcon width={16} height={16} />
                    Envoyer
                  </button>
                </div>
              </form>
            </article>
          ))}
        </div>
      </section>
    </section>
  );
}

function conversationStatusLabel(status: string): string {
  switch (status) {
    case "open":
      return "En cours";
    case "waiting-user":
      return "En attente de votre retour";
    case "resolved":
      return "Résolu";
    default:
      return "Suivi en cours";
  }
}

function conversationPresenceLabel(status: string): string {
  switch (status) {
    case "open":
      return "Support en ligne";
    case "waiting-user":
      return "Votre réponse est attendue";
    case "resolved":
      return "Conversation clôturée";
    default:
      return "Support disponible";
  }
}
