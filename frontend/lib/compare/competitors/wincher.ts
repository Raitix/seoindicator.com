import { CompetitorData } from '../types';

/**
 * Wincher Competitor Data
 * Task: TASK-32-13 - Tier 3 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Affordable rank tracking focused tool.
 * Data sourced from wincher.com/pricing (March 2026)
 */
export const wincher: CompetitorData = {
  id: 'wincher',
  name: 'Wincher',
  slug: 'wincher',
  website: 'https://wincher.com',
  logo: '/images/competitors/wincher.svg',
  tagline: 'Affordable rank tracking for everyone',
  pricing: [
    {
      name: 'Free',
      monthlyPrice: 'Free',
      annualPrice: 'Free',
      features: [
        '10 keywords',
        'Daily updates',
        'Basic reports',
        'Email support',
        '1 website',
        'Competitor tracking',
      ],
    },
    {
      name: 'Starter',
      monthlyPrice: 29,
      annualPrice: 24,
      features: [
        '100 keywords',
        'Email reports',
        'Competitor tracking',
        'Priority support',
        '3 websites',
        'CSV export',
      ],
      highlighted: true,
    },
    {
      name: 'Professional',
      monthlyPrice: 99,
      annualPrice: 79,
      features: [
        '500 keywords',
        'Local SEO',
        'API access',
        'White label reports',
        '10 websites',
        'Team members (2)',
        'Advanced reporting',
      ],
    },
    {
      name: 'Business',
      monthlyPrice: 399,
      annualPrice: 319,
      features: [
        '5,000 keywords',
        'Multiple websites',
        'Team members (5)',
        'Advanced reporting',
        'Priority support',
        'Custom integrations',
        'API access',
      ],
    },
  ],
  features: [
    {
      name: 'Rank Tracking',
      features: [
        { name: 'Daily Rank Updates', seoindicator: true, competitor: true },
        { name: 'Local Rank Tracking', seoindicator: true, competitor: true },
        { name: 'Mobile Rankings', seoindicator: true, competitor: true },
        { name: 'Competitor Tracking', seoindicator: true, competitor: true },
        { name: 'Historical Data', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Core SEO Features',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: false },
        { name: 'Keyword Research', seoindicator: true, competitor: 'Limited' },
        { name: 'Backlink Analysis', seoindicator: true, competitor: false },
        { name: 'On-Page Analysis', seoindicator: true, competitor: false },
        { name: 'Technical SEO', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'Email Reports', seoindicator: true, competitor: true },
        { name: 'PDF Export', seoindicator: true, competitor: true },
        { name: 'White Label Reports', seoindicator: true, competitor: 'Pro+' },
        { name: 'Scheduled Reports', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Integrations',
      features: [
        { name: 'Google Search Console', seoindicator: true, competitor: true },
        { name: 'API Access', seoindicator: false, competitor: 'Pro+' },
        { name: 'CSV Export', seoindicator: true, competitor: true },
        { name: 'Looker Studio', seoindicator: false, competitor: false },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: true },
        { name: 'Trial Available', seoindicator: true, competitor: 'Free tier' },
        { name: 'Monthly Billing', seoindicator: true, competitor: true },
        { name: 'Annual Discount', seoindicator: true, competitor: '17%' },
        { name: 'Starting Price', seoindicator: 'Free', competitor: 'Free' },
      ],
    },
  ],
  pros: [
    'Very affordable pricing with genuine free tier',
    'Simple and easy to use for beginners',
    'Accurate daily rank tracking',
    'Great value for solopreneurs and small businesses',
    'Clean and intuitive interface',
    'Good local rank tracking capabilities',
    'Free tier is genuinely usable (10 keywords)',
    'Fast setup - start tracking in minutes',
  ],
  cons: [
    'Limited features compared to comprehensive SEO tools',
    'No backlink analysis or site audit',
    'Basic keyword research capabilities only',
    'Not suitable for agencies or enterprise teams',
    'Limited integrations with other tools',
    'No content optimization features',
    'Competitor tracking limited to rankings only',
    'No technical SEO analysis',
  ],
  useCases: [
    {
      title: 'Solo Entrepreneurs',
      description: 'Individuals tracking their own website rankings on a budget',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Small Business Owners',
      description: 'Small businesses needing basic rank tracking without enterprise costs',
      audience: 'solo',
      budget: 'budget',
    },
    {
      title: 'Budget-Conscious Marketers',
      description: 'Marketers who need rank tracking without the full SEO suite price tag',
      audience: 'small-team',
      budget: 'budget',
    },
  ],
  lastUpdated: '2026-03-07',
  source: 'https://wincher.com/pricing',
};
