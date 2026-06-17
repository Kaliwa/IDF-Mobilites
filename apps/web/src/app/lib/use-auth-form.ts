"use client";

import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";
import {
  API_BASE_URL,
  ApiError,
  getErrorMessage,
  hasSupportAccess,
  MeResponse,
  readJson,
  setStoredToken,
} from "./auth";

type TokenResponse = { token: string };

export function useAuthForm(endpoint: "login" | "register", fallbackError: string) {
  const router = useRouter();
  const [email, setEmail] = useState<string>("");
  const [password, setPassword] = useState<string>("");
  const [error, setError] = useState<string>("");
  const [isLoading, setIsLoading] = useState<boolean>(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setIsLoading(true);
    setError("");

    try {
      const response = await fetch(`${API_BASE_URL}/api/${endpoint}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
      });

      const data = await readJson<TokenResponse | ApiError>(response);

      if (!response.ok) {
        setError(getErrorMessage(data as ApiError | null, fallbackError));
        return;
      }

      const token = (data as TokenResponse).token;
      setStoredToken(token);

      if (endpoint === "login") {
        const meResponse = await fetch(`${API_BASE_URL}/api/me`, {
          headers: { Authorization: `Bearer ${token}` },
          cache: "no-store",
        });

        const meData = await readJson<MeResponse | ApiError>(meResponse);

        if (!meResponse.ok) {
          setStoredToken("");
          setError(getErrorMessage(meData as ApiError | null, fallbackError));
          return;
        }

        const roles = (meData as MeResponse).user.roles;
        if (hasSupportAccess(roles)) {
          window.location.href = `${process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000"}/admin`;
        } else {
          router.push("/");
        }
      } else {
        router.push("/");
      }

      router.refresh();
    } catch {
      setError("Le service est momentanément injoignable.");
    } finally {
      setIsLoading(false);
    }
  }

  return { email, setEmail, password, setPassword, error, isLoading, handleSubmit };
}
