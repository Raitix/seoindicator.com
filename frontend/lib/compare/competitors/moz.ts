import { CompetitorData } from '../types';

/**
 * Moz Competitor Data
 * Task: TASK-32-11 - Tier 1 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Data sourced from moz.com/products/pro/pricing (March 2026)
 */
export const moz: CompetitorData = {
  id: 'moz',
  name: 'Moz',
  slug: 'moz',
  website: 'https://moz.com',
  logo: '/images/competitors/moz.svg',
  tagline: 'Simplify SEO with trusted metrics and actionable insights',
  pricing: [
    {
      name: 'Starter',
      monthlyPrice: 49,
      annualPrice: 39,
      features: [
        '1 user',
        '1 tracked site',
        '50 tracked keywords',
        '20K pages crawled/month',
        'Keyword Suggestions',
        'AI Overviews',
        'Brand Authority Score',
        'MozBar Premium',
      ],
    },
    {
      name: 'Standard',
      monthlyPrice: 99,
      annualPrice: 79,
      features: [
        '1 user',
        '3 tracked sites',
        '300 tracked keywords',
        '400K pages crawled/month',
        'Unlimited scheduled reports',
        'Backlink analysis',
        'Competitive research',
        'All Starter features',
      ],
    },
    {
      name: 'Medium',
      monthlyPrice: 179,
      annualPrice: 143,
      features: [
        '2 users',
        '10 tracked sites',
        '1,500 tracked keywords',
        '2M pages crawled/month',
        'Branded reports',
        'Report templates',
        'AI Visibility (Beta)',
        'All Standard features',
      ],
      highlighted: true,
    },
    {
      name: 'Large',
      monthlyPrice: 299,
      annualPrice: 239,
      features: [
        '3 users',
        '25 tracked sites',
        '3,000 tracked keywords',
        '5M pages crawled/month',
        'Increased quotas',
        'Priority support',
        'All Medium features',
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
        { name: 'On-Page Grader', seoindicator: false, competitor: true },
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
        { name: 'Link Intersect', seoindicator: true, competitor: false },
        { name: 'Toxic Link Detection', seoindicator: true, competitor: false },
        { name: 'Anchor Text Analysis', seoindicator: true, competitor: true },
        { name: 'Broken Backlinks', seoindicator: true, competitor: true },
        { name: 'Referring Domains', seoindicator: true, competitor: true },
        { name: 'Link Explorer', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Keywords',
      features: [
        { name: 'Keyword Research', seoindicator: true, competitor: true },
        { name: 'Rank Tracking', seoindicator: true, competitor: true },
        { name: 'Keyword Gap Analysis', seoindicator: true, competitor: false },
        { name: 'Search Volume Data', seoindicator: true, competitor: true },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: true },
        { name: 'SERP Analysis', seoindicator: true, competitor: false },
        { name: 'Keyword Explorer', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Content',
      features: [
        { name: 'Content Ideas', seoindicator: true, competitor: true },
        { name: 'Content Explorer', seoindicator: false, competitor: false },
        { name: 'AI Content Helper', seoindicator: false, competitor: true },
        { name: 'Plagiarism Checker', seoindicator: true, competitor: false },
        { name: 'Readability Score', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'White Label Reports', seoindicator: true, competitor: 'Medium+' },
        { name: 'Custom Dashboards', seoindicator: true, competitor: true },
        { name: 'API Access', seoindicator: false, competitor: 'Separate' },
        { name: 'PDF Export', seoindicator: true, competitor: true },
        { name: 'Scheduled Reports', seoindicator: true, competitor: 'Standard+' },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: true },
        { name: 'Trial Available', seoindicator: true, competitor: true },
        { name: 'Monthly Billing', seoindicator: true, competitor: true },
        { name: 'Annual Discount', seoindicator: true, competitor: '20%' },
        { name: 'Starting Price', seoindicator: 'Free', competitor: '$39/mo' },
      ],
    },
  ],
  pros: [
    'Trusted Domain Authority (DA) and Page Authority (PA) metrics',
    'User-friendly interface with easy onboarding',
    'Comprehensive free tools (MozBar, Link Explorer)',
    'Good for beginners with extensive learning resources',
    'Brand Authority score for brand visibility tracking',
    'Moz Academy courses for SEO education',
    'Strong community and support documentation',
    'Affordable entry-level pricing',
  ],
  cons: [
    'Smaller backlink database than Ahrefs/SEMrush',
    'Limited keyword database compared to competitors',
    'API access sold separately',
    'No content explorer or writer tools',
    'SERP analysis less detailed than competitors',
    'No integrated social media tools',
    'Limited competitor analysis features',
    'Lower crawl rate on cheaper plans',
  ],
  useCases: [
    {
      title: 'Small Business Owners',
      description: 'Affordable entry point with essential SEO tools and easy-to-understand metrics',
      audience: 'solo',
      budget: 'budget',
    },
    {
      title: 'SEO Beginners',
      description: 'Great learning resources, simple interface, and trusted DA/PA metrics',
      audience: 'solo',
      budget: 'budget',
    },
    {
      title: 'Marketing Teams',
      description: 'Collaborative features with branded reports and team access',
      audience: 'small-team',
      budget: 'mid-range',
    },
    {
      title: 'Link Builders',
      description: 'Domain Authority metric is industry standard for link quality assessment',
      audience: 'agency',
      budget: 'mid-range',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'moz.com/products/pro/pricing',
};
