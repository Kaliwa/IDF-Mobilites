type ApiHealth = {
  application: string;
  docs: string;
  status: string;
  timestamp: string;
};

async function getApiHealth(): Promise<{
  data: ApiHealth | null;
  ok: boolean;
}> {
  const baseUrl =
    process.env.INTERNAL_API_URL ??
    process.env.NEXT_PUBLIC_API_URL ??
    "http://localhost:8000";

  try {
    const response = await fetch(`${baseUrl}/api/health`, {
      cache: "no-store",
    });

    if (!response.ok) {
      return { data: null, ok: false };
    }

    const data = (await response.json()) as ApiHealth;

    return { data, ok: true };
  } catch {
    return { data: null, ok: false };
  }
}

export default async function Home() {
  const { data, ok } = await getApiHealth();

  if (!ok || !data) {
    return <pre>{`API unavailable`}</pre>;
  }

  return (
    <pre>{JSON.stringify(data, null, 2)}</pre>
  );
}
