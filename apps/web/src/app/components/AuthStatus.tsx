"use client";

import { useEffect, useState } from "react";
import {
  API_BASE_URL,
  ApiError,
  AuthUser,
  MeResponse,
  getErrorMessage,
  getStoredToken,
  readJson,
} from "../lib/auth";

export function AuthStatus() {
  const [tokenPresent, setTokenPresent] = useState<boolean>(false);
  const [user, setUser] = useState<AuthUser | null>(null);
  const [userError, setUserError] = useState<string>("");

  useEffect(() => {
    void loadUser();
  }, []);

  async function loadUser() {
    const token = getStoredToken();
    setTokenPresent(Boolean(token));

    if (!token) {
      setUser(null);
      setUserError("");
      return;
    }

    try {
      const response = await fetch(`${API_BASE_URL}/api/me`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
        cache: "no-store",
      });

      if (!response.ok) {
        const error = await readJson<ApiError>(response);
        setUser(null);
        setUserError(getErrorMessage(error, "Unable to load current user."));
        return;
      }

      const data = await readJson<MeResponse>(response);
      setUser(data?.user ?? null);
      setUserError("");
    } catch {
      setUser(null);
      setUserError("Unable to reach the API.");
    }
  }

  return (
    <>
      <section>
        <h2>Token</h2>
        <p>Token present: {tokenPresent ? "yes" : "no"}</p>
        <button type="button" onClick={() => void loadUser()}>
          Refresh token state
        </button>
      </section>
      <section>
        <h2>Current user</h2>
        <button type="button" onClick={() => void loadUser()}>
          Refresh me
        </button> 
        <pre>{JSON.stringify(user, null, 2)}</pre>
        {userError ? <p>{userError}</p> : null}
      </section>
    </>
  );
}
