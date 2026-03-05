/**
 * Moz Competitor Data
 * Task: TASK-32-11 - Tier 1 Competitor Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Comprehensive data for Moz SEO toolset comparison.
 * Data validated against moz.com/products
 */

import { CompetitorData } from '../types';

export const moz: CompetitorData = {
  id: 'moz',
  name: 'Moz',
  slug: 'moz',
  website: 'https://moz.com',
  logo: '/images/competitors/moz.svg',
  tagline: 'SEO software and data to help you increase traffic, rankings, and visibility',
  pricing: [
    {
      name: 'Free',
      monthlyPrice: 'Free',
      annualPrice: 'Free',
      features: [
        'Community account',
        'Limited Keyword Explorer queries',
        'Limited Link Explorer queries',
        'Limited Site Crawl (1 site)',
        'MozBar browser extension',
        'Access to Moz Academy free courses',
      ],
    },
    {
      name: 'Starter',
      monthlyPrice: 99,
      annualPrice: 79,
      features: [
        '1 user',
        '2 campaigns',
        'Rank tracking (50 keywords)',
        'Site Crawl (50,000 pages)',
        'Keyword Explorer queries (150/month)',
        'Link Explorer queries (50/month)',
        'On-Page Grader',
      ],
    },
    {
      name: 'Standard',
      monthlyPrice: 179,
      annualPrice: 143,
      features: [
        '1 user',
        '5 campaigns',
        'Rank tracking (300 keywords)',
        'Site Crawl (100,000 pages)',
        'Keyword Explorer queries (500/month)',
        'Link Explorer queries (200/month)',
        'On-Page Grader',
        'Custom reports',
      ],
      highlighted: true,
    },
    {
      name: 'Medium',
      monthlyPrice: 299,
      annualPrice: 239,
      features: [
        '3 users',
        '10 campaigns',
        'Rank tracking (1,000 keywords)',
        'Site Crawl (200,000 pages)',
        'Keyword Explorer queries (1,000/month)',
        'Link Explorer queries (500/month)',
        'On-Page Grader',
        'Custom reports',
        'API access',
      ],
    },
    {
      name: 'Large',
      monthlyPrice: 599,
      annualPrice: 479,
      features: [
        '5 users',
        '25 campaigns',
        'Rank tracking (2,500 keywords)',
        'Site Crawl (400,000 pages)',
        'Keyword Explorer queries (2,500/month)',
        'Link Explorer queries (1,000/month)',
        'On-Page Grader',
        'Custom reports',
        'API access',
        'White label reports',
      ],
    },
  ],
  features: [
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: true, tooltip: 'Crawls and analyzes your website for SEO issues' },
        { name: 'On-Page Grader', seoindicator: true, competitor: true, tooltip: 'Grades individual pages for SEO optimization' },
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
        { name: 'HTTPS Check', seoindicator: true, competitor: true, tooltip: 'Verifies SSL certificate and HTTPS implementation' },
        { name: 'Core Web Vitals', seoindicator: true, competitor: false, tooltip: 'Google Core Web Vitals analysis' },
      ],
    },
    {
      name: 'Backlinks',
      features: [
        { name: 'Link Explorer', seoindicator: true, competitor: true, tooltip: 'Analyze backlink profile and discover link opportunities' },
        { name: 'Link Intersect', seoindicator: true, competitor: false, tooltip: 'Finds sites linking to competitors but not you' },
        { name: 'Spam Score', seoindicator: true, competitor: true, tooltip: 'Identifies potentially harmful backlinks' },
        { name: 'Anchor Text Analysis', seoindicator: true, competitor: true, tooltip: 'Analyzes anchor text distribution' },
        { name: 'Broken Link Checker', seoindicator: true, competitor: false, tooltip: 'Finds broken backlinks pointing to your site' },
        { name: 'Referring Domains', seoindicator: true, competitor: true, tooltip: 'Shows all domains linking to any URL or domain' },
      ],
    },
    {
      name: 'Keywords',
      features: [
        { name: 'Keyword Explorer', seoindicator: true, competitor: true, tooltip: 'Discover and analyze keyword opportunities' },
        { name: 'Rank Tracking', seoindicator: true, competitor: true, tooltip: 'Track keyword positions in search results' },
        { name: 'Keyword Gap Analysis', seoindicator: true, competitor: false, tooltip: 'Find keywords competitors rank for that you don\'t' },
        { name: 'Search Volume Data', seoindicator: true, competitor: true, tooltip: 'Historical and current search volume data' },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: true, tooltip: 'Estimates how hard it is to rank for a keyword' },
        { name: 'SERP Analysis', seoindicator: true, competitor: false, tooltip: 'Analyze search results for any keyword' },
      ],
    },
    {
      name: 'Content',
      features: [
        { name: 'Content Ideas', seoindicator: true, competitor: false, tooltip: 'Generate content topic suggestions' },
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
      name: 'Unique Metrics',
      features: [
        { name: 'Domain Authority (DA)', seoindicator: true, competitor: true, tooltip: 'Predictive score for ranking potential' },
        { name: 'Page Authority (PA)', seoindicator: true, competitor: true, tooltip: 'Predictive score for page ranking potential' },
        { name: 'Spam Score', seoindicator: true, competitor: true, tooltip: 'Indicates potential link spam' },
        { name: 'MozRank', seoindicator: false, competitor: true, tooltip: 'Link popularity metric' },
      ],
    },
    {
      name: 'Learning & Community',
      features: [
        { name: 'Moz Academy', seoindicator: false, competitor: true, tooltip: 'Free and paid SEO courses' },
        { name: 'Moz Blog', seoindicator: false, competitor: true, tooltip: 'Industry-leading SEO blog' },
        { name: 'MozBar', seoindicator: false, competitor: true, tooltip: 'Free browser extension for SEO metrics' },
        { name: 'Community Forums', seoindicator: false, competitor: true, tooltip: 'Active community for Q&A' },
      ],
    },
    {
      name: 'Pricing & Value',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: true, tooltip: 'Free plan with limited features' },
        { name: 'Trial Available', seoindicator: true, competitor: true, tooltip: '30-day free trial available' },
        { name: 'Annual Discount', seoindicator: true, competitor: true, tooltip: 'Save 20% with annual billing' },
        { name: 'Money-Back Guarantee', seoindicator: true, competitor: true, tooltip: '30-day money-back guarantee' },
      ],
    },
  ],
  pros: [
    'Domain Authority (DA) is industry-standard metric',
    'Beginner-friendly with excellent educational resources',
    'Free tier with meaningful functionality',
    'MozBar browser extension provides quick insights',
    'Strong community and support resources',
    'Moz Academy offers quality SEO training',
    'More affordable than premium alternatives',
    'Established reputation and trust in SEO industry',
  ],
  cons: [
    'Smaller link index compared to Ahrefs and SEMrush',
    'Less comprehensive keyword data',
    'Fewer advanced features than competitors',
    'Limited content optimization tools',
    'No built-in content explorer',
    'API access requires Medium tier or higher',
  ],
  useCases: [
    {
      title: 'SEO Beginners',
      description: 'Free tier, educational resources, and beginner-friendly interface make Moz ideal for those new to SEO.',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Small Business Owners',
      description: 'Affordable pricing with essential SEO tools helps small businesses improve search visibility without enterprise costs.',
      audience: 'small-team',
      budget: 'budget',
    },
    {
      title: 'Digital Marketing Students',
      description: 'Moz Academy and free resources provide excellent learning opportunities for aspiring SEO professionals.',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Link Building Professionals',
      description: 'Domain Authority and Spam Score metrics help evaluate link opportunities and identify toxic backlinks.',
      audience: 'agency',
      budget: 'mid-range',
    },
    {
      title: 'Marketing Agencies on Budget',
      description: 'White label reports and affordable pricing make Moz suitable for agencies with cost constraints.',
      audience: 'agency',
      budget: 'mid-range',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'https://moz.com/products',
};
