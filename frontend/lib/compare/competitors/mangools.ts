import { CompetitorData } from '../types';

/**
 * Mangools Competitor Data
 * Task: TASK-32-12 - Tier 2 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Data sourced from mangools.com/pricing (March 2026)
 */
export const mangools: CompetitorData = {
  id: 'mangools',
  name: 'Mangools',
  slug: 'mangools',
  website: 'https://mangools.com',
  logo: '/images/competitors/mangools.svg',
  tagline: 'SEO tools for beginners with a focus on simplicity and ease of use',
  pricing: [
    {
      name: 'Entry',
      monthlyPrice: 29,
      annualPrice: 19.90,
      features: [
        '25 keyword lookups per 24h',
        '100 SERP lookups per 24h',
        '25 backlink rows per month',
        '5 SERPWatcher campaigns',
        '100 site limit per SiteProfiler scan',
        '10 daily keyword checks',
        'Basic email support',
      ],
      highlighted: true,
    },
    {
      name: 'Premium',
      monthlyPrice: 49,
      annualPrice: 29.90,
      features: [
        '100 keyword lookups per 24h',
        '300 SERP lookups per 24h',
        '500 backlink rows per month',
        '15 SERPWatcher campaigns',
        '500 site limit per SiteProfiler scan',
        '50 daily keyword checks',
        'Priority email support',
      ],
    },
    {
      name: 'Agency',
      monthlyPrice: 129,
      annualPrice: 79.90,
      features: [
        '500 keyword lookups per 24h',
        '1,000 SERP lookups per 24h',
        '2,500 backlink rows per month',
        '50 SERPWatcher campaigns',
        'Unlimited SiteProfiler scans',
        '250 daily keyword checks',
        'Priority support',
        'Team collaboration',
      ],
    },
  ],
  features: [
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: false },
        { name: 'On-Page Analyzer', seoindicator: true, competitor: false },
        { name: 'Content Analysis', seoindicator: true, competitor: 'Partial' },
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
        { name: 'HTTPS Check', seoindicator: true, competitor: false },
        { name: 'Core Web Vitals', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Backlinks',
      features: [
        { name: 'Backlink Checker', seoindicator: true, competitor: true },
        { name: 'LinkMiner', seoindicator: true, competitor: true, tooltip: 'Dedicated backlink analysis tool' },
        { name: 'Toxic Link Detection', seoindicator: true, competitor: false },
        { name: 'Anchor Text Analysis', seoindicator: true, competitor: 'Partial' },
        { name: 'Broken Backlinks', seoindicator: true, competitor: true },
        { name: 'Referring Domains', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Keywords',
      features: [
        { name: 'Keyword Research', seoindicator: true, competitor: true },
        { name: 'KWFinder', seoindicator: true, competitor: true, tooltip: 'Long-tail keyword finder with difficulty score' },
        { name: 'Rank Tracking', seoindicator: true, competitor: true },
        { name: 'SERPWatcher', seoindicator: true, competitor: true, tooltip: 'Rank tracking with performance index' },
        { name: 'Keyword Gap Analysis', seoindicator: true, competitor: false },
        { name: 'Search Volume Data', seoindicator: true, competitor: true },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: true },
        { name: 'SERP Analysis', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Content',
      features: [
        { name: 'Content Ideas', seoindicator: true, competitor: 'Partial' },
        { name: 'Content Explorer', seoindicator: false, competitor: false },
        { name: 'AI Content Helper', seoindicator: false, competitor: false },
        { name: 'Plagiarism Checker', seoindicator: true, competitor: false },
        { name: 'Readability Score', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Competitor Analysis',
      features: [
        { name: 'SERPChecker', seoindicator: true, competitor: true, tooltip: 'SERP analysis with rich metrics' },
        { name: 'SiteProfiler', seoindicator: true, competitor: true, tooltip: 'Quick website authority analysis' },
        { name: 'Domain Comparison', seoindicator: true, competitor: 'Partial' },
        { name: 'Traffic Estimation', seoindicator: true, competitor: 'Partial' },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'White Label Reports', seoindicator: true, competitor: 'Agency' },
        { name: 'Custom Dashboards', seoindicator: true, competitor: 'Partial' },
        { name: 'API Access', seoindicator: false, competitor: false },
        { name: 'PDF Export', seoindicator: true, competitor: true },
        { name: 'Scheduled Reports', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: 'Limited' },
        { name: 'Trial Available', seoindicator: true, competitor: true, tooltip: '10-day free trial available' },
        { name: 'Monthly Billing', seoindicator: true, competitor: true },
        { name: 'Annual Discount', seoindicator: true, competitor: '38%' },
        { name: 'Starting Price', seoindicator: 'Free', competitor: '$29/mo' },
      ],
    },
  ],
  pros: [
    'User-friendly interface designed for beginners and non-experts',
    'Affordable pricing with generous annual discounts (up to 38%)',
    'Excellent KWFinder tool for long-tail keyword research',
    '10-day free trial with no credit card required',
    'Simple and intuitive design across all tools',
    'Good keyword difficulty scoring system',
    'Fast SERP analysis with rich competitor data',
    'Great value for freelancers and small businesses',
    'Browser extension for quick SEO insights',
  ],
  cons: [
    'Limited database compared to enterprise tools',
    'No comprehensive site audit functionality',
    'Daily lookup limits can be restrictive for heavy users',
    'No API access available',
    'Limited advanced features for experienced SEOs',
    'No content optimization or AI writing tools',
    'Backlink database is smaller than competitors',
    'Technical SEO features are very limited',
  ],
  useCases: [
    {
      title: 'Solo Bloggers & Freelancers',
      description: 'Perfect for individual creators who need simple, affordable keyword research and rank tracking tools',
      audience: 'solo',
      budget: 'budget',
    },
    {
      title: 'Small Business Owners',
      description: 'Easy-to-learn tools for business owners managing their own SEO without a dedicated team',
      audience: 'solo',
      budget: 'budget',
    },
    {
      title: 'SEO Beginners',
      description: 'Ideal entry point for learning SEO with intuitive tools and helpful educational content',
      audience: 'solo',
      budget: 'budget',
    },
    {
      title: 'Content Creators',
      description: 'KWFinder helps discover low-competition keywords for blog posts and content marketing',
      audience: 'solo',
      budget: 'mid-range',
    },
  ],
  lastUpdated: '2026-03-07',
  source: 'mangools.com/pricing',
};
