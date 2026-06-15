"use client";

import { useState } from "react";
import { API_BASE_URL, getStoredToken, setStoredToken } from "../lib/auth";

export function LogoutButton() {
  const [message, setMessage] = useState<string>("");
  const [error, setError] = useState<string>("");
  const [isLoading, setIsLoading] = useState<boolean>(false);

  async function handleLogout() {
    setIsLoading(true);
    setMessage("");
    setError("");

    try {
      const token = getStoredToken();

      if (token) {
        await fetch(`${API_BASE_URL}/api/logout`, {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
      }

      setStoredToken("");
      setMessage("Logout successful.");
    } catch {
      setError("Unable to reach the API.");
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <section>
      <button type="button" onClick={() => void handleLogout()} disabled={isLoading}>
        Logout
      </button>
      {message ? <p>{message}</p> : null}
      {error ? <p>{error}</p> : null}
    </section>
  );
}
