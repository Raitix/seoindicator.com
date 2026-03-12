const { guides, indexGuide, generateHTML } = require('./generate-og-images.js');
const fs = require('fs');
const path = require('path');
const http = require('http');

const OUTPUT_DIR = 'C:\\Users\\Raitis\\.openclaw\\workspace\\PROJECT-SEOINDICATOR\\frontend\\public\\guides\\og';

// Create HTTP server to serve HTML
const server = http.createServer((req, res) => {
  const url = new URL(req.url, `http://localhost${req.url.startsWith('/') ? '' : ''}${req.url}`);
  const guideSlug = url.pathname.slice(1);
  
  let guide;
  if (guideSlug === 'index') {
    guide = indexGuide;
  } else {
    guide = guides.find(g => g.slug === guideSlug);
  }
  
  if (guide) {
    res.writeHead(200, { 'Content-Type': 'text/html' });
    res.end(generateHTML(guide));
  } else {
    res.writeHead(404);
    res.end('Not found');
  }
});

const PORT = 8766;
server.listen(PORT);

console.log(`Server running on http://localhost:${PORT}`);
console.log('Available guides:');
console.log('- index');
guides.forEach(g => console.log(`- ${g.slug}`));

// Keep server running
process.on('SIGINT', () => {
  server.close();
  process.exit();
});
