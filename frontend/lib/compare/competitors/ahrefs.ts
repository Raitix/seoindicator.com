import { CompetitorData } from '../types';

/**
 * Ahrefs Competitor Data
 * Task: TASK-32-11 - Tier 1 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Data sourced from ahrefs.com/pricing (March 2026)
 */
export const ahrefs: CompetitorData = {
  id: 'ahrefs',
  name: 'Ahrefs',
  slug: 'ahrefs',
  website: 'https://ahrefs.com',
  logo: '/images/competitors/ahrefs.svg',
  tagline: 'All-in-one SEO toolset for backlinks, keywords, and content',
  pricing: [
    {
      name: 'Starter',
      monthlyPrice: 29,
      annualPrice: 24,
      features: [
        '1 user',
        '1 project',
        'See what people search',
        'Spy on competitors',
        'Limited data access',
      ],
    },
    {
      name: 'Lite',
      monthlyPrice: 119,
      annualPrice: 99,
      features: [
        '1 user',
        '5 projects',
        '6 months historical data',
        '750 tracked keywords',
        '100,000 crawl credits',
        'Site Audit',
        'Keywords Explorer',
        'Site Explorer',
      ],
    },
    {
      name: 'Standard',
      monthlyPrice: 229,
      annualPrice: 191,
      features: [
        '1 user',
        '20 projects',
        '2 years historical data',
        '2,000 tracked keywords',
        '500,000 crawl credits',
        'Content Explorer',
        'Batch Analysis',
        'SERP comparison',
      ],
    },
    {
      name: 'Advanced',
      monthlyPrice: 419,
      annualPrice: 349,
      features: [
        '1 user',
        '50 projects',
        '5 years historical data',
        '5,000 tracked keywords',
        '1,500,000 crawl credits',
        'Looker Studio integration',
        'Agency directory listing',
        'API access',
      ],
    },
    {
      name: 'Enterprise',
      monthlyPrice: 1394,
      annualPrice: 1162,
      features: [
        'Unlimited users',
        'Unlimited projects',
        'Unlimited historical data',
        'Custom keyword limits',
        'SSO & enterprise security',
        'Access management',
        'Personalized higher limits',
        'Forecasting & trends',
      ],
    },
  ],
  features: [
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: true },
        { name: 'On-Page Analyzer', seoindicator: true, competitor: false },
        { name: 'Content Analysis', seoindicator: true, competitor: 'Partial' },
        { name: 'Internal Link Analysis', seoindicator: true, competitor: true },
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
        { name: 'Link Intersect', seoindicator: true, competitor: true },
        { name: 'Toxic Link Detection', seoindicator: true, competitor: false },
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
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'White Label Reports', seoindicator: true, competitor: 'Add-on' },
        { name: 'Custom Dashboards', seoindicator: true, competitor: true },
        { name: 'API Access', seoindicator: false, competitor: 'Enterprise' },
        { name: 'PDF Export', seoindicator: true, competitor: true },
        { name: 'Scheduled Reports', seoindicator: true, competitor: 'Add-on' },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: 'Limited' },
        { name: 'Trial Available', seoindicator: true, competitor: false },
        { name: 'Monthly Billing', seoindicator: true, competitor: true },
        { name: 'Annual Discount', seoindicator: true, competitor: '17%' },
        { name: 'Starting Price', seoindicator: 'Free', competitor: '$29/mo' },
      ],
    },
  ],
  pros: [
    'Industry-leading backlink database with trillions of links',
    'Comprehensive keyword research with accurate search volume',
    'Excellent content explorer for finding link opportunities',
    'Fast and reliable Site Audit tool',
    'Regular data updates and index refreshes',
    'Strong Site Explorer for competitive analysis',
    'Good historical data retention (up to 5 years)',
    'API access available on Enterprise plans',
  ],
  cons: [
    'No free tier (Webmaster Tools only, requires ownership)',
    'Expensive for small businesses and freelancers',
    'Steeper learning curve for beginners',
    'Limited on-page SEO analysis tools',
    'No integrated rank tracking updates in lower tiers',
    'No white-label reports on standard plans',
    'Limited technical SEO features',
    'Credits system can be confusing',
  ],
  useCases: [
    {
      title: 'Link Building Specialists',
      description: 'Best-in-class backlink analysis for outreach campaigns and link prospecting',
      audience: 'agency',
      budget: 'premium',
    },
    {
      title: 'Enterprise SEO Teams',
      description: 'Comprehensive data and API access for large-scale SEO operations',
      audience: 'enterprise',
      budget: 'premium',
    },
    {
      title: 'Content Marketing Teams',
      description: 'Content Explorer helps find trending topics and competitor content gaps',
      audience: 'small-team',
      budget: 'mid-range',
    },
    {
      title: 'Competitive Research',
      description: 'Deep competitor analysis with traffic estimation and keyword gaps',
      audience: 'agency',
      budget: 'mid-range',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'ahrefs.com/pricing',
};
