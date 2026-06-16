type NoticeProps = {
  tone: "error" | "success";
  children: string;
};

export function Notice({ tone, children }: NoticeProps) {
  const isError = tone === "error";
  const styles = isError
    ? "bg-danger/10 text-danger border-danger/25"
    : "bg-success/10 text-success border-success/25";

  return (
    <p
      role={isError ? "alert" : "status"}
      className={`rounded-2xl border px-4 py-3 text-sm font-medium ${styles}`}
    >
      {children}
    </p>
  );
}
