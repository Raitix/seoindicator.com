const express = require('express');
const cors = require('cors');
const path = require('path');
const Parser = require('./lib/parser');

const app = express();
const PORT = 3333;

// Project path to monitor
const PROJECT_PATH = path.join(__dirname, '..', 'PROJECT-SEOINDICATOR');

// OpenClaw Gateway URL
const GATEWAY_URL = 'http://localhost:3777';

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

// API: Get active agents from OpenClaw Gateway
app.get('/api/agents', async (req, res) => {
  try {
    const response = await fetch(`${GATEWAY_URL}/api/sessions`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'list' })
    });
    
    if (!response.ok) {
      return res.json({ agents: [], error: 'Gateway not available' });
    }
    
    const data = await response.json();
    const sessions = data.sessions || [];
    
    // Filter and format active agent sessions
    // Include 'main' as 'Lead' session
    const agents = sessions
      .filter(s => s.status === 'running')
      .map(s => {
        // Map 'main' agentId to 'Lead'
        const agentId = s.agentId === 'main' ? 'lead' : s.agentId;
        const name = agentId === 'lead' ? 'Lead' : (agentId.charAt(0).toUpperCase() + agentId.slice(1));
        
        return {
          id: agentId,
          name: name,
          label: s.label || (agentId === 'lead' ? 'Coordinator' : ''),
          sessionKey: s.sessionKey,
          startedAt: s.startedAt
        };
      });
    
    res.json({ agents });
  } catch (error) {
    res.json({ agents: [], error: error.message });
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
