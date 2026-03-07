/**
 * Google Search Console Competitor Data
 * Task: TASK-32-14 - Free Alternatives Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Positioning: Essential complement for indexing and crawl management.
 * GSC is not a competitor to replace - it's a must-have tool that SEOIndicator complements.
 */

import { CompetitorData } from '../types';

export const gsc: CompetitorData = {
  id: 'gsc',
  name: 'Google Search Console',
  slug: 'google-search-console',
  website: 'https://search.google.com/search-console',
  logo: '/images/competitors/gsc.svg',
  tagline: "Google's official tool for website performance in search",
  pricing: [
    {
      name: 'Free',
      monthlyPrice: 'Free',
      annualPrice: 'Free',
      features: [
        'Search performance data',
        'Index coverage reports',
        'Sitemap submission',
        'Mobile usability reports',
        'Core Web Vitals',
        'URL inspection',
        'Manual actions',
        'Security issues',
      ],
    },
  ],
  features: [
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: false },
        { name: 'Meta Analyzer', seoindicator: true, competitor: false },
        { name: 'Page Experience', seoindicator: true, competitor: true },
        { name: 'URL Inspection', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Technical SEO',
      features: [
        { name: 'Index Coverage', seoindicator: false, competitor: true },
        { name: 'Core Web Vitals', seoindicator: true, competitor: true },
        { name: 'Mobile Usability', seoindicator: true, competitor: true },
        { name: 'Page Speed Insights', seoindicator: true, competitor: false },
        { name: 'Structured Data', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Keywords',
      features: [
        { name: 'Search Analytics', seoindicator: true, competitor: true },
        { name: 'Keyword Research', seoindicator: true, competitor: false },
        { name: 'Rank Tracking', seoindicator: true, competitor: false },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: false },
        { name: 'Search Volume', seoindicator: true, competitor: 'Partial' },
      ],
    },
    {
      name: 'Backlinks',
      features: [
        { name: 'Backlink Checker', seoindicator: true, competitor: false },
        { name: 'Link Analysis', seoindicator: true, competitor: false },
        { name: 'Referring Domains', seoindicator: true, competitor: false },
        { name: 'External Links', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'Performance Reports', seoindicator: true, competitor: true },
        { name: 'Email Alerts', seoindicator: true, competitor: true },
        { name: 'PDF Export', seoindicator: true, competitor: false },
        { name: 'API Access', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: true },
        { name: 'Requires Verification', seoindicator: false, competitor: true },
        { name: 'Google Account Required', seoindicator: false, competitor: true },
      ],
    },
  ],
  pros: [
    'Completely free with no usage limits',
    'Direct data from Google search',
    'Essential for indexing and crawl management',
    'Core Web Vitals integration',
    'Security and manual action alerts',
    'Mobile usability insights',
    'Official Google support',
    'API access available',
  ],
  cons: [
    'Limited SEO analysis capabilities',
    'No competitor data or analysis',
    'Basic keyword research only',
    'No backlink analysis',
    'Requires website ownership verification',
    'Limited historical data (16 months)',
    'No rank tracking across locations',
    'Learning curve for beginners',
  ],
  useCases: [
    {
      title: 'Index Management',
      description: 'Essential for ensuring Google can crawl and index your site properly',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Technical SEO Monitoring',
      description: 'Monitor Core Web Vitals, mobile usability, and security issues',
      audience: 'small-team',
      budget: 'free',
    },
    {
      title: 'Search Performance Tracking',
      description: 'Track impressions, clicks, and average position for your pages',
      audience: 'solo',
      budget: 'free',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'search.google.com/search-console',
};

export default gsc;
