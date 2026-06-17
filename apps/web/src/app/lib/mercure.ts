import { MERCURE_PUBLIC_URL } from "./auth";

type MercureMessageEvent = {
  conversation?: unknown;
  supportConversation?: unknown;
  notification?: unknown;
  type?: string;
};

export function userConversationTopic(userId: number): string {
  return `https://comutitres.local/topics/users/${userId}/conversations`;
}

export function supportConversationTopic(): string {
  return "https://comutitres.local/topics/support/conversations";
}

export function userNotificationsTopic(userId: number): string {
  return `https://comutitres.local/topics/users/${userId}/notifications`;
}

export function subscribeToMercure(
  topics: string[],
  onMessage: (event: MercureMessageEvent) => void,
): () => void {
  if (typeof window === "undefined" || typeof EventSource === "undefined") {
    return () => {};
  }

  const url = new URL(MERCURE_PUBLIC_URL);
  topics.forEach((topic) => url.searchParams.append("topic", topic));

  const eventSource = new EventSource(url.toString());
  eventSource.onmessage = (event) => {
    try {
      onMessage(JSON.parse(event.data) as MercureMessageEvent);
    } catch {
      // Ignore malformed realtime payloads instead of crashing the UI.
    }
  };

  return () => {
    eventSource.close();
  };
}
