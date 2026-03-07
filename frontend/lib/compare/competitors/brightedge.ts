import { CompetitorData } from '../types';

/**
 * BrightEdge Competitor Data
 * Task: TASK-32-13 - Tier 3 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Enterprise SEO platform powered by AI.
 * Data sourced from brightedge.com (March 2026)
 */
export const brightedge: CompetitorData = {
  id: 'brightedge',
  name: 'BrightEdge',
  slug: 'brightedge',
  website: 'https://brightedge.com',
  logo: '/images/competitors/brightedge.svg',
  tagline: 'Enterprise SEO platform powered by AI',
  pricing: [
    {
      name: 'Enterprise',
      monthlyPrice: 'Custom',
      annualPrice: 'Custom',
      features: [
        'Custom pricing based on needs',
        'Dedicated customer success manager',
        'Full platform access',
        'API access',
        'Custom integrations',
        'Training and onboarding',
        'Priority support',
        'White label options',
      ],
      highlighted: true,
    },
  ],
  features: [
    {
      name: 'AI Features',
      features: [
        { name: 'Datacube', seoindicator: false, competitor: true },
        { name: 'Content Recommendations', seoindicator: true, competitor: true },
        { name: 'Intent Targeting', seoindicator: false, competitor: true },
        { name: 'Hyperlocal', seoindicator: false, competitor: true },
        { name: 'BrightEdge Autopilot', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Core SEO Features',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: true },
        { name: 'Rank Tracking', seoindicator: true, competitor: true },
        { name: 'Keyword Research', seoindicator: true, competitor: true },
        { name: 'Backlink Analysis', seoindicator: true, competitor: 'Limited' },
        { name: 'Competitor Analysis', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Content Optimization',
      features: [
        { name: 'Content Recommendations', seoindicator: true, competitor: true },
        { name: 'Content Performance', seoindicator: true, competitor: true },
        { name: 'StoryBuilder', seoindicator: false, competitor: true },
        { name: 'Copywriting Assistant', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Global SEO',
      features: [
        { name: 'International SEO', seoindicator: false, competitor: true },
        { name: 'Multi-language Support', seoindicator: false, competitor: true },
        { name: 'Local SEO', seoindicator: true, competitor: true },
        { name: 'Global Market Analysis', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Reporting & Integration',
      features: [
        { name: 'Custom Dashboards', seoindicator: true, competitor: true },
        { name: 'API Access', seoindicator: false, competitor: true },
        { name: 'White Label Reports', seoindicator: true, competitor: true },
        { name: 'Enterprise Integrations', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: false },
        { name: 'Trial Available', seoindicator: true, competitor: 'Demo only' },
        { name: 'Monthly Billing', seoindicator: true, competitor: false },
        { name: 'Annual Discount', seoindicator: true, competitor: 'N/A' },
        { name: 'Starting Price', seoindicator: 'Free', competitor: 'Custom' },
      ],
    },
  ],
  pros: [
    'Enterprise-grade features with AI-powered recommendations',
    'Dedicated customer success and support team',
    'Comprehensive content optimization capabilities',
    'Strong integration with enterprise marketing stacks',
    'Datacube provides deep keyword insights at scale',
    'Hyperlocal targeting for multi-location businesses',
    'Robust reporting for large organizations',
    'Proven track record with Fortune 500 companies',
  ],
  cons: [
    'Very expensive - pricing requires contact with sales',
    'Complex setup requiring dedicated team resources',
    'Overkill for small businesses or individual users',
    'Long implementation timeline',
    'Steep learning curve for full feature utilization',
    'Limited self-service options',
    'Contract-based pricing lacks flexibility',
    'Requires significant organizational commitment',
  ],
  useCases: [
    {
      title: 'Enterprise SEO Teams',
      description: 'For large organizations with dedicated SEO resources and substantial budgets',
      audience: 'enterprise',
      budget: 'premium',
    },
    {
      title: 'Global Brands',
      description: 'Multi-national companies needing advanced international SEO capabilities',
      audience: 'enterprise',
      budget: 'premium',
    },
    {
      title: 'Fortune 500 Companies',
      description: 'Large corporations requiring enterprise-grade SEO with dedicated support',
      audience: 'enterprise',
      budget: 'premium',
    },
  ],
  lastUpdated: '2026-03-07',
  source: 'https://brightedge.com/contact',
};
