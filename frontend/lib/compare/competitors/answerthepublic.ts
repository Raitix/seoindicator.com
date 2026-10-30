/**
 * AnswerThePublic Competitor Data
 * Task: TASK-32-14 - Free Alternatives Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Positioning: Visual keyword research tool with limited free tier.
 * Great for content ideation but lacks comprehensive SEO analysis.
 */

import { CompetitorData } from '../types';

export const answerthepublic: CompetitorData = {
  id: 'answerthepublic',
  name: 'AnswerThePublic',
  slug: 'answerthepublic',
  website: 'https://answerthepublic.com',
  logo: '/images/competitors/answerthepublic.svg',
  tagline: 'Visual keyword research tool for discovering search questions',
  pricing: [
    {
      name: 'Free',
      monthlyPrice: 'Free',
      annualPrice: 'Free',
      features: [
        '3 searches per day',
        'Basic visualizations',
        'Question wheel',
        'Preposition maps',
        'Alphabetical lists',
        'Related topics',
      ],
    },
    {
      name: 'Pro',
      monthlyPrice: 99,
      annualPrice: 79,
      features: [
        'Unlimited searches',
        'High-resolution images',
        'CSV export',
        'Priority data',
        'Compare searches',
        'Folder organization',
      ],
    },
  ],
  features: [
    {
      name: 'Keyword Research',
      features: [
        { name: 'Question Keywords', seoindicator: true, competitor: true },
        { name: 'Visual Keyword Map', seoindicator: false, competitor: true },
        { name: 'Search Volume', seoindicator: true, competitor: false },
        { name: 'Keyword Difficulty', seoindicator: true, competitor: false },
        { name: 'SERP Analysis', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Content Ideas',
      features: [
        { name: 'Content Suggestions', seoindicator: true, competitor: true },
        { name: 'Question Analysis', seoindicator: true, competitor: true },
        { name: 'Preposition Ideas', seoindicator: false, competitor: true },
        { name: 'Comparison Keywords', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'SEO Analysis',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: false },
        { name: 'On-Page Analysis', seoindicator: true, competitor: false },
        { name: 'Rank Tracking', seoindicator: true, competitor: false },
        { name: 'Backlink Analysis', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'CSV Export', seoindicator: true, competitor: 'Pro' },
        { name: 'PDF Reports', seoindicator: true, competitor: false },
        { name: 'White Label', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: true },
        { name: 'Daily Search Limit', seoindicator: false, competitor: '3/day' },
        { name: 'Starting Price', seoindicator: 'Free', competitor: '$99/mo' },
      ],
    },
  ],
  pros: [
    'Unique visual keyword presentations',
    'Great for content brainstorming',
    'Discovers question-based keywords',
    'Free tier available (3 searches/day)',
    'Intuitive and easy to use',
    'Good for finding content gaps',
    'Multiple visualization types',
    'Useful for FAQ content creation',
  ],
  cons: [
    'Very limited free tier (3 searches/day)',
    'No SEO analysis tools',
    'No rank tracking',
    'No backlink data',
    'No search volume in free tier',
    'Expensive Pro tier ($99/month)',
    'No competitor analysis',
    'No site audit capabilities',
  ],
  useCases: [
    {
      title: 'Content Ideation',
      description: 'Discover what questions people ask about your topic for content creation',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'FAQ Content',
      description: 'Find questions to answer in your FAQ pages and blog posts',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Keyword Discovery',
      description: 'Uncover long-tail keywords and question-based search queries',
      audience: 'small-team',
      budget: 'budget',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'answerthepublic.com',
};

export default answerthepublic;
