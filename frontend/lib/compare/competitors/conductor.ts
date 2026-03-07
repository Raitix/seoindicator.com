import { CompetitorData } from '../types';

/**
 * Conductor Competitor Data
 * Task: TASK-32-13 - Tier 3 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Enterprise SEO and content marketing platform.
 * Data sourced from conductor.com (March 2026)
 */
export const conductor: CompetitorData = {
  id: 'conductor',
  name: 'Conductor',
  slug: 'conductor',
  website: 'https://conductor.com',
  logo: '/images/competitors/conductor.svg',
  tagline: 'Enterprise SEO and content marketing platform',
  pricing: [
    {
      name: 'Enterprise',
      monthlyPrice: 'Custom',
      annualPrice: 'Custom',
      features: [
        'Custom pricing',
        'Full platform access',
        'Dedicated support',
        'Custom training',
        'API access',
        'Workspaces',
        'Team collaboration',
        'Content guidance',
      ],
      highlighted: true,
    },
  ],
  features: [
    {
      name: 'Content Marketing',
      features: [
        { name: 'Content Guidance', seoindicator: false, competitor: true },
        { name: 'Impact Analysis', seoindicator: false, competitor: true },
        { name: 'Content Performance', seoindicator: true, competitor: true },
        { name: 'Topic Research', seoindicator: true, competitor: true },
        { name: 'Content Optimization', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Core SEO Features',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: true },
        { name: 'Rank Tracking', seoindicator: true, competitor: true },
        { name: 'Keyword Research', seoindicator: true, competitor: true },
        { name: 'Competitor Analysis', seoindicator: true, competitor: true },
        { name: 'SEO Recommendations', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Workspaces & Collaboration',
      features: [
        { name: 'Workspaces', seoindicator: false, competitor: true },
        { name: 'Team Collaboration', seoindicator: true, competitor: true },
        { name: 'Task Management', seoindicator: false, competitor: true },
        { name: 'Workflow Automation', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Global SEO',
      features: [
        { name: 'International SEO', seoindicator: false, competitor: true },
        { name: 'Multi-language Support', seoindicator: false, competitor: true },
        { name: 'Global Keyword Tracking', seoindicator: false, competitor: true },
        { name: 'Regional Insights', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Reporting & Integration',
      features: [
        { name: 'Custom Dashboards', seoindicator: true, competitor: true },
        { name: 'API Access', seoindicator: false, competitor: true },
        { name: 'White Label Reports', seoindicator: true, competitor: true },
        { name: 'Training Resources', seoindicator: false, competitor: true },
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
    'Excellent content marketing and optimization capabilities',
    'Strong focus on enterprise-level content strategy',
    'Good workflow and collaboration features for teams',
    'Comprehensive training and customer success resources',
    'Workspaces for organizing large SEO initiatives',
    'Impact Analysis shows content ROI',
    'Dedicated customer success management',
    'Strong integration with marketing technology stacks',
  ],
  cons: [
    'Expensive enterprise-only pricing',
    'Requires significant investment in implementation',
    'Not suitable for small businesses or individual users',
    'Limited backlink analysis capabilities',
    'No self-service or trial options',
    'Long sales cycle and contract requirements',
    'Complex platform requiring dedicated training',
    'No free tier or affordable entry point',
  ],
  useCases: [
    {
      title: 'Content Marketing Teams',
      description: 'Enterprise teams focused on content-driven SEO strategies',
      audience: 'enterprise',
      budget: 'premium',
    },
    {
      title: 'Large SEO Departments',
      description: 'Organizations with dedicated SEO teams managing multiple properties',
      audience: 'enterprise',
      budget: 'premium',
    },
    {
      title: 'Enterprise Marketing',
      description: 'Large marketing teams needing integrated content and SEO workflow',
      audience: 'enterprise',
      budget: 'premium',
    },
  ],
  lastUpdated: '2026-03-07',
  source: 'https://conductor.com/contact',
};
