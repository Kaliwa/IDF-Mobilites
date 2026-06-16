"use client";

import { useState } from "react";
import { field } from "../../lib/ui";

type FieldProps = {
  id: string;
  label: string;
  type?: "email" | "password" | "text";
  value: string;
  onChange: (value: string) => void;
  autoComplete?: string;
  placeholder?: string;
  required?: boolean;
};

export function Field({
  id,
  label,
  type = "text",
  value,
  onChange,
  autoComplete,
  placeholder,
  required = true,
}: FieldProps) {
  const [show, setShow] = useState(false);
  const isPassword = type === "password";
  const inputType = isPassword && show ? "text" : type;

  return (
    <div className="space-y-1.5">
      <label htmlFor={id} className="block text-sm font-medium text-anthracite/80">
        {label}
      </label>
      <div className="relative">
        <input
          id={id}
          name={id}
          type={inputType}
          value={value}
          required={required}
          autoComplete={autoComplete}
          placeholder={placeholder}
          onChange={(event) => onChange(event.target.value)}
          className={`${field} ${isPassword ? "pr-24" : ""}`}
        />
        {isPassword && (
          <button
            type="button"
            onClick={() => setShow((current) => !current)}
            aria-label={show ? "Masquer le mot de passe" : "Afficher le mot de passe"}
            aria-pressed={show}
            className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold uppercase tracking-wide text-idf-interaction hover:text-idf-focus"
          >
            {show ? "Masquer" : "Afficher"}
          </button>
        )}
      </div>
    </div>
  );
}
