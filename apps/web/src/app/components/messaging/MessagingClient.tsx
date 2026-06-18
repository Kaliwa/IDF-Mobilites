"use client";

import Link from "next/link";
import { type ReactNode, useEffect, useState } from "react";
import { isSupportUser } from "../../lib/auth";
import { useAuth } from "../../lib/auth-context";
import { subscribeToMercure, userNotificationsTopic } from "../../lib/mercure";
import { syncUnreadNotifications } from "../../lib/notifications-live";
import { btnGhost, btnPrimary, chip, glass, glassTile, iconBadge, sectionAccent } from "../../lib/ui";
import {
  MessagingLine,
  MessagingNotification,
  MessagingOverview,
  MessagingSubscription,
  createSubscription,
  deleteSubscription,
  fetchLineCatalog,
  fetchMessagingOverview,
  formatMessagingDate,
  markAllNotificationsAsRead,
  markNotificationAsRead,
  updateSubscription,
} from "../../lib/messaging-api";
import { CheckIcon } from "../home/icons";
import { RouteSign } from "../RouteSign";

function MessagingLineBadge({ line }: { line: MessagingLine }) {
  return (
    <span className="text-[1.3rem] leading-none" title={line.name}>
      <RouteSign route={line.code} />
    </span>
  );
}

