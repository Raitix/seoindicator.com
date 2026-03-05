/**
 * SEMrush Competitor Data
 * Task: TASK-32-11 - Tier 1 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Comprehensive data for SEMrush SEO toolset comparison.
 * Data validated against semrush.com/pricing
 */

import { CompetitorData } from '../types';

export const semrush: CompetitorData = {
  id: 'semrush',
  name: 'SEMrush',
  slug: 'semrush',
  website: 'https://semrush.com',
  logo: '/images/competitors/semrush.svg',
  tagline: 'All-in-one digital marketing toolkit for SEO, content, and competitive research',
  pricing: [
    {
      name: 'Pro',
      monthlyPrice: 129,
      annualPrice: 108,
      features: [
        '1 user',
        '5 projects',
        '500 keywords to track',
        '100,000 results per report',
        'Site Audit (100,000 pages)',
        'Keyword Research',
        'Backlink Analytics',
        'Position Tracking',
      ],
    },
    {
      name: 'Guru',
      monthlyPrice: 249,
      annualPrice: 208,
      features: [
        '1 user',
        '15 projects',
        '1,500 keywords to track',
        '300,000 results per report',
        'Site Audit (300,000 pages)',
        'Keyword Research',
        'Backlink Analytics',
        'Position Tracking',
        'Content Marketing Platform',
        'Historical data',
      ],
      highlighted: true,
    },
    {
      name: 'Business',
      monthlyPrice: 499,
      annualPrice: 416,
      features: [
        '5 users',
        '25 projects',
        '5,000 keywords to track',
        '500,000 results per report',
        'Site Audit (500,000 pages)',
        'Keyword Research',
        'Backlink Analytics',
        'Position Tracking',
        'Content Marketing Platform',
        'Historical data',
        'API access',
        'White label reports',
        'Share of Voice',
      ],
    },
    {
      name: 'Enterprise',
      monthlyPrice: 'Contact Sales',
      annualPrice: 'Contact Sales',
      features: [
        'Unlimited users',
        'Unlimited projects',
        'Custom keyword limits',
        'Dedicated support',
        'Custom API limits',
        'Advanced integrations',
        'On-demand training',
        'SLA guarantees',
      ],
    },
  ],
  features: [
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: true, tooltip: 'Comprehensive website crawling and analysis' },
        { name: 'On Page SEO Checker', seoindicator: true, competitor: true, tooltip: 'AI-powered optimization recommendations' },
        { name: 'Content Analysis', seoindicator: true, competitor: true, tooltip: 'Analyzes content quality and optimization' },
        { name: 'Internal Link Analysis', seoindicator: true, competitor: false, tooltip: 'Identifies internal linking opportunities' },
        { name: 'Meta Tag Analyzer', seoindicator: true, competitor: false, tooltip: 'Analyzes and optimizes meta tags' },
      ],
    },
    {
      name: 'Technical SEO',
      features: [
        { name: 'Page Speed Insights', seoindicator: true, competitor: false, tooltip: 'Analyzes page load performance' },
        { name: 'Mobile-Friendly Test', seoindicator: true, competitor: false, tooltip: 'Tests mobile responsiveness' },
        { name: 'Structured Data Validator', seoindicator: true, competitor: false, tooltip: 'Validates schema markup' },
        { name: 'HTTPS Check', seoindicator: true, competitor: true, tooltip: 'Verifies SSL certificate and HTTPS implementation' },
        { name: 'Core Web Vitals', seoindicator: true, competitor: false, tooltip: 'Google Core Web Vitals analysis' },
      ],
    },
    {
      name: 'Backlinks',
      features: [
        { name: 'Backlink Analytics', seoindicator: true, competitor: true, tooltip: 'Comprehensive backlink analysis and monitoring' },
        { name: 'Link Intersect', seoindicator: true, competitor: false, tooltip: 'Finds sites linking to competitors but not you' },
        { name: 'Toxic Link Analysis', seoindicator: true, competitor: false, tooltip: 'Identifies potentially harmful backlinks' },
        { name: 'Anchor Text Analysis', seoindicator: true, competitor: true, tooltip: 'Analyzes anchor text distribution' },
        { name: 'Broken Link Checker', seoindicator: true, competitor: false, tooltip: 'Finds broken backlinks pointing to your site' },
        { name: 'Referring Domains', seoindicator: true, competitor: true, tooltip: 'Shows all domains linking to any URL or domain' },
      ],
    },
    {
      name: 'Keywords',
      features: [
        { name: 'Keyword Research', seoindicator: true, competitor: true, tooltip: 'Discover and analyze keyword opportunities' },
        { name: 'Position Tracking', seoindicator: true, competitor: true, tooltip: 'Track keyword positions in search results' },
        { name: 'Keyword Gap Analysis', seoindicator: true, competitor: true, tooltip: 'Find keywords competitors rank for that you don\'t' },
        { name: 'Search Volume Data', seoindicator: true, competitor: true, tooltip: 'Historical and current search volume data' },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: true, tooltip: 'Estimates how hard it is to rank for a keyword' },
        { name: 'SERP Analysis', seoindicator: true, competitor: true, tooltip: 'Analyze search results for any keyword' },
      ],
    },
    {
      name: 'Content',
      features: [
        { name: 'Content Marketing Platform', seoindicator: false, competitor: true, tooltip: 'Plan, create, and optimize content' },
        { name: 'Content Ideas', seoindicator: true, competitor: true, tooltip: 'Generate content topic suggestions' },
        { name: 'SEO Writing Assistant', seoindicator: true, competitor: true, tooltip: 'Real-time content optimization suggestions' },
        { name: 'Plagiarism Checker', seoindicator: false, competitor: true, tooltip: 'Check content for duplicate text' },
        { name: 'Readability Analysis', seoindicator: true, competitor: true, tooltip: 'Analyzes content readability scores' },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'White Label Reports', seoindicator: true, competitor: true, tooltip: 'Custom branded reports for clients' },
        { name: 'Custom Dashboards', seoindicator: true, competitor: true, tooltip: 'Build personalized reporting dashboards' },
        { name: 'API Access', seoindicator: true, competitor: true, tooltip: 'Programmatic access to data' },
        { name: 'Scheduled Reports', seoindicator: true, competitor: true, tooltip: 'Automated report generation and delivery' },
        { name: 'PDF Export', seoindicator: true, competitor: true, tooltip: 'Export reports as PDF documents' },
      ],
    },
    {
      name: 'Additional Tools',
      features: [
        { name: 'Traffic Analytics', seoindicator: false, competitor: true, tooltip: 'Analyze competitor website traffic' },
        { name: 'Social Media Poster', seoindicator: false, competitor: true, tooltip: 'Schedule and publish social media posts' },
        { name: 'Advertising Toolkit', seoindicator: false, competitor: true, tooltip: 'PPC research and optimization tools' },
        { name: 'Local SEO Tools', seoindicator: true, competitor: true, tooltip: 'Manage local business listings' },
      ],
    },
    {
      name: 'Pricing & Value',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: false, tooltip: 'Free plan with limited features' },
        { name: 'Trial Available', seoindicator: true, competitor: true, tooltip: '7-day free trial available' },
        { name: 'Annual Discount', seoindicator: true, competitor: true, tooltip: 'Save 16% with annual billing' },
        { name: 'Money-Back Guarantee', seoindicator: true, competitor: false, tooltip: '7-day refund policy on annual plans' },
      ],
    },
  ],
  pros: [
    'Massive keyword database with 25+ billion keywords',
    'Comprehensive competitive intelligence features',
    'All-in-one marketing toolkit including PPC and social',
    'Excellent content marketing and SEO writing tools',
    'Strong local SEO capabilities',
    'Good API access and integration options',
    'Regular feature updates and improvements',
  ],
  cons: [
    'Higher price point compared to alternatives',
    'Interface can feel overwhelming for beginners',
    'API access requires Business tier or higher',
    'Limited on-page technical SEO analysis',
    'Some features require separate upgrades',
    'Annual billing required for best pricing',
  ],
  useCases: [
    {
      title: 'Digital Marketing Agencies',
      description: 'Complete toolkit for SEO, content, PPC, and social media makes SEMrush ideal for agencies offering comprehensive marketing services.',
      audience: 'agency',
      budget: 'premium',
    },
    {
      title: 'Content Marketing Teams',
      description: 'Content Marketing Platform with SEO Writing Assistant helps teams create optimized content at scale.',
      audience: 'small-team',
      budget: 'mid-range',
    },
    {
      title: 'Competitive Intelligence Analysts',
      description: 'Traffic Analytics and Market Explorer provide deep insights into competitor strategies and market opportunities.',
      audience: 'enterprise',
      budget: 'premium',
    },
    {
      title: 'Local Businesses',
      description: 'Listing Management and local SEO tools help businesses improve visibility in local search results.',
      audience: 'small-team',
      budget: 'mid-range',
    },
    {
      title: 'PPC Advertisers',
      description: 'Advertising toolkit with keyword research and competitive analysis supports paid search campaign optimization.',
      audience: 'agency',
      budget: 'mid-range',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'https://semrush.com/pricing',
};
