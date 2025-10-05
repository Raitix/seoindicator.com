const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

// Generate timestamp in format DD-MM-YYYY_HH-MM
const now = new Date();
const timestamp = now.toLocaleDateString('en-GB').replace(/\//g, '-') + '_' + 
                 now.toLocaleTimeString('en-GB', {hour12: false}).replace(/:/g, '-');

// Create local-test-results directory if it doesn't exist
const resultsDir = './local-test-results';
if (!fs.existsSync(resultsDir)) {
  fs.mkdirSync(resultsDir, { recursive: true });
}

// Set environment variable for custom report filename
process.env.PLAYWRIGHT_HTML_REPORT_FILENAME = `index_${timestamp}.html`;

console.log(`Running tests with timestamp: ${timestamp}`);
console.log(`Report will be saved as: ${resultsDir}/index_${timestamp}.html`);

try {
  // Run playwright tests with HTML reporter
  execSync('playwright test --reporter=html', { 
    stdio: 'inherit',
    env: {
      ...process.env,
      PLAYWRIGHT_HTML_REPORT_FILENAME: `index_${timestamp}.html`
    }
  });
  
  console.log(`\nTest report saved to: ${resultsDir}/index_${timestamp}.html`);
} catch (error) {
  console.error('Test execution failed:', error.message);
  process.exit(1);
}
