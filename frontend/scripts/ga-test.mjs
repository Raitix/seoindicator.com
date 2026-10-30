// Test Google Analytics Data API connection
import { BetaAnalyticsDataClient } from '@google-analytics/data';

const credentialsPath = 'C:/Users/Raitis/.openclaw/secrets/google-analytics-service-account.json';
const propertyId = '489951380';

async function testGAConnection() {
  console.log('Testing Google Analytics Data API connection...\n');
  
  const analyticsDataClient = new BetaAnalyticsDataClient({
    keyFilename: credentialsPath,
  });

  try {
    // Run a simple report: get active users for last 7 days
    const [response] = await analyticsDataClient.runReport({
      property: `properties/${propertyId}`,
      dateRanges: [
        {
          startDate: '7daysAgo',
          endDate: 'yesterday',
        },
      ],
      dimensions: [
        {
          name: 'date',
        },
      ],
      metrics: [
        {
          name: 'activeUsers',
        },
        {
          name: 'screenPageViews',
        },
        {
          name: 'sessions',
        },
      ],
    });

    console.log('✅ GA API Connection: SUCCESS\n');
    console.log('Last 7 Days Report:');
    console.log('-------------------');
    
    if (response.rows && response.rows.length > 0) {
      console.log('Date       | Users | Page Views | Sessions');
      console.log('-----------|-------|------------|----------');
      response.rows.forEach(row => {
        const date = row.dimensionValues[0].value;
        const users = row.metricValues[0].value;
        const pageViews = row.metricValues[1].value;
        const sessions = row.metricValues[2].value;
        console.log(`${date} | ${users.padStart(5)} | ${pageViews.padStart(10)} | ${sessions.padStart(8)}`);
      });
    } else {
      console.log('No data found for this period.');
    }

    // Get total metrics
    const [totals] = await analyticsDataClient.runReport({
      property: `properties/${propertyId}`,
      dateRanges: [
        {
          startDate: '30daysAgo',
          endDate: 'yesterday',
        },
      ],
      metrics: [
        { name: 'totalUsers' },
        { name: 'screenPageViews' },
        { name: 'sessions' },
        { name: 'averageSessionDuration' },
        { name: 'bounceRate' },
      ],
    });

    if (totals.rows && totals.rows.length > 0) {
      console.log('\n30-Day Summary:');
      console.log('---------------');
      const metrics = totals.rows[0].metricValues;
      console.log(`Total Users: ${metrics[0].value}`);
      console.log(`Page Views: ${metrics[1].value}`);
      console.log(`Sessions: ${metrics[2].value}`);
      console.log(`Avg Session Duration: ${parseFloat(metrics[3].value).toFixed(1)}s`);
      console.log(`Bounce Rate: ${parseFloat(metrics[4].value).toFixed(1)}%`);
    }

    console.log('\n✅ GA Data API is fully operational!');
    return true;
  } catch (error) {
    console.log('❌ GA API Connection: FAILED');
    console.log('Error:', error.message);
    
    if (error.message.includes('PERMISSION_DENIED')) {
      console.log('\nTroubleshooting:');
      console.log('1. Make sure the service account email is added to GA property');
      console.log('2. Make sure it has Viewer role');
      console.log('3. Service account email: ga-data-reader@seoindicator.iam.gserviceaccount.com');
    }
    
    return false;
  }
}

testGAConnection();
