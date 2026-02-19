/**
 * Google Analytics Data API Client
 * 
 * Usage:
 *   node scripts/ga-analytics.mjs <command> [options]
 * 
 * Commands:
 *   summary [days]     - Get summary metrics for last N days (default: 30)
 *   daily [days]       - Get daily breakdown for last N days (default: 7)
 *   pages [limit]      - Get top pages by traffic (default: 10)
 *   sources [limit]    - Get top traffic sources (default: 10)
 *   devices            - Get device breakdown
 *   realtime           - Get current active users
 *   custom <json>      - Run custom report (JSON query)
 */

import { BetaAnalyticsDataClient } from '@google-analytics/data';

const credentialsPath = 'C:/Users/Raitis/.openclaw/secrets/google-analytics-service-account.json';
const propertyId = '489951380';

const analyticsDataClient = new BetaAnalyticsDataClient({
  keyFilename: credentialsPath,
});

// Helper to format numbers
const fmt = (n) => n?.toString().padStart(8) || 'N/A';
const pct = (n) => n ? `${parseFloat(n).toFixed(1)}%` : 'N/A';
const dur = (n) => n ? `${parseFloat(n).toFixed(1)}s` : 'N/A';

// Get summary metrics
async function getSummary(days = 30) {
  const [response] = await analyticsDataClient.runReport({
    property: `properties/${propertyId}`,
    dateRanges: [{ startDate: `${days}daysAgo`, endDate: 'yesterday' }],
    metrics: [
      { name: 'totalUsers' },
      { name: 'newUsers' },
      { name: 'screenPageViews' },
      { name: 'sessions' },
      { name: 'averageSessionDuration' },
      { name: 'bounceRate' },
      { name: 'engagementRate' },
    ],
  });

  const m = response.rows?.[0]?.metricValues || [];
  return {
    period: `Last ${days} days`,
    totalUsers: parseInt(m[0]?.value) || 0,
    newUsers: parseInt(m[1]?.value) || 0,
    pageViews: parseInt(m[2]?.value) || 0,
    sessions: parseInt(m[3]?.value) || 0,
    avgSessionDuration: parseFloat(m[4]?.value) || 0,
    bounceRate: parseFloat(m[5]?.value) || 0,
    engagementRate: parseFloat(m[6]?.value) || 0,
  };
}

// Get daily breakdown
async function getDaily(days = 7) {
  const [response] = await analyticsDataClient.runReport({
    property: `properties/${propertyId}`,
    dateRanges: [{ startDate: `${days}daysAgo`, endDate: 'yesterday' }],
    dimensions: [{ name: 'date' }],
    metrics: [
      { name: 'activeUsers' },
      { name: 'screenPageViews' },
      { name: 'sessions' },
    ],
    orderBys: [{ dimension: { dimensionName: 'date' }, desc: false }],
  });

  return response.rows?.map(row => ({
    date: row.dimensionValues[0].value,
    users: parseInt(row.metricValues[0].value) || 0,
    pageViews: parseInt(row.metricValues[1].value) || 0,
    sessions: parseInt(row.metricValues[2].value) || 0,
  })) || [];
}

// Get top pages
async function getTopPages(limit = 10) {
  const [response] = await analyticsDataClient.runReport({
    property: `properties/${propertyId}`,
    dateRanges: [{ startDate: '30daysAgo', endDate: 'yesterday' }],
    dimensions: [{ name: 'pagePath' }],
    metrics: [
      { name: 'screenPageViews' },
      { name: 'activeUsers' },
      { name: 'averageSessionDuration' },
      { name: 'bounceRate' },
    ],
    orderBys: [{ metric: { metricName: 'screenPageViews' }, desc: true }],
    limit: limit,
  });

  return response.rows?.map(row => ({
    path: row.dimensionValues[0].value,
    views: parseInt(row.metricValues[0].value) || 0,
    users: parseInt(row.metricValues[1].value) || 0,
    avgDuration: parseFloat(row.metricValues[2].value) || 0,
    bounceRate: parseFloat(row.metricValues[3].value) || 0,
  })) || [];
}

