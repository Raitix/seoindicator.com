import { CompetitorData } from '../types';

/**
 * SpyFu Competitor Data
 * Task: TASK-32-12 - Tier 2 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Data sourced from spyfu.com/plans (March 2026)
 */
export const spyfu: CompetitorData = {
  id: 'spyfu',
  name: 'SpyFu',
  slug: 'spyfu',
  website: 'https://spyfu.com',
  logo: '/images/competitors/spyfu.svg',
  tagline: 'Competitive intelligence tool for spying on competitors\' SEO and PPC strategies',
  pricing: [
    {
      name: 'Basic',
      monthlyPrice: 39,
      annualPrice: 33,
      features: [
        '6,000 row exports',
        '12 months historical data',
        '1,000 tracked keywords',
        '5 projects',
        'Domain overview',
        'Keyword research',
        'Backlink analysis',
        'Limited API access',
      ],
    },
    {
      name: 'Professional',
      monthlyPrice: 79,
      annualPrice: 58,
      features: [
        '50,000 row exports',
        '10+ years historical data',
        '5,000 tracked keywords',
        '25 projects',
        'Unlimited searches',
        'API access',
        'Custom reports',
        'Team collaboration',
      ],
      highlighted: true,
    },
    {
      name: 'Team',
      monthlyPrice: 149,
      annualPrice: 116,
      features: [
        'Unlimited row exports',
        '10+ years historical data',
        '15,000 tracked keywords',
        'Unlimited projects',
        '5 user seats included',
        'White label reports',
        'Priority support',
        'Custom API limits',
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
        { name: 'Link Intersect', seoindicator: true, competitor: 'Partial' },
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
        { name: 'Keyword Gap Analysis', seoindicator: true, competitor: 'Kombat', tooltip: 'Competitor keyword comparison tool' },
        { name: 'Search Volume Data', seoindicator: true, competitor: true },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: true },
        { name: 'SERP Analysis', seoindicator: true, competitor: true },
        { name: 'Historical Rankings', seoindicator: true, competitor: true },
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
      name: 'Competitor Intelligence',
      features: [
        { name: 'Competitor Spying', seoindicator: true, competitor: true, tooltip: 'Core feature - see competitor keywords and ads' },
        { name: 'Domain Overview', seoindicator: true, competitor: true },
        { name: 'Kombat', seoindicator: true, competitor: true, tooltip: 'Side-by-side keyword comparison' },
        { name: 'Traffic Estimation', seoindicator: true, competitor: true },
        { name: 'Competitor Backlinks', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'PPC & Advertising',
      features: [
        { name: 'PPC Keyword Research', seoindicator: false, competitor: true },
        { name: 'Ad History', seoindicator: false, competitor: true, tooltip: 'See competitors\' ad copy history' },
        { name: 'Ad Budget Estimation', seoindicator: false, competitor: true },
        { name: 'Negative Keywords', seoindicator: false, competitor: true },
        { name: 'Google Ads Intelligence', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'White Label Reports', seoindicator: true, competitor: 'Team' },
        { name: 'Custom Dashboards', seoindicator: true, competitor: true },
        { name: 'API Access', seoindicator: false, competitor: 'Professional+' },
        { name: 'PDF Export', seoindicator: true, competitor: true },
        { name: 'Scheduled Reports', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: 'Limited' },
        { name: 'Trial Available', seoindicator: true, competitor: false },
        { name: 'Monthly Billing', seoindicator: true, competitor: true },
        { name: 'Annual Discount', seoindicator: true, competitor: '30%' },
        { name: 'Starting Price', seoindicator: 'Free', competitor: '$39/mo' },
      ],
    },
  ],
  pros: [
    'Best-in-class competitor spying and intelligence features',
    'Extensive PPC and Google Ads research capabilities',
    '10+ years of historical ranking data available',
    'Kombat tool for easy keyword gap analysis',
    'Unlimited searches on Professional plan and above',
    'See competitor ad copy and spending estimates',
    'Good value for competitive intelligence focus',
    'Large database of keywords and domains',
    'API access available on Professional tier',
    'Free limited access without signup required',
  ],
  cons: [
    'Limited on-page and technical SEO tools',
    'No integrated site audit functionality',
    'Interface can feel dated compared to newer tools',
    'PPC-focused - not ideal for pure SEO needs',
    'No content creation or optimization features',
    'Learning curve for advanced features',
    'Export limits on lower-tier plans',
    'Smaller backlink database than enterprise tools',
  ],
  useCases: [
    {
      title: 'PPC & Ad Specialists',
      description: 'Unparalleled competitor ad intelligence for Google Ads campaign optimization',
      audience: 'agency',
      budget: 'mid-range',
    },
    {
      title: 'Digital Marketing Agencies',
      description: 'Client pitch support with detailed competitor spending and keyword intelligence',
      audience: 'agency',
      budget: 'mid-range',
    },
    {
      title: 'Competitive Research Teams',
      description: 'Deep competitor analysis for market intelligence and strategy development',
      audience: 'enterprise',
      budget: 'mid-range',
    },
    {
      title: 'Affiliate Marketers',
      description: 'Discover profitable keywords and ad strategies competitors are using',
      audience: 'solo',
      budget: 'budget',
    },
  ],
  lastUpdated: '2026-03-07',
  source: 'spyfu.com/plans',
};
