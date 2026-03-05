/**
 * Ubersuggest Competitor Data
 * Task: TASK-32-11 - Tier 1 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Comprehensive data for Ubersuggest SEO toolset comparison.
 * Data validated against ubersuggest.com/pricing
 */

import { CompetitorData } from '../types';

export const ubersuggest: CompetitorData = {
  id: 'ubersuggest',
  name: 'Ubersuggest',
  slug: 'ubersuggest',
  website: 'https://ubersuggest.com',
  logo: '/images/competitors/ubersuggest.svg',
  tagline: 'Free keyword tool to find more keywords for your business',
  pricing: [
    {
      name: 'Free',
      monthlyPrice: 'Free',
      annualPrice: 'Free',
      features: [
        '3 daily searches',
        '1 project',
        'Limited keyword suggestions',
        'Limited backlink data',
        'Basic site audit',
        'Chrome extension',
      ],
    },
    {
      name: 'Individual',
      monthlyPrice: 29,
      annualPrice: 12,
      features: [
        '1 user',
        '1 project',
        'Rank tracking (100 keywords)',
        'Site audit (100 pages)',
        'Keyword suggestions',
        'Backlink analysis',
        'Competitor analysis',
        'Content ideas',
        'Daily rank updates',
      ],
    },
    {
      name: 'Business',
      monthlyPrice: 99,
      annualPrice: 40,
      features: [
        '2 users',
        '7 projects',
        'Rank tracking (500 keywords)',
        'Site audit (500 pages)',
        'Keyword suggestions',
        'Backlink analysis',
        'Competitor analysis',
        'Content ideas',
        'Daily rank updates',
        'API access',
      ],
      highlighted: true,
    },
    {
      name: 'Enterprise',
      monthlyPrice: 199,
      annualPrice: 80,
      features: [
        '5 users',
        '15 projects',
        'Rank tracking (2,500 keywords)',
        'Site audit (1,000 pages)',
        'Keyword suggestions',
        'Backlink analysis',
        'Competitor analysis',
        'Content ideas',
        'Daily rank updates',
        'API access',
        'Priority support',
      ],
    },
  ],
  features: [
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: true, tooltip: 'Crawls and analyzes your website for SEO issues' },
        { name: 'On-Page Analyzer', seoindicator: true, competitor: false, tooltip: 'Analyzes individual pages for optimization opportunities' },
        { name: 'Content Analysis', seoindicator: true, competitor: false, tooltip: 'Analyzes content quality and optimization' },
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
        { name: 'HTTPS Check', seoindicator: true, competitor: false, tooltip: 'Verifies SSL certificate and HTTPS implementation' },
        { name: 'Core Web Vitals', seoindicator: true, competitor: false, tooltip: 'Google Core Web Vitals analysis' },
      ],
    },
    {
      name: 'Backlinks',
      features: [
        { name: 'Backlink Checker', seoindicator: true, competitor: true, tooltip: 'Analyze backlink profile and referring domains' },
        { name: 'Link Intersect', seoindicator: true, competitor: false, tooltip: 'Finds sites linking to competitors but not you' },
        { name: 'Toxic Link Analysis', seoindicator: true, competitor: false, tooltip: 'Identifies potentially harmful backlinks' },
        { name: 'Anchor Text Analysis', seoindicator: true, competitor: false, tooltip: 'Analyzes anchor text distribution' },
        { name: 'Broken Link Checker', seoindicator: true, competitor: false, tooltip: 'Finds broken backlinks pointing to your site' },
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
        { name: 'SERP Analysis', seoindicator: true, competitor: false, tooltip: 'Analyze search results for any keyword' },
      ],
    },
    {
      name: 'Content',
      features: [
        { name: 'Content Ideas', seoindicator: true, competitor: true, tooltip: 'Generate content topic suggestions based on competitors' },
        { name: 'SEO Writing Assistant', seoindicator: true, competitor: false, tooltip: 'Real-time content optimization suggestions' },
        { name: 'Plagiarism Checker', seoindicator: false, competitor: false, tooltip: 'Check content for duplicate text' },
        { name: 'Readability Analysis', seoindicator: true, competitor: false, tooltip: 'Analyzes content readability scores' },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'White Label Reports', seoindicator: true, competitor: false, tooltip: 'Custom branded reports for clients' },
        { name: 'Custom Dashboards', seoindicator: true, competitor: true, tooltip: 'Build personalized reporting dashboards' },
        { name: 'API Access', seoindicator: true, competitor: true, tooltip: 'Programmatic access to data' },
        { name: 'Scheduled Reports', seoindicator: true, competitor: false, tooltip: 'Automated report generation and delivery' },
        { name: 'PDF Export', seoindicator: true, competitor: true, tooltip: 'Export reports as PDF documents' },
      ],
    },
    {
      name: 'Additional Tools',
      features: [
        { name: 'Competitor Analysis', seoindicator: true, competitor: true, tooltip: 'Analyze competitor keywords and traffic' },
        { name: 'Chrome Extension', seoindicator: false, competitor: true, tooltip: 'Browser extension for quick SEO insights' },
        { name: 'Traffic Estimator', seoindicator: true, competitor: true, tooltip: 'Estimate website traffic and value' },
      ],
    },
    {
      name: 'Pricing & Value',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: true, tooltip: 'Free plan with limited features' },
        { name: 'Trial Available', seoindicator: true, competitor: true, tooltip: '7-day trial for $1' },
        { name: 'Annual Discount', seoindicator: true, competitor: true, tooltip: 'Save up to 60% with annual billing' },
        { name: 'Money-Back Guarantee', seoindicator: true, competitor: true, tooltip: '30-day money-back guarantee' },
        { name: 'Lifetime Deal', seoindicator: false, competitor: true, tooltip: 'One-time payment option available' },
      ],
    },
  ],
  pros: [
    'Generous free tier with meaningful functionality',
    'Most affordable option for budget-conscious users',
    'Simple, beginner-friendly interface',
    'Lifetime deal option available (no recurring fees)',
    'Good keyword suggestions and ideas',
    'Chrome extension provides quick insights',
    'Created by Neil Patel with trusted brand recognition',
    'Fast and responsive tool performance',
  ],
  cons: [
    'Smaller database compared to premium tools',
    'Limited advanced features for professionals',
    'Less accurate search volume data',
    'Fewer integrations and API capabilities',
    'Limited site audit depth',
    'No white label reporting',
    'Lower keyword tracking limits',
    'Less comprehensive backlink data',
  ],
  useCases: [
    {
      title: 'Solo Entrepreneurs',
      description: 'Free tier and affordable pricing make Ubersuggest ideal for individuals starting their SEO journey with limited budget.',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Small Business Owners',
      description: 'Budget-friendly option for small businesses who need basic SEO tools without enterprise-level features.',
      audience: 'small-team',
      budget: 'budget',
    },
    {
      title: 'Bloggers and Content Creators',
      description: 'Content ideas and keyword suggestions help bloggers discover topics that drive organic traffic.',
      audience: 'solo',
      budget: 'budget',
    },
    {
      title: 'SEO Beginners',
      description: 'Simple interface and educational content from Neil Patel help beginners learn SEO fundamentals.',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Budget-Conscious Startups',
      description: 'Lifetime deal option provides long-term value without recurring subscription costs.',
      audience: 'small-team',
      budget: 'budget',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'https://ubersuggest.com/pricing',
};
