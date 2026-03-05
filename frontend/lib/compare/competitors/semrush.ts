import { CompetitorData } from '../types';

/**
 * SEMrush Competitor Data
 * Task: TASK-32-11 - Tier 1 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Data sourced from semrush.com/pricing (March 2026)
 */
export const semrush: CompetitorData = {
  id: 'semrush',
  name: 'SEMrush',
  slug: 'semrush',
  website: 'https://semrush.com',
  logo: '/images/competitors/semrush.svg',
  tagline: 'All-in-one digital marketing platform for SEO, content, and competitive research',
  pricing: [
    {
      name: 'Pro',
      monthlyPrice: 139,
      annualPrice: 117,
      features: [
        '1 user',
        '5 websites to monitor',
        '500 tracked keywords',
        'Keyword research tools',
        'Competitor analysis',
        'Position tracking',
        'Backlinks',
        'Site Audit',
      ],
    },
    {
      name: 'Guru',
      monthlyPrice: 249,
      annualPrice: 208,
      features: [
        '1 user',
        '15 websites to monitor',
        '1,500 tracked keywords',
        'Historical data',
        'Multi-location tracking',
        'Content optimization tools',
        'Branded reports',
        'All Pro features',
      ],
    },
    {
      name: 'Business',
      monthlyPrice: 499,
      annualPrice: 417,
      features: [
        '1 user',
        '40 websites to monitor',
        '5,000 tracked keywords',
        'Share of Voice',
        'Extended limits',
        'API access',
        'Migration tools',
        'All Guru features',
      ],
    },
    {
      name: 'Enterprise',
      monthlyPrice: 'Custom',
      annualPrice: 'Custom',
      features: [
        'Unlimited users',
        'Unlimited websites',
        'Custom keyword limits',
        'Dedicated support',
        'Custom API access',
        'Advanced integrations',
        'Custom training',
        'All Business features',
      ],
    },
  ],
  features: [
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: true },
        { name: 'On-Page Analyzer', seoindicator: true, competitor: true },
        { name: 'Content Analysis', seoindicator: true, competitor: true },
        { name: 'Internal Link Analysis', seoindicator: true, competitor: false },
        { name: 'Meta Tag Analysis', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Technical SEO',
      features: [
        { name: 'Page Speed Insights', seoindicator: true, competitor: false },
        { name: 'Mobile-Friendly Test', seoindicator: true, competitor: false },
        { name: 'Structured Data Validator', seoindicator: true, competitor: false },
        { name: 'HTTPS Check', seoindicator: true, competitor: true },
        { name: 'Core Web Vitals', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Backlinks',
      features: [
        { name: 'Backlink Checker', seoindicator: true, competitor: true },
        { name: 'Link Intersect', seoindicator: true, competitor: true },
        { name: 'Toxic Link Detection', seoindicator: true, competitor: true },
        { name: 'Anchor Text Analysis', seoindicator: true, competitor: true },
        { name: 'Broken Backlinks', seoindicator: true, competitor: true },
        { name: 'Referring Domains', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Keywords',
      features: [
        { name: 'Keyword Research', seoindicator: true, competitor: true },
        { name: 'Rank Tracking', seoindicator: true, competitor: true },
        { name: 'Keyword Gap Analysis', seoindicator: true, competitor: true },
        { name: 'Search Volume Data', seoindicator: true, competitor: true },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: true },
        { name: 'SERP Analysis', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Content',
      features: [
        { name: 'Content Ideas', seoindicator: true, competitor: true },
        { name: 'Content Explorer', seoindicator: false, competitor: true },
        { name: 'AI Content Helper', seoindicator: false, competitor: true },
        { name: 'Plagiarism Checker', seoindicator: true, competitor: false },
        { name: 'Readability Score', seoindicator: true, competitor: false },
        { name: 'SEO Writing Assistant', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'White Label Reports', seoindicator: true, competitor: 'Add-on' },
        { name: 'Custom Dashboards', seoindicator: true, competitor: true },
        { name: 'API Access', seoindicator: false, competitor: 'Business+' },
        { name: 'PDF Export', seoindicator: true, competitor: true },
        { name: 'Scheduled Reports', seoindicator: true, competitor: 'Add-on' },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: 'Limited' },
        { name: 'Trial Available', seoindicator: true, competitor: true },
        { name: 'Monthly Billing', seoindicator: true, competitor: true },
        { name: 'Annual Discount', seoindicator: true, competitor: '17%' },
        { name: 'Starting Price', seoindicator: 'Free', competitor: '$117/mo' },
      ],
    },
  ],
  pros: [
    'Comprehensive all-in-one digital marketing platform',
    'Excellent keyword research and tracking capabilities',
    'Strong competitive analysis and market intelligence',
    'Content marketing and SEO writing tools included',
    'Good integration with Google Search Console',
    'Active community and extensive learning resources',
    'Local SEO features available',
    'API access on Business tier and above',
  ],
  cons: [
    'Expensive for small businesses and solopreneurs',
    'Can be overwhelming due to feature breadth',
    'Free trial requires credit card',
    'Some advanced features locked behind higher tiers',
    'API access requires Business plan ($499/mo)',
    'Learning curve for new users',
    'Occasional data discrepancies between tools',
    'Limited crawl credits on lower plans',
  ],
  useCases: [
    {
      title: 'Digital Marketing Agencies',
      description: 'Comprehensive toolkit for managing multiple client campaigns and reporting',
      audience: 'agency',
      budget: 'premium',
    },
    {
      title: 'In-House Marketing Teams',
      description: 'All-in-one platform for SEO, content, and competitive intelligence',
      audience: 'small-team',
      budget: 'mid-range',
    },
    {
      title: 'Content Marketers',
      description: 'SEO Writing Assistant and content optimization tools for content teams',
      audience: 'small-team',
      budget: 'mid-range',
    },
    {
      title: 'PPC & SEO Specialists',
      description: 'Combined paid and organic search intelligence in one platform',
      audience: 'agency',
      budget: 'premium',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'semrush.com/pricing',
};
