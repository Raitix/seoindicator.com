import { CompetitorData } from '../types';

/**
 * Raven Tools Competitor Data
 * Task: TASK-32-13 - Tier 3 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Agency-focused SEO and marketing reporting platform.
 * Data sourced from raventools.com/pricing (March 2026)
 */
export const raven: CompetitorData = {
  id: 'raven',
  name: 'Raven Tools',
  slug: 'raven',
  website: 'https://raventools.com',
  logo: '/images/competitors/raven.svg',
  tagline: 'SEO and marketing reporting platform for agencies',
  pricing: [
    {
      name: 'Lite',
      monthlyPrice: 49,
      annualPrice: 39,
      features: [
        '1 user',
        '5 campaigns',
        '50 keywords tracked',
        'Basic reporting',
        'Site Auditor',
        'Google Analytics integration',
      ],
    },
    {
      name: 'Pro',
      monthlyPrice: 99,
      annualPrice: 79,
      features: [
        '1 user',
        '25 campaigns',
        '500 keywords tracked',
        'Advanced reporting',
        'API access',
        'White label reports',
        'Link Manager',
        'Social media tools',
      ],
      highlighted: true,
    },
    {
      name: 'Agency',
      monthlyPrice: 279,
      annualPrice: 229,
      features: [
        'Unlimited users',
        'Unlimited campaigns',
        '5,000 keywords tracked',
        'White label reports',
        'Team collaboration',
        'Priority support',
        'Custom branding',
        'Advanced API access',
      ],
    },
  ],
  features: [
    {
      name: 'Core SEO Features',
      features: [
        { name: 'Site Auditor', seoindicator: true, competitor: true },
        { name: 'Rank Tracking', seoindicator: true, competitor: true },
        { name: 'Backlink Analysis', seoindicator: true, competitor: 'Limited' },
        { name: 'Keyword Research', seoindicator: true, competitor: 'Limited' },
        { name: 'Competitor Analysis', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'Custom Reports', seoindicator: true, competitor: true },
        { name: 'White Label Reports', seoindicator: true, competitor: true },
        { name: 'PDF Export', seoindicator: true, competitor: true },
        { name: 'Scheduled Reports', seoindicator: true, competitor: true },
        { name: 'Client Portal', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Link Management',
      features: [
        { name: 'Link Manager', seoindicator: false, competitor: true },
        { name: 'Backlink Monitor', seoindicator: true, competitor: true },
        { name: 'Link Opportunities', seoindicator: true, competitor: 'Limited' },
        { name: 'Anchor Text Analysis', seoindicator: true, competitor: 'Limited' },
      ],
    },
    {
      name: 'Integrations',
      features: [
        { name: 'Google Analytics', seoindicator: true, competitor: true },
        { name: 'Google Search Console', seoindicator: true, competitor: true },
        { name: 'Social Media', seoindicator: false, competitor: true },
        { name: 'Moz Data', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: false },
        { name: 'Trial Available', seoindicator: true, competitor: '14 days' },
        { name: 'Monthly Billing', seoindicator: true, competitor: true },
        { name: 'Annual Discount', seoindicator: true, competitor: '20%' },
        { name: 'Starting Price', seoindicator: 'Free', competitor: '$49/mo' },
      ],
    },
  ],
  pros: [
    'Great for agencies managing multiple client campaigns',
    'Affordable pricing with agency-focused features',
    'Comprehensive reporting and white label options',
    'Integrates with Google Analytics and Search Console',
    'Includes social media management tools',
    'Link Manager for tracking outreach campaigns',
    'Easy client reporting with automated schedules',
    'Good collaboration features for teams',
  ],
  cons: [
    'Limited backlink database compared to Ahrefs',
    'No proprietary keyword database',
    'Less advanced features for technical SEO',
    'Rank tracking not as accurate as dedicated tools',
    'Limited competitor analysis capabilities',
    'No content optimization features',
    'Basic keyword research compared to SEMrush',
  ],
  useCases: [
    {
      title: 'Marketing Agencies',
      description: 'Perfect for agencies managing multiple client campaigns with white label reporting needs',
      audience: 'agency',
      budget: 'mid-range',
    },
    {
      title: 'Small Marketing Teams',
      description: 'Good for small teams that need basic SEO tools without enterprise costs',
      audience: 'small-team',
      budget: 'budget',
    },
    {
      title: 'Freelance SEO Consultants',
      description: 'Affordable option for freelancers managing several client accounts',
      audience: 'solo',
      budget: 'budget',
    },
  ],
  lastUpdated: '2026-03-07',
  source: 'https://raventools.com/pricing',
};
