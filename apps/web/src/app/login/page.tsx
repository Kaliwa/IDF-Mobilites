"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";
import { NavLinks } from "../components/NavLinks";
import {
  API_BASE_URL,
  ApiError,
  LoginResponse,
  getErrorMessage,
  readJson,
  setStoredToken,
} from "../lib/auth";

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState<string>("");
  const [password, setPassword] = useState<string>("");
  const [message, setMessage] = useState<string>("");
  const [error, setError] = useState<string>("");
  const [isLoading, setIsLoading] = useState<boolean>(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setIsLoading(true);
    setMessage("");
    setError("");

    try {
      const response = await fetch(`${API_BASE_URL}/api/login`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ email, password }),
      });

      const data = await readJson<LoginResponse | ApiError>(response);

      if (!response.ok) {
        setError(getErrorMessage(data as ApiError | null, "Login failed."));
        return;
      }

      setStoredToken((data as LoginResponse).token);
      setMessage("Login successful.");
      router.push("/");
      router.refresh();
    } catch {
      setError("Unable to reach the API.");
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <main>
      <h1>Login</h1>
      <NavLinks />

      <form onSubmit={handleSubmit}>
        <label htmlFor="email">Email</label>
        <input
          id="email"
          name="email"
          type="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
        />

        <label htmlFor="password">Password</label>
        <input
          id="password"
          name="password"
          type="password"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
        />

        <button type="submit" disabled={isLoading}>
          Login
        </button>
      </form>

      {message ? <p>{message}</p> : null}
      {error ? <p>{error}</p> : null}

      <p>
        No account? <Link href="/register">Go to register</Link>
      </p>
    </main>
  );
}
