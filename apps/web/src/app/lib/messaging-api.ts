import {
  API_BASE_URL,
  ApiError,
  getStoredToken,
  readJson,
} from "./auth";

export type MessagingLine = {
  id: number;
  code: string;
  name: string;
};

export type MessagingNotification = {
  id: number;
  title: string;
  body: string;
  category: string;
  priority: string;
  isRead: boolean;
  createdAt: string | null;
  actionLabel: string | null;
  line: MessagingLine | null;
};

export type MessagingSubscription = {
  id: number;
  enabled: boolean;
  channels: string[];
  line: MessagingLine;
};

export type MessagingMessage = {
  id: number;
  author: string;
  content: string;
  sentAt: string | null;
};

export type MessagingConversation = {
  id: number;
  subject: string;
  status: string;
  updatedAt: string | null;
  messages: MessagingMessage[];
};

export type MessagingOverview = {
  notifications: MessagingNotification[];
  subscriptions: MessagingSubscription[];
  conversations: MessagingConversation[];
};

async function apiRequest<T>(
  path: string,
  init?: RequestInit,
): Promise<{ data: T | null; error: string }> {
  const token = getStoredToken();

  if (!token) {
    return { data: null, error: "Vous devez être connecté pour accéder à la messagerie." };
  }

  try {
    const response = await fetch(`${API_BASE_URL}${path}`, {
      ...init,
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
        ...(init?.headers ?? {}),
      },
      cache: "no-store",
    });

    const data = await readJson<T | ApiError>(response);

    if (!response.ok) {
      return {
        data: null,
        error: (data as ApiError | null)?.message ?? "Une erreur est survenue.",
      };
    }

    return { data: data as T | null, error: "" };
  } catch {
    return { data: null, error: "Unable to reach the API." };
  }
}

export async function fetchMessagingOverview() {
  return apiRequest<MessagingOverview>("/api/messaging/overview");
}

export async function markNotificationAsRead(notificationId: number) {
  return apiRequest<{ notification: MessagingNotification }>(
    `/api/messaging/notifications/${notificationId}/read`,
    { method: "POST" },
  );
}

export async function markAllNotificationsAsRead() {
  return apiRequest<{ success: boolean }>(
    "/api/messaging/notifications/read-all",
    { method: "POST" },
  );
}

export async function updateSubscription(
  subscriptionId: number,
  payload: { enabled?: boolean; channels?: string[] },
) {
  return apiRequest<{ subscription: MessagingSubscription }>(
    `/api/messaging/subscriptions/${subscriptionId}`,
    {
      method: "POST",
      body: JSON.stringify(payload),
    },
  );
}

export async function sendConversationMessage(
  conversationId: number,
  content: string,
) {
  return apiRequest<{ conversation: MessagingConversation }>(
    `/api/messaging/conversations/${conversationId}/messages`,
    {
      method: "POST",
      body: JSON.stringify({ content }),
    },
  );
}

export async function deleteSubscription(subscriptionId: number) {
  return apiRequest<null>(`/api/messaging/subscriptions/${subscriptionId}`, {
    method: "DELETE",
  });
}

export async function fetchLineCatalog() {
  return apiRequest<{ lines: MessagingLine[] }>("/api/messaging/lines");
}

export async function createSubscription(lineId: number, channels: string[] = ["inApp"]) {
  return apiRequest<{ subscription: MessagingSubscription }>("/api/messaging/subscriptions", {
    method: "POST",
    body: JSON.stringify({ lineId, channels }),
  });
}

export function formatMessagingDate(date: string | null): string {
  if (!date) {
    return "-";
  }

  return new Intl.DateTimeFormat("fr-FR", {
    dateStyle: "short",
    timeStyle: "short",
  }).format(new Date(date));
}
