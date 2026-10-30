/**
 * Google Analytics Competitor Data
 * Task: TASK-32-14 - Free Alternatives Data
 * Spec: SPEC-32 (Competitor Comparison Landing Pages)
 *
 * Positioning: Traffic analytics tool (NOT SEO-specific).
 * GA excels at traffic analysis but lacks SEO-specific features.
 */

import { CompetitorData } from '../types';

export const ga: CompetitorData = {
  id: 'ga',
  name: 'Google Analytics',
  slug: 'google-analytics',
  website: 'https://analytics.google.com',
  logo: '/images/competitors/ga.svg',
  tagline: 'Free web analytics for tracking website traffic and user behavior',
  pricing: [
    {
      name: 'Free',
      monthlyPrice: 'Free',
      annualPrice: 'Free',
      features: [
        'Unlimited traffic tracking',
        'Real-time reports',
        'Audience insights',
        'Acquisition reports',
        'Behavior analysis',
        'Conversion goals',
        'Event tracking',
        'Custom dimensions',
      ],
    },
  ],
  features: [
    {
      name: 'Traffic Analytics',
      features: [
        { name: 'Real-Time Visitors', seoindicator: false, competitor: true },
        { name: 'Traffic Sources', seoindicator: false, competitor: true },
        { name: 'User Behavior', seoindicator: false, competitor: true },
        { name: 'Audience Insights', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'On-Page SEO',
      features: [
        { name: 'Site Audit', seoindicator: true, competitor: false },
        { name: 'Meta Analyzer', seoindicator: true, competitor: false },
        { name: 'Content Analysis', seoindicator: true, competitor: false },
        { name: 'Landing Page Data', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Keywords',
      features: [
        { name: 'Keyword Research', seoindicator: true, competitor: false },
        { name: 'Rank Tracking', seoindicator: true, competitor: false },
        { name: 'Search Console Data', seoindicator: true, competitor: 'Partial' },
        { name: 'Organic Search Queries', seoindicator: true, competitor: 'Partial' },
      ],
    },
    {
      name: 'Backlinks',
      features: [
        { name: 'Backlink Checker', seoindicator: true, competitor: false },
        { name: 'Link Analysis', seoindicator: true, competitor: false },
        { name: 'Referring Domains', seoindicator: true, competitor: false },
      ],
    },
    {
      name: 'Reporting',
      features: [
        { name: 'Custom Dashboards', seoindicator: true, competitor: true },
        { name: 'Goal Tracking', seoindicator: false, competitor: true },
        { name: 'PDF Export', seoindicator: true, competitor: false },
        { name: 'API Access', seoindicator: false, competitor: true },
        { name: 'Email Reports', seoindicator: false, competitor: true },
      ],
    },
    {
      name: 'Pricing & Access',
      features: [
        { name: 'Free Tier', seoindicator: true, competitor: true },
        { name: 'Google Account Required', seoindicator: false, competitor: true },
        { name: 'Tracking Code Required', seoindicator: false, competitor: true },
      ],
    },
  ],
  pros: [
    'Completely free for most websites',
    'Comprehensive traffic analytics',
    'Real-time visitor tracking',
    'Goal and conversion tracking',
    'Audience demographics',
    'Behavior flow analysis',
    'Integration with Google Ads',
    'Powerful API access',
  ],
  cons: [
    'Not SEO-specific - traffic analytics only',
    'No rank tracking capabilities',
    'No backlink analysis',
    'No keyword research tools',
    'Requires JavaScript tracking code',
    'GDPR/privacy considerations',
    'Learning curve for advanced features',
    'No competitor analysis',
  ],
  useCases: [
    {
      title: 'Traffic Analysis',
      description: 'Understand where visitors come from and how they interact with your site',
      audience: 'solo',
      budget: 'free',
    },
    {
      title: 'Conversion Tracking',
      description: 'Set up goals and track conversions from marketing campaigns',
      audience: 'small-team',
      budget: 'free',
    },
    {
      title: 'Audience Insights',
      description: 'Learn about your visitors demographics, interests, and behavior',
      audience: 'small-team',
      budget: 'free',
    },
  ],
  lastUpdated: '2026-03-05',
  source: 'analytics.google.com',
};

export default ga;