export function MessagingClient() {
  const { user, loading: authLoading } = useAuth();
  const [overview, setOverview] = useState<MessagingOverview | null>(null);
  const [catalog, setCatalog] = useState<MessagingLine[]>([]);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(true);
  const [subscribing, setSubscribing] = useState<number | null>(null);

  useEffect(() => {
    void loadOverview();
  }, []);

  useEffect(() => {
    if (!user || isSupportUser(user)) {
      return;
    }

    return subscribeToMercure([userNotificationsTopic(user.id)], (event) => {
      const notification = event.notification as MessagingNotification | undefined;
      if (!notification) {
        return;
      }

      setOverview((current) => {
        if (!current) {
          return current;
        }

        const exists = current.notifications.some((item) => item.id === notification.id);
        if (exists) {
          return current;
        }

        const next = {
          ...current,
          notifications: [notification, ...current.notifications],
        };
        syncUnreadNotifications(next.notifications);
        return next;
      });
    });
  }, [user]);

  async function loadOverview() {
    setLoading(true);
    const [overviewResult, catalogResult] = await Promise.all([
      fetchMessagingOverview(),
      fetchLineCatalog(),
    ]);
    setOverview(overviewResult.data);
    setError(overviewResult.error);
    if (overviewResult.data) {
      syncUnreadNotifications(overviewResult.data.notifications);
    }
    setCatalog(catalogResult.data?.lines ?? []);
    setLoading(false);
  }

  async function handleRead(notificationId: number) {
    const result = await markNotificationAsRead(notificationId);

    if (result.error) {
      setError(result.error);
      return;
    }

    setOverview((current) => {
      if (!current || !result.data) {
        return current;
      }

      const next = {
        ...current,
        notifications: current.notifications.map((notification) =>
          notification.id === notificationId
            ? result.data!.notification
            : notification,
        ),
      };
      syncUnreadNotifications(next.notifications);
      return next;
    });
  }

  async function handleReadAll() {
    const result = await markAllNotificationsAsRead();

    if (result.error) {
      setError(result.error);
      return;
    }

    setOverview((current) =>
      current
        ? (() => {
            const next = {
              ...current,
              notifications: current.notifications.map((notification) => ({
                ...notification,
                isRead: true,
              })),
            };
            syncUnreadNotifications(next.notifications);
            return next;
          })()
        : current,
    );
  }

  async function handleToggleEnabled(subscription: MessagingSubscription) {
    const result = await updateSubscription(subscription.id, {
      enabled: !subscription.enabled,
    });

    if (result.error) {
      setError(result.error);
      return;
    }

    replaceSubscription(result.data?.subscription ?? null);
  }

  function replaceSubscription(subscription: MessagingSubscription | null) {
    if (!subscription) {
      return;
    }

    setOverview((current) =>
      current
        ? {
            ...current,
            subscriptions: current.subscriptions.map((item) =>
              item.id === subscription.id ? subscription : item,
            ),
          }
        : current,
    );
  }

  async function handleDeleteSubscription(subscriptionId: number) {
    const result = await deleteSubscription(subscriptionId);

    if (result.error) {
      setError(result.error);
      return;
    }

    setOverview((current) => {
      if (!current) {
        return current;
      }

      const removed = current.subscriptions.find((item) => item.id === subscriptionId);
      const next = {
        ...current,
        subscriptions: current.subscriptions.filter((item) => item.id !== subscriptionId),
      };

      if (removed) {
        setCatalog((prev) =>
          prev.some((line) => line.id === removed.line.id)
            ? prev
            : [...prev, removed.line].sort((a, b) => a.name.localeCompare(b.name)),
        );
      }

      return next;
    });
  }

  async function handleSubscribe(lineId: number) {
    setSubscribing(lineId);
    const result = await createSubscription(lineId);
    setSubscribing(null);

    if (result.error) {
      setError(result.error);
      return;
    }

    const subscription = result.data?.subscription;
    if (!subscription) {
      return;
    }

    setCatalog((prev) => prev.filter((line) => line.id !== lineId));
    setOverview((current) =>
      current
        ? {
            ...current,
            subscriptions: [...current.subscriptions, subscription],
          }
        : current,
    );
  }

  if (authLoading || loading) {
    return (
      <section className={`${glass} p-6 sm:p-8`}>
        <p className="text-sm text-muted">Chargement de la messagerie…</p>
      </section>
    );
  }

  if (isSupportUser(user)) {
    return (
      <section className={`${glass} p-6 sm:p-8`}>
        <h2 className="text-2xl font-bold text-anthracite">Espace réservé aux usagers</h2>
        <p className="mt-2 max-w-2xl text-sm text-muted">
          Ce compte appartient à l&apos;équipe support. La gestion des demandes et incidents se
          fait depuis l&apos;interface support dédiée.
        </p>
        <div className="mt-5">
          <a
            href={`${process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000"}/admin`}
            className={btnPrimary}
          >
            Ouvrir l&apos;espace support
          </a>
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

  const notifications = [...overview.notifications].sort((left, right) => {
    if (left.isRead !== right.isRead) {
      return left.isRead ? 1 : -1;
    }

    const priorityOrder = { high: 0, medium: 1, low: 2 };
    const leftPriority =
      priorityOrder[left.priority as keyof typeof priorityOrder] ?? 99;
    const rightPriority =
      priorityOrder[right.priority as keyof typeof priorityOrder] ?? 99;

    if (leftPriority !== rightPriority) {
      return leftPriority - rightPriority;
    }

    return (
      new Date(right.createdAt ?? 0).getTime() -
      new Date(left.createdAt ?? 0).getTime()
    );
  });

  const unreadCount = notifications.filter((item) => !item.isRead).length;
  const activeSubscriptions = overview.subscriptions.filter((item) => item.enabled).length;

  return (
    <section className="space-y-8">
      <div className={`${glass} overflow-hidden p-6 sm:p-8`}>
        <span className={sectionAccent} aria-hidden="true" />
        <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div className="max-w-2xl">
            <div className="inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1 text-xs font-semibold tracking-wide text-idf-focus">
              Centre de messagerie
            </div>
            <h2 className="mt-4 text-3xl font-bold tracking-tight text-anthracite sm:text-4xl">
              Suivez vos alertes et vos lignes au même endroit
            </h2>
            <p className="mt-3 max-w-xl text-sm leading-relaxed text-muted sm:text-base">
              Incidents sur vos lignes favorites, renouvellements, paiements et
              informations utiles sur votre abonnement.
            </p>
          </div>

          <div className="grid gap-3 min-[420px]:grid-cols-2">
            <StatCard icon={<img src="/images/illustrations/illu-infos-trafic.svg" width={22} height={22} aria-hidden="true" />} label="Non lues" value={String(unreadCount)} />
            <StatCard icon={<img src="/images/illustrations/illu-app.svg" width={22} height={22} aria-hidden="true" />} label="Lignes suivies" value={String(activeSubscriptions)} />
          </div>
        </div>
      </div>

      {error ? (
        <div className="rounded-2xl border border-danger/20 bg-danger/8 px-4 py-3 text-sm text-danger">
          {error}
        </div>
      ) : null}

      <div className="grid gap-8 xl:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.65fr)]">
        <div className="space-y-8">
          <section className={`${glass} p-6 sm:p-8`}>
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h3 className="text-xl font-bold text-anthracite">Notifications</h3>
                <p className="mt-1 text-sm text-muted">
                  Les alertes les plus utiles pour éviter les ruptures de parcours.
                </p>
              </div>
              <button
                type="button"
                onClick={() => void handleReadAll()}
                className={`${btnGhost} self-start sm:self-auto`}
              >
                <CheckIcon width={16} height={16} />
                Tout marquer comme lu
              </button>
            </div>

            <div className="mt-6 space-y-4">
              {notifications.map((notification) => (
                <NotificationItem
                  key={notification.id}
                  notification={notification}
                  onRead={handleRead}
                />
              ))}
            </div>
          </section>

        </div>

        <div className="space-y-8">
          <section className={`${glass} p-6 sm:p-8`}>
            <div>
              <h3 className="text-xl font-bold text-anthracite">Lignes suivies</h3>
              <p className="mt-1 text-sm text-muted">
                Choisissez où recevoir les alertes utiles.
              </p>
            </div>

            <div className="mt-6 space-y-4">
              {overview.subscriptions.length === 0 ? (
                <p className="text-sm text-muted">Aucune ligne suivie.</p>
              ) : null}
              {overview.subscriptions.map((subscription) => (
                <article key={subscription.id} className={`${glassTile} p-5`}>
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex items-center gap-2">
                      <MessagingLineBadge line={subscription.line} />
                      <h4 className="text-lg font-semibold text-anthracite">
                        {subscription.line.name}
                      </h4>
                    </div>
                    <span className={chip}>
                      {subscription.enabled ? "Suivie" : "En pause"}
                    </span>
                  </div>

                  <button
                    type="button"
                    onClick={() => void handleToggleEnabled(subscription)}
                    className={`${subscription.enabled ? btnGhost : btnPrimary} mt-4 self-start`}
                  >
                    {subscription.enabled ? "Suspendre les alertes" : "Réactiver les alertes"}
                  </button>
                  <button
                    type="button"
                    onClick={() => void handleDeleteSubscription(subscription.id)}
                    className={`${btnGhost} mt-3 self-start border-danger/25 text-danger hover:border-danger/45`}
                  >
                    Supprimer cette ligne
                  </button>
                </article>
              ))}
            </div>
          </section>

          {catalog.length > 0 ? (
            <section className={`${glass} p-6 sm:p-8`}>
              <div>
                <h3 className="text-xl font-bold text-anthracite">Ajouter une ligne</h3>
                <p className="mt-1 text-sm text-muted">
                  Recevez les perturbations en temps réel sur les lignes de votre choix.
                </p>
              </div>

              <div className="mt-6 space-y-3">
                {catalog.map((line) => (
                  <div
                    key={line.id}
                    className="flex items-center justify-between gap-4 rounded-2xl border border-white/60 bg-white/45 px-4 py-3"
                  >
                    <div className="flex items-center gap-2">
                      <MessagingLineBadge line={line} />
                      <p className="font-semibold text-anthracite">{line.name}</p>
                    </div>
                    <button
                      type="button"
                      disabled={subscribing === line.id}
                      onClick={() => void handleSubscribe(line.id)}
                      className={btnPrimary}
                    >
                      {subscribing === line.id ? "…" : "Suivre"}
                    </button>
                  </div>
                ))}
              </div>
            </section>
          ) : null}
        </div>
      </div>
    </section>
  );
}

function NotificationItem({
  notification,
  onRead,
}: {
  notification: MessagingNotification;
  onRead: (notificationId: number) => Promise<void>;
}) {
  const actionHref = notification.category === "support" ? "/messages" : "/notifications";

  return (
    <article
      className={`rounded-2xl border p-5 ${
        notification.isRead
          ? "border-white/60 bg-white/45"
          : "border-idf-interaction/20 bg-idf-interaction/8"
      }`}
    >
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div className="max-w-2xl">
          <div className="flex flex-wrap items-center gap-2">
            <span className={chip}>{categoryLabel(notification.category)}</span>
            {notification.line ? (
              <span className="inline-flex items-center gap-1.5">
                <MessagingLineBadge line={notification.line} />
                <span className="text-xs font-medium text-muted">{notification.line.name}</span>
              </span>
            ) : null}
          </div>
          <h4 className="mt-3 text-lg font-semibold text-anthracite">{notification.title}</h4>
          <p className="mt-1 text-sm text-muted">
            {formatMessagingDate(notification.createdAt)}
          </p>
          <p className="mt-3 text-sm leading-relaxed text-anthracite/85">
            {notification.body}
          </p>
          {notification.actionLabel ? (
            <div className="mt-3">
              <Link
                href={actionHref}
                className="text-sm font-medium text-idf-focus hover:text-idf-interaction"
              >
                Action recommandée : {notification.actionLabel}
              </Link>
            </div>
          ) : null}
        </div>

        {!notification.isRead ? (
          <button type="button" onClick={() => void onRead(notification.id)} className={btnGhost}>
            <CheckIcon width={16} height={16} />
            Marquer comme lu
          </button>
        ) : (
          <span className={chip}>Déjà lue</span>
        )}
      </div>
    </article>
  );
}

function categoryLabel(category: string): string {
  switch (category) {
    case "incident":
      return "Incident";
    case "renewal":
      return "Renouvellement";
    case "payment":
      return "Paiement";
    case "support":
      return "Support";
    case "account":
      return "Compte";
    default:
      return "Information";
  }
}

function StatCard({
  icon,
  label,
  value,
}: {
  icon: ReactNode;
  label: string;
  value: string;
}) {
  return (
    <div className="rounded-2xl border border-white/60 bg-white/55 px-4 py-4 shadow-[0_12px_30px_-22px_rgba(0,80,170,0.55)]">
      <span className={iconBadge}>{icon}</span>
      <p className="mt-3 text-2xl font-bold text-anthracite">{value}</p>
      <p className="text-sm text-muted">{label}</p>
    </div>
  );
}
