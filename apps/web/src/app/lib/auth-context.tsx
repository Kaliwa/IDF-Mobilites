"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useState,
  type ReactNode,
} from "react";
import { usePathname, useRouter } from "next/navigation";
import {
  API_BASE_URL,
  AuthUser,
  MeResponse,
  getStoredToken,
  hasSupportAccess,
  readJson,
  setStoredToken,
} from "./auth";

type AuthState = {
  user: AuthUser | null;
  loading: boolean;
  logout: () => Promise<void>;
};

const AuthContext = createContext<AuthState>({
  user: null,
  loading: true,
  logout: async () => {},
});

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    let active = true;

    async function load() {
      const token = getStoredToken();
      if (!token) {
        if (active) setLoading(false);
        return;
      }

      try {
        const response = await fetch(`${API_BASE_URL}/api/me`, {
          headers: { Authorization: `Bearer ${token}` },
          cache: "no-store",
        });
        if (!response.ok) {
          if (active) setUser(null);
          return;
        }
        const data = await readJson<MeResponse>(response);
        if (active) setUser(data?.user ?? null);
      } catch {
        if (active) setUser(null);
      } finally {
        if (active) setLoading(false);
      }
    }

    void load();
    return () => {
      active = false;
    };
  }, []);

  const logout = useCallback(async () => {
    const token = getStoredToken();
    try {
      if (token) {
        await fetch(`${API_BASE_URL}/api/logout`, {
          method: "POST",
          headers: { Authorization: `Bearer ${token}` },
        });
      }
    } catch {
      // Le token est invalidé localement même si l'appel réseau échoue.
    }
    setStoredToken("");
    setUser(null);
  }, []);

  return (
    <AuthContext.Provider value={{ user, loading, logout }}>
      <SupportGuard user={user} loading={loading} />
      {children}
    </AuthContext.Provider>
  );
}

function SupportGuard({ user, loading }: { user: AuthUser | null; loading: boolean }) {
  const router = useRouter();
  const pathname = usePathname();

  useEffect(() => {
    if (loading) return;
    if (!user) return;
    if (!hasSupportAccess(user.roles)) return;
    if (pathname.startsWith("/support")) return;
    window.location.href = `${process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000"}/admin`;
  }, [loading, user, pathname, router]);

  return null;
}

export const useAuth = () => useContext(AuthContext);
