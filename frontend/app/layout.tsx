import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import GoogleAnalytics from "./components/GoogleAnalytics";
import { Providers } from "./providers";
import { SkipLink, SkipToMain } from "@/components/layout/SkipLink";
import "./globals.css";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "SEOIndicator.com - AI Powered SEO Analysis",
  description: "Free AI-powered SEO analysis tool. Get instant scores and suggestions for 17 SEO indicators including title, meta, mobile-friendly, and more.",
  keywords: ["SEO", "SEO analysis", "SEO tool", "AI SEO", "website analysis", "SEO indicators"],
  authors: [{ name: "SEOIndicator.com" }],
  openGraph: {
    title: "SEOIndicator.com - AI Powered SEO Analysis",
    description: "Free AI-powered SEO analysis tool. Get instant scores and suggestions for 17 SEO indicators.",
    url: "https://seoindicator.com",
    siteName: "SEOIndicator.com",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "SEOIndicator.com - AI Powered SEO Analysis",
    description: "Free AI-powered SEO analysis tool. Get instant scores and suggestions for 17 SEO indicators.",
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <head>
        <link rel="icon" href="/favicon.ico" />
      </head>
      <body
        className={`${geistSans.variable} ${geistMono.variable} antialiased`}
      >
        <SkipLink />
        <GoogleAnalytics />
        <Providers>
          <SkipToMain>{children}</SkipToMain>
        </Providers>
      </body>
    </html>
  );
}
