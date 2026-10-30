import { CompetitorData } from '../types';

/**
 * SE Ranking Competitor Data
 * Task: TASK-32-12 - Tier 2 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Data sourced from seranking.com/pricing.html (March 2026)
 */
export const seranking: CompetitorData = {
  id: 'seranking',
  name: 'SE Ranking',
  slug: 'seranking',
  website: 'https://seranking.com',
  logo: '/images/competitors/seranking.svg',
  tagline: 'All-in-one SEO platform with powerful rank tracking and agency-focused features',
  pricing: [
    {
      name: 'Essential',
      monthlyPrice: 52,
      annualPrice: 39,
      features: [
        '250 tracked keywords',
        '5 projects',
        '1 user',
        'Rank tracking',
        'Website audit',
        'Backlink checker',
        'Keyword research',
        'Competitor analysis',
        'Basic reporting',
      ],
    },
    {
      name: 'Pro',
      monthlyPrice: 95,
      annualPrice: 71,
      features: [
        '1,000 tracked keywords',
        '10 projects',
        '3 users',
        'White label reports',
        'Marketing plan',
        'Content editor',
        'On-page SEO checker',
        'Social media management',
        'Priority support',
      ],
      highlighted: true,
    },
    {
      name: 'Business',
      monthlyPrice: 207,
      annualPrice: 155,
      features: [
        '2,500 tracked keywords',
        'Unlimited projects',
        '10 users',
        'API access',
        'Extended limits',
        'All Pro features',
        'Lead generator',
        'Custom reports',
        'Dedicated support',
      ],
    },
  ],
  features: [
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: true },
        { name: 'On-Page SEO Checker', seoindicator: true, competitor: true, tooltip: 'Page-specific optimization suggestions' },
        { name: 'Content Analysis', seoindicator: true, competitor: true },
        { name: 'Internal Link Analysis', seoindicator: true, competitor: true },
        { name: 'Meta Tag Analysis', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Technical SEO',
      features: [
        { name: 'Page Speed Insights', seoindicator: true, competitor: 'Partial' },
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
        { name: 'Rank Tracking', seoindicator: true, competitor: true, tooltip: 'Highly accurate daily tracking' },
        { name: 'Keyword Gap Analysis', seoindicator: true, competitor: true },
        { name: 'Search Volume Data', seoindicator: true, competitor: true },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: true },
        { name: 'SERP Analysis', seoindicator: true, competitor: true },
        { name: 'Competitor Keywords', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Content',
      features: [
        { name: 'Content Ideas', seoindicator: true, competitor: true },
        { name: 'Content Editor', seoindicator: false, competitor: true, tooltip: 'AI-assisted content optimization' },
        { name: 'AI Content Helper', seoindicator: false, competitor: true, tooltip: 'AI writing suggestions' },
        { name: 'Plagiarism Checker', seoindicator: true, competitor: false },
        { name: 'Readability Score', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Competitor Analysis',
      features: [
        { name: 'Competitor Research', seoindicator: true, competitor: true },
        { name: 'Domain Comparison', seoindicator: true, competitor: true },
        { name: 'Traffic Estimation', seoindicator: true, competitor: true },
        { name: 'Competitor Monitoring', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Marketing Tools',
      features: [
        { name: 'Marketing Plan', seoindicator: false, competitor: true, tooltip: 'Step-by-step SEO task checklist' },
        { name: 'Lead Generator', seoindicator: false, competitor: 'Business' },
        { name: 'Local SEO', seoindicator: true, competitor: true },
        { name: 'Social Media Management', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'White Label Reports', seoindicator: true, competitor: 'Pro+' },
        { name: 'Custom Dashboards', seoindicator: true, competitor: true },
        { name: 'API Access', seoindicator: false, competitor: 'Business' },
        { name: 'PDF Export', seoindicator: true, competitor: true },
        { name: 'Scheduled Reports', seoindicator: true, competitor: true },
        { name: 'Automated Reports', seoindicator: true, competitor: true },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: 'Trial' },
        { name: 'Trial Available', seoindicator: true, competitor: true, tooltip: '14-day free trial' },
        { name: 'Monthly Billing', seoindicator: true, competitor: true },
        { name: 'Annual Discount', seoindicator: true, competitor: '25%' },
        { name: 'Starting Price', seoindicator: 'Free', competitor: '$52/mo' },
      ],
    },
  ],
  pros: [
    'Accurate daily rank tracking with granular location options',
    'Excellent agency features with white-label reporting',
    'Marketing Plan provides step-by-step SEO guidance',
    'Good balance of features at competitive pricing',
    'On-page SEO checker with actionable recommendations',
    'Social media management integration included',
    'AI-assisted content editor for optimization',
    'Strong local SEO capabilities',
    'User-friendly interface with good documentation',
    'Regular platform updates and feature additions',
  ],
  cons: [
    'Smaller database compared to enterprise tools',
    'API access limited to Business tier',
    'Some advanced features require higher plans',
    'No free tier (trial only)',
    'Technical SEO tools could be more comprehensive',
    'Limited integrations with third-party tools',
    'Content editor is basic compared to dedicated tools',
    'Backlink database smaller than Ahrefs or Semrush',
  ],
  useCases: [
    {
      title: 'SEO Agencies',
      description: 'White-label reports, client management, and team collaboration features designed for agencies',
      audience: 'agency',
      budget: 'mid-range',
    },
    {
      title: 'Small to Medium Businesses',
      description: 'Comprehensive all-in-one platform at an affordable price point for growing businesses',
      audience: 'small-team',
      budget: 'mid-range',
    },
    {
      title: 'Local SEO Specialists',
      description: 'Strong local rank tracking and local SEO features for location-based optimization',
      audience: 'agency',
      budget: 'mid-range',
    },
    {
      title: 'Marketing Managers',
      description: 'Marketing Plan feature provides structured approach to SEO implementation',
      audience: 'small-team',
      budget: 'budget',
    },
  ],
  lastUpdated: '2026-03-07',
  source: 'seranking.com/pricing.html',
};
