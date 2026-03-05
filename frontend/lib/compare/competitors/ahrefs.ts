/**
 * Ahrefs Competitor Data
 * Task: TASK-32-11 - Tier 1 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Comprehensive data for Ahrefs SEO toolset comparison.
 * Data validated against ahrefs.com/pricing
 */

import { CompetitorData } from '../types';

export const ahrefs: CompetitorData = {
  id: 'ahrefs',
  name: 'Ahrefs',
  slug: 'ahrefs',
  website: 'https://ahrefs.com',
  logo: '/images/competitors/ahrefs.svg',
  tagline: 'All-in-one SEO toolset for backlinks, keywords, and content',
  pricing: [
    {
      name: 'Lite',
      monthlyPrice: 99,
      annualPrice: 83,
      features: [
        '1 user',
        '5 campaigns',
        'Site Audit (500 pages)',
        'Keywords Explorer',
        'Backlinks Checker',
        'Content Explorer',
        'Rank Tracker (250 keywords)',
      ],
    },
    {
      name: 'Standard',
      monthlyPrice: 199,
      annualPrice: 166,
      features: [
        '1 user',
        '10 campaigns',
        'Site Audit (1,500 pages)',
        'Keywords Explorer',
        'Backlinks Checker',
        'Content Explorer',
        'Rank Tracker (500 keywords)',
        'Historical data',
      ],
      highlighted: true,
    },
    {
      name: 'Advanced',
      monthlyPrice: 399,
      annualPrice: 332,
      features: [
        '1 user',
        '25 campaigns',
        'Site Audit (5,000 pages)',
        'Keywords Explorer',
        'Backlinks Checker',
        'Content Explorer',
        'Rank Tracker (1,000 keywords)',
        'Historical data',
        'API access',
        'White label reports',
      ],
    },
    {
      name: 'Enterprise',
      monthlyPrice: 999,
      annualPrice: 832,
      features: [
        '5 users',
        '100 campaigns',
        'Site Audit (25,000 pages)',
        'Keywords Explorer',
        'Backlinks Checker',
        'Content Explorer',
        'Rank Tracker (5,000 keywords)',
        'Historical data',
        'API access',
        'White label reports',
        'Dedicated support',
        'Custom integrations',
      ],
    },
  ],
  features: [
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: true, tooltip: 'Crawls and analyzes your website for SEO issues' },
        { name: 'On-Page Analyzer', seoindicator: true, competitor: false, tooltip: 'Analyzes individual pages for optimization opportunities' },
        { name: 'Content Analysis', seoindicator: true, competitor: true, tooltip: 'Analyzes content quality and optimization' },
        { name: 'Internal Link Analysis', seoindicator: true, competitor: true, tooltip: 'Identifies internal linking opportunities' },
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
        { name: 'Backlink Checker', seoindicator: true, competitor: true, tooltip: 'Analyzes backlink profile and referring domains' },
        { name: 'Link Intersect', seoindicator: true, competitor: true, tooltip: 'Finds sites linking to competitors but not you' },
        { name: 'Toxic Link Analysis', seoindicator: true, competitor: false, tooltip: 'Identifies potentially harmful backlinks' },
        { name: 'Anchor Text Analysis', seoindicator: true, competitor: true, tooltip: 'Analyzes anchor text distribution' },
        { name: 'Broken Link Checker', seoindicator: true, competitor: true, tooltip: 'Finds broken backlinks pointing to your site' },
        { name: 'Referring Domains', seoindicator: true, competitor: true, tooltip: 'Shows all domains linking to any URL or domain' },
      ],
    },
    {
      name: 'Keywords',
      features: [
        { name: 'Keyword Research', seoindicator: true, competitor: true, tooltip: 'Discover and analyze keyword opportunities' },
        { name: 'Rank Tracking', seoindicator: true, competitor: true, tooltip: 'Track keyword positions in search results' },
        { name: 'Keyword Gap Analysis', seoindicator: true, competitor: true, tooltip: 'Find keywords competitors rank for that you don\'t' },
        { name: 'Search Volume Data', seoindicator: true, competitor: true, tooltip: 'Historical and current search volume data' },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: true, tooltip: 'Estimates how hard it is to rank for a keyword' },
        { name: 'SERP Analysis', seoindicator: true, competitor: true, tooltip: 'Analyze search results for any keyword' },
      ],
    },
    {
      name: 'Content',
      features: [
        { name: 'Content Explorer', seoindicator: false, competitor: true, tooltip: 'Find popular content in any niche' },
        { name: 'Content Ideas', seoindicator: true, competitor: true, tooltip: 'Generate content topic suggestions' },
        { name: 'SEO Writing Assistant', seoindicator: true, competitor: false, tooltip: 'Real-time content optimization suggestions' },
        { name: 'Plagiarism Checker', seoindicator: false, competitor: false, tooltip: 'Check content for duplicate text' },
        { name: 'Readability Analysis', seoindicator: true, competitor: false, tooltip: 'Analyzes content readability scores' },
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
      name: 'Pricing & Value',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: false, tooltip: 'Free plan with limited features' },
        { name: 'Trial Available', seoindicator: true, competitor: true, tooltip: '7-day trial for $7' },
        { name: 'Annual Discount', seoindicator: true, competitor: true, tooltip: 'Save 16% with annual billing' },
        { name: 'Money-Back Guarantee', seoindicator: true, competitor: false, tooltip: '7-day refund policy on annual plans' },
      ],
    },
  ],
  pros: [
    'Industry-leading backlink database with trillions of links',
    'Comprehensive keyword research with accurate volume data',
    'Excellent content explorer for topic discovery',
    'Regular data updates with fresh index',
    'Intuitive and user-friendly interface',
    'Fast site audit with actionable recommendations',
    'Strong API documentation for integrations',
  ],
  cons: [
    'No free tier available (trial only)',
    'Expensive for small businesses and solopreneurs',
    'Steeper learning curve for beginners',
    'Limited on-page SEO analysis tools compared to competitors',
    'No dedicated content writing assistant',
    'Higher tiers required for API access',
  ],
  useCases: [
    {
      title: 'Link Building Specialists',
      description: 'Best-in-class backlink analysis makes Ahrefs ideal for SEO professionals focused on link building campaigns and outreach.',
      audience: 'agency',
      budget: 'premium',
    },
    {
      title: 'Enterprise SEO Teams',
      description: 'Comprehensive data and API access support large-scale SEO operations and custom reporting workflows.',
      audience: 'enterprise',
      budget: 'premium',
    },
    {
      title: 'Content Marketing Teams',
      description: 'Content Explorer helps content teams discover trending topics and analyze competitor content strategies.',
      audience: 'agency',
      budget: 'mid-range',
    },
    {
      title: 'SEO Agencies',
      description: 'White-label reporting and multi-campaign support make it suitable for agencies managing multiple clients.',
      audience: 'agency',
      budget: 'premium',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'https://ahrefs.com/pricing',
};
