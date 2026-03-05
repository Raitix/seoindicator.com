import { CompetitorData } from '../types';

/**
 * Ubersuggest Competitor Data
 * Task: TASK-32-11 - Tier 1 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Data sourced from neilpatel.com/ubersuggest (March 2026)
 */
export const ubersuggest: CompetitorData = {
  id: 'ubersuggest',
  name: 'Ubersuggest',
  slug: 'ubersuggest',
  website: 'https://neilpatel.com/ubersuggest/',
  logo: '/images/competitors/ubersuggest.svg',
  tagline: 'Free keyword research tool with AI-powered insights',
  pricing: [
    {
      name: 'Free',
      monthlyPrice: 'Free',
      annualPrice: 'Free',
      features: [
        '3 daily searches',
        'Limited keyword suggestions',
        'Basic competitor analysis',
        'Site audit (limited)',
        'Backlink overview',
        '3 tracked keywords',
      ],
    },
    {
      name: 'Individual',
      monthlyPrice: 29,
      annualPrice: 12,
      features: [
        '1 user',
        '1 project',
        '100 tracked keywords',
        '25 tracked keywords',
        'Keyword suggestions',
        'Competitor analysis',
        'Site audit',
        'Backlink data',
      ],
    },
    {
      name: 'Business',
      monthlyPrice: 49,
      annualPrice: 20,
      features: [
        '1 user',
        '3 projects',
        '150 tracked keywords',
        '500 tracked keywords',
        'All Individual features',
        'Content ideas',
        'AI Writer',
        'Priority support',
      ],
    },
    {
      name: 'Enterprise',
      monthlyPrice: 99,
      annualPrice: 40,
      features: [
        '1 user',
        '7 projects',
        '300 tracked keywords',
        '2,000 tracked keywords',
        'All Business features',
        'API access',
        'White label reports',
        'Dedicated support',
      ],
    },
  ],
  features: [
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: true },
        { name: 'On-Page Analyzer', seoindicator: true, competitor: false },
        { name: 'Content Analysis', seoindicator: true, competitor: 'Basic' },
        { name: 'Internal Link Analysis', seoindicator: true, competitor: false },
        { name: 'Meta Tag Analysis', seoindicator: true, competitor: false },
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
        { name: 'Anchor Text Analysis', seoindicator: true, competitor: false },
        { name: 'Broken Backlinks', seoindicator: true, competitor: false },
        { name: 'Referring Domains', seoindicator: true, competitor: true },
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
        { name: 'SERP Analysis', seoindicator: true, competitor: 'Basic' },
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
        { name: 'AI Writer', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'White Label Reports', seoindicator: true, competitor: 'Enterprise' },
        { name: 'Custom Dashboards', seoindicator: true, competitor: false },
        { name: 'API Access', seoindicator: false, competitor: 'Enterprise' },
        { name: 'PDF Export', seoindicator: true, competitor: true },
        { name: 'Scheduled Reports', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: true },
        { name: 'Trial Available', seoindicator: true, competitor: false },
        { name: 'Monthly Billing', seoindicator: true, competitor: true },
        { name: 'Annual Discount', seoindicator: true, competitor: '58%' },
        { name: 'Starting Price', seoindicator: 'Free', competitor: 'Free' },
      ],
    },
  ],
  pros: [
    'Generous free tier with useful features',
    'Very affordable paid plans compared to competitors',
    'Lifetime deal options available (pay once, use forever)',
    'Simple, beginner-friendly interface',
    'AI Writer included in paid plans',
    'Good for basic keyword research',
    'Quick site audit functionality',
    'No credit card required for free tier',
  ],
  cons: [
    'Smaller keyword and backlink database',
    'Less accurate data than premium competitors',
    'Limited advanced features for power users',
    'No API access on lower tiers',
    'Basic competitive analysis',
    'Limited reporting and white-label options',
    'No content explorer or deep SERP analysis',
    'Smaller community and fewer learning resources',
  ],
  useCases: [
    {
      title: 'Budget-Conscious Marketers',
      description: 'Affordable SEO tools with lifetime deal options for cost-effective optimization',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'SEO Beginners',
      description: 'Simple interface with free tier to learn SEO basics without commitment',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Small Business Owners',
      description: 'Essential keyword research and site audit at a fraction of competitor prices',
      audience: 'solo',
      budget: 'budget',
    },
    {
      title: 'Content Creators',
      description: 'AI Writer and content ideas help create SEO-optimized content',
      audience: 'solo',
      budget: 'budget',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'neilpatel.com/ubersuggest',
};
