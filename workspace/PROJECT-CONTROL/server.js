const express = require('express');
const cors = require('cors');
const path = require('path');
const fs = require('fs');
const Parser = require('./lib/parser');

const app = express();
const PORT = 3333;

// Project path to monitor
const PROJECT_PATH = path.join(__dirname, '..', 'PROJECT-SEOINDICATOR');
const ACTIVE_AGENTS_FILE = 'C:\\Users\\Raitis\\.openclaw\\logs\\active-agents.json';
const ORCHESTRATOR_LOG = 'C:\\Users\\Raitis\\.openclaw\\logs\\orchestrator.log';

app.use(cors());
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

// Initialize parser
const parser = new Parser(PROJECT_PATH);

// API: Get all functionalities (features, specs, tasks)
app.get('/api/functionalities', async (req, res) => {
  try {
    const data = await parser.getAll();
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// API: Get single functionality by ID
app.get('/api/functionalities/:type/:id', async (req, res) => {
  try {
    const { type, id } = req.params;
    const item = await parser.getById(type, id);
    if (!item) {
      return res.status(404).json({ error: 'Not found' });
    }
    res.json(item);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// API: Get statuses configuration
app.get('/api/statuses', (req, res) => {
  res.json(parser.getStatuses());
});

// API: Get active agents from file
app.get('/api/agents', (req, res) => {
  try {
    if (fs.existsSync(ACTIVE_AGENTS_FILE)) {
      const content = fs.readFileSync(ACTIVE_AGENTS_FILE, 'utf-8');
      const data = JSON.parse(content);
      res.json({ agents: data });
    } else {
      // Default: show Lead as active if file doesn't exist
      res.json({ 
        agents: [{
          id: 'lead',
          name: 'Lead',
          label: 'Coordinator',
          status: 'running'
        }]
      });
    }
  } catch (error) {
    res.json({ agents: [], error: error.message });
  }
});

// API: Get orchestrator logs (last N lines)
app.get('/api/logs', (req, res) => {
  try {
    const lines = parseInt(req.query.lines) || 100;
    
    if (!fs.existsSync(ORCHESTRATOR_LOG)) {
      return res.json({ logs: [], error: 'Log file not found' });
    }
    
    const content = fs.readFileSync(ORCHESTRATOR_LOG, 'utf-8');
    const allLines = content.split('\n').filter(line => line.trim());
    const lastLines = allLines.slice(-lines);
    
    res.json({ 
      logs: lastLines,
      totalLines: allLines.length,
      showing: lastLines.length
    });
  } catch (error) {
    res.json({ logs: [], error: error.message });
  }
});

// Serve main page
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.listen(PORT, () => {
  console.log(`\n  🎛️  Workflow Control Center`);
  console.log(`  ─────────────────────────────`);
  console.log(`  Running at: http://localhost:${PORT}`);
  console.log(`  Monitoring: ${PROJECT_PATH}\n`);
});
