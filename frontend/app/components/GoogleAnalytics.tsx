'use client';

import Script from 'next/script';
import { GA_MEASUREMENT_ID, isGAEnabled } from '../lib/gtag';

export default function GoogleAnalytics() {
  // Only load in production
  if (process.env.NODE_ENV !== 'production' && process.env.NEXT_PUBLIC_GA_DEBUG !== 'true') {
    return null;
  }

  return (
    <>
      <Script
        src={`https://www.googletagmanager.com/gtag/js?id=${GA_MEASUREMENT_ID}`}
        strategy="afterInteractive"
      />
      <Script id="google-analytics" strategy="afterInteractive">
        {`
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', '${GA_MEASUREMENT_ID}', {
            page_path: window.location.pathname,
          });
        `}
      </Script>
    </>
  );
}
