/**
 * SEOIndicator Competitor Data
 * Task: TASK-32-2 - Implement Comparison Registry and Data Loading
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Canonical SEOIndicator data for comparison pages.
 * This represents the official SEOIndicator feature set and pricing.
 *
 * Last verified: 2026-03-05
 * Source: seoindicator.com/pricing
 */

import { CompetitorData } from '../types';

/**
 * SEOIndicator competitor data for comparison pages.
 * This is the canonical reference for SEOIndicator's features, pricing, and capabilities.
 */
export const seoindicator: CompetitorData = {
  id: 'seoindicator',
  name: 'SEOIndicator',
  slug: 'seoindicator',
  website: 'https://seoindicator.com',
  logo: '/images/seoindicator-logo.svg',
  tagline: 'Affordable SEO rank tracking and keyword monitoring for startups and small businesses',

  pricing: [
    {
      name: 'Free',
      monthlyPrice: 'Free',
      annualPrice: 'Free',
      features: [
        '1 project',
        '10 tracked keywords',
        'Basic rank tracking',
        'Weekly updates',
        'Email notifications',
      ],
    },
    {
      name: 'Pro',
      monthlyPrice: 19,
      annualPrice: 15,
      highlighted: true,
      features: [
        '5 projects',
        '100 tracked keywords',
        'Daily rank tracking',
        'Competitor analysis',
        'Keyword suggestions',
        'Site audit',
        'Backlink monitoring',
        'PDF reports',
        'API access',
      ],
    },
    {
      name: 'Business',
      monthlyPrice: 49,
      annualPrice: 39,
      features: [
        '20 projects',
        '500 tracked keywords',
        'All Pro features',
        'White-label reports',
        'Team collaboration',
        'Priority support',
        'Historical data',
        'Custom dashboards',
      ],
    },
    {
      name: 'Agency',
      monthlyPrice: 99,
      annualPrice: 79,
      features: [
        'Unlimited projects',
        '2,000 tracked keywords',
        'All Business features',
        'API access',
        'Dedicated support',
        'Custom integrations',
        'SLA guarantee',
      ],
    },
  ],

  features: [
    {
      name: 'Rank Tracking',
      features: [
        { name: 'Daily Updates', seoindicator: true, competitor: true, tooltip: 'Track keyword positions daily' },
        { name: 'Mobile & Desktop', seoindicator: true, competitor: true, tooltip: 'Separate tracking for mobile and desktop' },
        { name: 'Local Tracking', seoindicator: true, competitor: true, tooltip: 'Track rankings in specific locations' },
        { name: 'Competitor Comparison', seoindicator: true, competitor: true, tooltip: 'Compare rankings with competitors' },
      ],
    },
    {
      name: 'Keyword Research',
      features: [
        { name: 'Keyword Suggestions', seoindicator: true, competitor: true, tooltip: 'Discover new keyword opportunities' },
        { name: 'Search Volume', seoindicator: true, competitor: true, tooltip: 'Accurate search volume data' },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: true, tooltip: 'Estimate ranking difficulty' },
        { name: 'SERP Analysis', seoindicator: true, competitor: true, tooltip: 'Analyze search results' },
      ],
    },
    {
      name: 'Site Audit',
      features: [
        { name: 'Technical SEO', seoindicator: true, competitor: true, tooltip: 'Identify technical SEO issues' },
        { name: 'On-Page Analysis', seoindicator: true, competitor: true, tooltip: 'Page-level optimization suggestions' },
        { name: 'Page Speed', seoindicator: true, competitor: false, tooltip: 'Core Web Vitals analysis' },
        { name: 'Mobile-Friendly', seoindicator: true, competitor: false, tooltip: 'Mobile usability checking' },
      ],
    },
    {
      name: 'Backlink Analysis',
      features: [
        { name: 'Backlink Monitoring', seoindicator: true, competitor: true, tooltip: 'Track new and lost backlinks' },
        { name: 'Link Analysis', seoindicator: true, competitor: true, tooltip: 'Analyze backlink quality' },
        { name: 'Toxic Link Detection', seoindicator: true, competitor: false, tooltip: 'Identify harmful backlinks' },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'PDF Reports', seoindicator: true, competitor: true, tooltip: 'Export professional reports' },
        { name: 'White-Label', seoindicator: true, competitor: true, tooltip: 'Custom branded reports' },
        { name: 'Scheduled Reports', seoindicator: true, competitor: true, tooltip: 'Automated report delivery' },
        { name: 'API Access', seoindicator: true, competitor: true, tooltip: 'Programmatic data access' },
      ],
    },
  ],

  pros: [
    'Most affordable SEO tool on the market',
    'Simple, intuitive interface designed for beginners',
    'All essential SEO features in one platform',
    'Generous free tier for getting started',
    'Fast setup with no learning curve',
    'Excellent customer support',
    'Regular feature updates',
  ],

  cons: [
    'Smaller database compared to enterprise tools',
    'Limited advanced features for power users',
    'No social media or advertising tools',
    'Newer platform with smaller community',
  ],

  useCases: [
    {
      title: 'Solo SEO Consultant',
      description: 'Perfect for freelancers who need professional SEO tools without the enterprise price tag.',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Startup Founders',
      description: 'Affordable SEO tracking with essential features for early-stage businesses',
      audience: 'solo',
      budget: 'budget',
    },
    {
      title: 'Small Business Owners',
      description: 'Easy-to-use toolkit for improving search visibility without the enterprise price tag',
      audience: 'solo',
      budget: 'budget',
    },
    {
      title: 'Marketing Teams',
      description: 'Collaborative SEO platform with reporting and competitor analysis',
      audience: 'small-team',
      budget: 'mid-range',
    },
    {
      title: 'SEO Agencies',
      description: 'White-label reports and multi-client management at an affordable price',
      audience: 'agency',
      budget: 'mid-range',
    },
    {
      title: 'Freelance Marketers',
      description: 'Complete SEO toolkit for managing client projects efficiently',
      audience: 'solo',
      budget: 'budget',
    },
  ],

  lastUpdated: '2026-03-05',
  source: 'Official pricing page and documentation',
};

export default seoindicator;
