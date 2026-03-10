import http from 'http';
import { createProxyMiddleware } from 'http-proxy-middleware';
import { URL } from 'url';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.join(__dirname, '..', 'out');

const proxy = createProxyMiddleware({
  target: 'http://localhost:8000',
  changeOrigin: true,
  secure: false,
});

const server = http.createServer((req, res) => {
  // Proxy /api and /sanctum to backend
  if (req.url.startsWith('/api') || req.url.startsWith('/sanctum')) {
    console.log(`[PROXY] ${req.method} ${req.url} -> backend:8000`);
    proxy(req, res);
    return;
  }

  // Serve static files from 'out' directory
  let filePath = path.join(outDir, req.url === '/' ? 'index.html' : req.url);
  
  // Handle SPA routing - try .html extension for routes
  if (!fs.existsSync(filePath)) {
    const htmlPath = filePath + '.html';
    if (fs.existsSync(htmlPath)) {
      filePath = htmlPath;
    } else if (fs.existsSync(path.join(outDir, '404.html'))) {
      filePath = path.join(outDir, '404.html');
    }
  }

  const ext = path.extname(filePath);
  const contentTypes = {
    '.html': 'text/html',
    '.js': 'application/javascript',
    '.css': 'text/css',
    '.json': 'application/json',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon',
  };

  fs.readFile(filePath, (err, data) => {
    if (err) {
      res.writeHead(404, { 'Content-Type': 'text/plain' });
      res.end('Not found');
      return;
    }
    res.writeHead(200, { 
      'Content-Type': contentTypes[ext] || 'application/octet-stream',
      'Cache-Control': 'no-store',
    });
    res.end(data);
  });
});

const PORT = 3000;
server.listen(PORT, '127.0.0.1', () => {
  console.log(`Static export server running at http://127.0.0.1:${PORT}`);
  console.log(`Proxying /api and /sanctum to http://localhost:8000`);
});
