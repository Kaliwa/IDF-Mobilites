export type AuthUser = {
  createdAt: string | null;
  email: string;
  id: number;
  roles: string[];
};

export type ApiError = {
  code?: number;
  errors?: Array<{ field: string; message: string }>;
  message?: string;
};

export type RegisterResponse = {
  message: string;
  token: string;
  user: AuthUser;
};

export type LoginResponse = {
  token: string;
};

export type MeResponse = {
  user: AuthUser;
};

export function hasSupportAccess(roles: string[]): boolean {
  return roles.includes("ROLE_SUPPORT") || roles.includes("ROLE_ADMIN");
}

export function isSupportUser(user: AuthUser | null): boolean {
  return !!user && hasSupportAccess(user.roles);
}

export const TOKEN_STORAGE_KEY = "idf-mobilites.jwt";
export const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000";
export const MERCURE_PUBLIC_URL =
  process.env.NEXT_PUBLIC_MERCURE_URL ?? "http://localhost:3001/.well-known/mercure";

export async function readJson<T>(response: Response): Promise<T | null> {
  const text = await response.text();

  if (!text) {
    return null;
  }

  return JSON.parse(text) as T;
}

export function getStoredToken(): string {
  if (typeof window === "undefined") {
    return "";
  }

  return window.localStorage.getItem(TOKEN_STORAGE_KEY) ?? "";
}

export function setStoredToken(token: string) {
  if (typeof window === "undefined") {
    return;
  }

  if (token) {
    window.localStorage.setItem(TOKEN_STORAGE_KEY, token);
    return;
  }

  window.localStorage.removeItem(TOKEN_STORAGE_KEY);
}

export function getErrorMessage(
  error: ApiError | null,
  fallback: string,
): string {
  if (!error) {
    return fallback;
  }

  if (error.errors && error.errors.length > 0) {
    return error.errors
      .map((entry) => `${entry.field}: ${entry.message}`)
      .join(", ");
  }

  return error.message ?? fallback;
}
