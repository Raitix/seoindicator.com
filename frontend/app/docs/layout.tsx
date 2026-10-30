import { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'API Documentation | SEOIndicator',
  description: 'Comprehensive API documentation for SEOIndicator. Learn how to integrate SEO analysis and monitoring into your applications.',
};

export default function DocsLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return <>{children}</>;
}
