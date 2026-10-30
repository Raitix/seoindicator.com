import { CompetitorData } from '../types';

/**
 * Serpstat Competitor Data
 * Task: TASK-32-12 - Tier 2 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Data sourced from serpstat.com/pricing (March 2026)
 */
export const serpstat: CompetitorData = {
  id: 'serpstat',
  name: 'Serpstat',
  slug: 'serpstat',
  website: 'https://serpstat.com',
  logo: '/images/competitors/serpstat.svg',
  tagline: 'All-in-one SEO platform with a comprehensive database for growth hacking',
  pricing: [
    {
      name: 'Lite',
      monthlyPrice: 69,
      annualPrice: 59,
      features: [
        '1 user',
        '3 projects',
        '15,000 API credits',
        'Keyword research',
        'Competitor analysis',
        'Backlink analysis',
        'Site Audit',
        'Rank tracking',
      ],
    },
    {
      name: 'Standard',
      monthlyPrice: 149,
      annualPrice: 119,
      features: [
        '3 users',
        '7 projects',
        '40,000 API credits',
        'Team collaboration',
        'White label reports',
        'All Lite features',
        'Keyword clustering',
        'Content marketing tools',
      ],
      highlighted: true,
    },
    {
      name: 'Advanced',
      monthlyPrice: 299,
      annualPrice: 239,
      features: [
        '5 users',
        '15 projects',
        '100,000 API credits',
        'Priority support',
        'Extended historical data',
        'All Standard features',
        'API access',
        'Custom reports',
      ],
    },
    {
      name: 'Enterprise',
      monthlyPrice: 'Custom',
      annualPrice: 'Custom',
      features: [
        'Unlimited users',
        'Unlimited projects',
        'Custom API limits',
        'Dedicated account manager',
        'SLA guarantee',
        'Custom integrations',
        'On-premise option',
        'Advanced security',
      ],
    },
  ],
  features: [
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: true },
        { name: 'On-Page Analyzer', seoindicator: true, competitor: 'Partial' },
        { name: 'Content Analysis', seoindicator: true, competitor: true, tooltip: 'Content marketing module' },
        { name: 'Internal Link Analysis', seoindicator: true, competitor: true },
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
        { name: 'Keyword Clustering', seoindicator: true, competitor: true, tooltip: 'Advanced keyword grouping' },
        { name: 'Keyword Gap Analysis', seoindicator: true, competitor: true },
        { name: 'Search Volume Data', seoindicator: true, competitor: true },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: true },
        { name: 'SERP Analysis', seoindicator: true, competitor: true },
        { name: 'Batch Analysis', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Content',
      features: [
        { name: 'Content Ideas', seoindicator: true, competitor: true },
        { name: 'Content Explorer', seoindicator: false, competitor: true },
        { name: 'AI Content Helper', seoindicator: false, competitor: false },
        { name: 'Plagiarism Checker', seoindicator: true, competitor: false },
        { name: 'Readability Score', seoindicator: true, competitor: false },
        { name: 'Text Analysis', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Competitor Analysis',
      features: [
        { name: 'Domain Analysis', seoindicator: true, competitor: true },
        { name: 'Competitor Research', seoindicator: true, competitor: true },
        { name: 'Traffic Estimation', seoindicator: true, competitor: true },
        { name: 'Market Share Analysis', seoindicator: true, competitor: true },
        { name: 'Historical Data', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Advertising Research',
      features: [
        { name: 'PPC Keyword Research', seoindicator: false, competitor: true },
        { name: 'Ad Copy Analysis', seoindicator: false, competitor: true },
        { name: 'Competitor Ads', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'White Label Reports', seoindicator: true, competitor: 'Standard+' },
        { name: 'Custom Dashboards', seoindicator: true, competitor: true },
        { name: 'API Access', seoindicator: false, competitor: 'Advanced+' },
        { name: 'PDF Export', seoindicator: true, competitor: true },
        { name: 'Scheduled Reports', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: 'Limited' },
        { name: 'Trial Available', seoindicator: true, competitor: true, tooltip: '7-day free trial' },
        { name: 'Monthly Billing', seoindicator: true, competitor: true },
        { name: 'Annual Discount', seoindicator: true, competitor: '20%' },
        { name: 'Starting Price', seoindicator: 'Free', competitor: '$69/mo' },
      ],
    },
  ],
  pros: [
    'All-in-one platform with comprehensive feature set',
    'Massive keyword database with billions of keywords',
    'Excellent keyword clustering functionality',
    'Strong competitor analysis and research tools',
    'Good value for money compared to enterprise alternatives',
    'API access available on higher tiers',
    'White label reports for agencies',
    'Historical data and trend analysis',
    'Active community and educational resources',
    'Regular platform updates and improvements',
  ],
  cons: [
    'Higher starting price compared to basic tools',
    'Learning curve for beginners due to feature depth',
    'No integrated on-page SEO content optimization',
    'API credits system can be confusing',
    'Limited free tier functionality',
    'No AI writing or content generation tools',
    'Technical SEO features are basic',
    'Smaller market presence than top competitors',
  ],
  useCases: [
    {
      title: 'Digital Marketing Agencies',
      description: 'White label reports, team features, and comprehensive tools for managing multiple client projects',
      audience: 'agency',
      budget: 'mid-range',
    },
    {
      title: 'Small to Mid-Size Teams',
      description: 'All-in-one platform that scales with growing SEO needs and team size',
      audience: 'small-team',
      budget: 'mid-range',
    },
    {
      title: 'Growth Hackers',
      description: 'Competitive intelligence and keyword gap analysis for market expansion',
      audience: 'small-team',
      budget: 'premium',
    },
    {
      title: 'E-commerce Businesses',
      description: 'Keyword clustering and competitor research for product page optimization',
      audience: 'enterprise',
      budget: 'mid-range',
    },
  ],
  lastUpdated: '2026-03-07',
  source: 'serpstat.com/pricing',
};
