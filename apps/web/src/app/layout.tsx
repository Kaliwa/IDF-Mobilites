import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import Script from "next/script";
import "./globals.css";
import "./idfm-signs.css";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "IDF Mobilites",
  description: "Frontend Next.js connecte a une API Symfony via Docker",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="fr"
      className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}
    >
      <body className="min-h-full flex flex-col">{children}</body>
      {process.env.NEXT_PUBLIC_UMAMI_WEBSITE_ID ? (
        <Script
          defer
          src={`${process.env.NEXT_PUBLIC_UMAMI_URL ?? "http://localhost:3002"}/script.js`}
          data-website-id={process.env.NEXT_PUBLIC_UMAMI_WEBSITE_ID}
        />
      ) : null}
    </html>
  );
}
