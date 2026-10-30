const http = require('http');
const fs = require('fs');
const path = require('path');
const httpProxy = require('http-proxy');

const proxy = httpProxy.createProxyServer({});
const OUT_DIR = path.join(__dirname, 'out');
const PORT = 3000;
const BACKEND = 'http://127.0.0.1:8000';

const MIME_TYPES = {
  '.html': 'text/html',
  '.js': 'application/javascript',
  '.css': 'text/css',
  '.json': 'application/json',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.gif': 'image/gif',
  '.svg': 'image/svg+xml',
  '.ico': 'image/x-icon',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
  '.ttf': 'font/ttf',
};

const server = http.createServer((req, res) => {
  // Proxy /api and /sanctum to backend
  if (req.url.startsWith('/api/') || req.url.startsWith('/sanctum/')) {
    proxy.web(req, res, { target: BACKEND }, (e) => {
      console.error('Proxy error:', e.message);
      res.writeHead(502);
      res.end('Bad Gateway');
    });
    return;
  }

  // Serve static files
  let filePath = path.join(OUT_DIR, req.url === '/' ? 'index.html' : req.url);
  
  // Try .html extension for SPA routes
  if (!fs.existsSync(filePath) && !path.extname(filePath)) {
    const htmlPath = filePath + '.html';
    if (fs.existsSync(htmlPath)) {
      filePath = htmlPath;
    } else {
      // Fallback to index.html for client-side routing
      filePath = path.join(OUT_DIR, 'index.html');
    }
  }

  const ext = path.extname(filePath);
  const contentType = MIME_TYPES[ext] || 'application/octet-stream';

  fs.readFile(filePath, (err, data) => {
    if (err) {
      if (err.code === 'ENOENT') {
        res.writeHead(404);
        res.end('Not Found');
      } else {
        res.writeHead(500);
        res.end('Internal Server Error');
      }
      return;
    }
    res.writeHead(200, { 'Content-Type': contentType });
    res.end(data);
  });
});

server.listen(PORT, () => {
  console.log(`Static server with proxy running at http://127.0.0.1:${PORT}`);
  console.log(`Proxying /api/* and /sanctum/* to ${BACKEND}`);
});
