"use client";

import { useEffect, useState } from "react";
import { getStoredToken } from "./auth";
import {
  type MessagingNotification,
  fetchMessagingOverview,
} from "./messaging-api";
import { subscribeToMercure, userNotificationsTopic } from "./mercure";

const UNREAD_NOTIFICATIONS_EVENT = "idf-notifications-unread";

function dispatchUnreadCount(count: number) {
  if (typeof window === "undefined") {
    return;
  }

  window.setTimeout(() => {
    window.dispatchEvent(
      new CustomEvent<number>(UNREAD_NOTIFICATIONS_EVENT, { detail: count }),
    );
  }, 0);
}

export function syncUnreadNotifications(notifications: MessagingNotification[]) {
  dispatchUnreadCount(notifications.filter((item) => !item.isRead).length);
}

export function useUnreadNotificationsCount(userId: number | null) {
  const [count, setCount] = useState(0);
  const enabled = !!userId && !!getStoredToken();

  useEffect(() => {
    if (!enabled || !userId) {
      return;
    }

    let active = true;

    void fetchMessagingOverview().then((result) => {
      if (!active || !result.data) {
        return;
      }

      const unread = result.data.notifications.filter((item) => !item.isRead).length;
      setCount(unread);
      dispatchUnreadCount(unread);
    });

    const handleUpdate = (event: Event) => {
      const nextCount = (event as CustomEvent<number>).detail;
      if (typeof nextCount === "number") {
        setCount(nextCount);
      }
    };

    window.addEventListener(UNREAD_NOTIFICATIONS_EVENT, handleUpdate as EventListener);

    const unsubscribe = subscribeToMercure([userNotificationsTopic(userId)], (event) => {
      const notification = event.notification as MessagingNotification | undefined;
      if (!notification || notification.isRead) {
        return;
      }

      setCount((current) => {
        const nextCount = current + 1;
        dispatchUnreadCount(nextCount);
        return nextCount;
      });
    });

    return () => {
      active = false;
      window.removeEventListener(
        UNREAD_NOTIFICATIONS_EVENT,
        handleUpdate as EventListener,
      );
      unsubscribe();
    };
  }, [enabled, userId]);

  return enabled ? count : 0;
}