// Get traffic sources
async function getTopSources(limit = 10) {
  const [response] = await analyticsDataClient.runReport({
    property: `properties/${propertyId}`,
    dateRanges: [{ startDate: '30daysAgo', endDate: 'yesterday' }],
    dimensions: [{ name: 'sessionDefaultChannelGroup' }],
    metrics: [
      { name: 'sessions' },
      { name: 'totalUsers' },
      { name: 'bounceRate' },
    ],
    orderBys: [{ metric: { metricName: 'sessions' }, desc: true }],
    limit: limit,
  });

  return response.rows?.map(row => ({
    source: row.dimensionValues[0].value,
    sessions: parseInt(row.metricValues[0].value) || 0,
    users: parseInt(row.metricValues[1].value) || 0,
    bounceRate: parseFloat(row.metricValues[2].value) || 0,
  })) || [];
}

// Get device breakdown
async function getDevices() {
  const [response] = await analyticsDataClient.runReport({
    property: `properties/${propertyId}`,
    dateRanges: [{ startDate: '30daysAgo', endDate: 'yesterday' }],
    dimensions: [{ name: 'deviceCategory' }],
    metrics: [
      { name: 'sessions' },
      { name: 'totalUsers' },
    ],
    orderBys: [{ metric: { metricName: 'sessions' }, desc: true }],
  });

  return response.rows?.map(row => ({
    device: row.dimensionValues[0].value,
    sessions: parseInt(row.metricValues[0].value) || 0,
    users: parseInt(row.metricValues[1].value) || 0,
  })) || [];
}

// Get real-time active users
async function getRealtime() {
  const [response] = await analyticsDataClient.runRealtimeReport({
    property: `properties/${propertyId}`,
    metrics: [{ name: 'activeUsers' }],
  });

  return {
    activeUsers: parseInt(response.rows?.[0]?.metricValues?.[0]?.value) || 0,
    timestamp: new Date().toISOString(),
  };
}

// Custom report
async function runCustomReport(queryJson) {
  const query = JSON.parse(queryJson);
  query.property = `properties/${propertyId}`;
  const [response] = await analyticsDataClient.runReport(query);
  return response;
}

// CLI
const [command, ...args] = process.argv.slice(2);

async function main() {
  try {
    let result;

    switch (command) {
      case 'summary':
        result = await getSummary(parseInt(args[0]) || 30);
        console.log(JSON.stringify(result, null, 2));
        break;

      case 'daily':
        result = await getDaily(parseInt(args[0]) || 7);
        console.log(JSON.stringify(result, null, 2));
        break;

      case 'pages':
        result = await getTopPages(parseInt(args[0]) || 10);
        console.log(JSON.stringify(result, null, 2));
        break;

      case 'sources':
        result = await getTopSources(parseInt(args[0]) || 10);
        console.log(JSON.stringify(result, null, 2));
        break;

      case 'devices':
        result = await getDevices();
        console.log(JSON.stringify(result, null, 2));
        break;

      case 'realtime':
        result = await getRealtime();
        console.log(JSON.stringify(result, null, 2));
        break;

      case 'custom':
        result = await runCustomReport(args[0]);
        console.log(JSON.stringify(result, null, 2));
        break;

      default:
        console.log(`
Google Analytics Data API Client

Usage:
  node ga-analytics.mjs <command> [options]

Commands:
  summary [days]     Get summary metrics (default: 30 days)
  daily [days]       Get daily breakdown (default: 7 days)
  pages [limit]      Get top pages (default: 10)
  sources [limit]    Get top traffic sources (default: 10)
  devices            Get device breakdown
  realtime           Get current active users
  custom <json>      Run custom report (JSON query)

Examples:
  node ga-analytics.mjs summary 7
  node ga-analytics.mjs pages 20
  node ga-analytics.mjs realtime
        `);
    }
  } catch (error) {
    console.error('Error:', error.message);
    process.exit(1);
  }
}

main();
