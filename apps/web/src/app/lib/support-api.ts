import { API_BASE_URL, ApiError, getStoredToken, readJson } from "./auth";

export type SupportDeskMessage = {
  id: number;
  author: string;
  content: string;
  sentAt: string | null;
};

export type SupportDeskConversation = {
  id: number;
  subject: string;
  status: string;
  updatedAt: string | null;
  customer: {
    id: number | null;
    email: string | null;
  };
  messages: SupportDeskMessage[];
};

export type SupportDeskOverview = {
  conversations: SupportDeskConversation[];
};

async function supportRequest<T>(
  path: string,
  init?: RequestInit,
): Promise<{ data: T | null; error: string }> {
  const token = getStoredToken();

  if (!token) {
    return { data: null, error: "Connexion support requise." };
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
    return { data: null, error: "Le service support est momentanément indisponible." };
  }
}

export async function fetchSupportConversations() {
  return supportRequest<SupportDeskOverview>("/api/messaging/support/conversations");
}

export async function sendSupportReply(
  conversationId: number,
  content: string,
) {
  return supportRequest<{ conversation: SupportDeskConversation }>(
    `/api/messaging/support/conversations/${conversationId}/messages`,
    {
      method: "POST",
      body: JSON.stringify({ content }),
    },
  );
}

export async function updateSupportConversationStatus(
  conversationId: number,
  status: "open" | "waiting-user" | "resolved",
) {
  return supportRequest<{ conversation: SupportDeskConversation }>(
    `/api/messaging/support/conversations/${conversationId}/status`,
    {
      method: "POST",
      body: JSON.stringify({ status }),
    },
  );
}
