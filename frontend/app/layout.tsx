import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import GoogleAnalytics from "./components/GoogleAnalytics";
import { Providers } from "./providers";
import { SkipLink, SkipToMain } from "@/components/layout/SkipLink";
import { CookieConsent, Footer } from "@/components/layout";
import GuestPreview from "@/components/auth/GuestPreview";
import { ConsentProvider } from "@/lib/contexts/ConsentContext";
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
  title: "SEOIndicator.com : AI Powered SEO Analysis",
  description:
    "Free AI-powered SEO analysis tool. Get instant scores and suggestions for 17 SEO indicators including title, meta, mobile-friendly, and more.",
  keywords: [
    "SEO",
    "SEO analysis",
    "SEO tool",
    "AI SEO",
    "website analysis",
    "SEO indicators",
  ],
  authors: [{ name: "SEOIndicator.com" }],
  openGraph: {
    title: "SEOIndicator.com : AI Powered SEO Analysis",
    description:
      "Free AI-powered SEO analysis tool. Get instant scores and suggestions for 17 SEO indicators.",
    url: "https://seoindicator.com",
    siteName: "SEOIndicator.com",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "SEOIndicator.com : AI Powered SEO Analysis",
    description:
      "Free AI-powered SEO analysis tool. Get instant scores and suggestions for 17 SEO indicators.",
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
          <ConsentProvider>
            <div className="flex flex-col min-h-screen">
              {/* The strip needs its own dark surface. The page body is
                  white by default, so a translucent emerald tint over it
                  rendered as a pale box with barely readable text. */}
              <div className="border-b border-emerald-500/20 bg-slate-950">
                <div className="container mx-auto px-4 py-3">
                  <GuestPreview feature="your saved data" />
                </div>
              </div>
              <div className="flex-1">
                <SkipToMain>{children}</SkipToMain>
              </div>
              <Footer />
            </div>
            <CookieConsent showCustomize />
          </ConsentProvider>
        </Providers>
      </body>
    </html>
  );
}
