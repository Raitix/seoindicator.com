let allData = null;
let currentFunctionalities = [];
let activeAgents = [];

// Status to workflow order (top to bottom)
const statusOrder = [
  { id: 'new-feature', name: 'New Feature', agent: 'Architect' },
  { id: 'specification-in-progress', name: 'Spec In Progress', agent: 'Architect' },
  { id: 'to-break-into-tasks', name: 'To Break Into Tasks', agent: 'SpecSystemAnalyst' },
  { id: 'breaking-into-tasks', name: 'Breaking Into Tasks', agent: 'SpecSystemAnalyst' },
  { id: 'to-prioritize', name: 'To Prioritize', agent: 'ProjectManager' },
  { id: 'prioritization-in-progress', name: 'Prioritizing', agent: 'ProjectManager' },
  { id: 'to-write-tests', name: 'To Write Tests', agent: 'PreTester' },
  { id: 'tests-coverage-in-progress', name: 'Writing Tests', agent: 'PreTester' },
  { id: 'to-develop', name: 'To Develop', agent: 'Developer' },
  { id: 'in-development', name: 'In Development', agent: 'Developer' },
  { id: 'to-test-locally', name: 'To Test Locally', agent: 'LocalTester' },
  { id: 'testing-locally', name: 'Testing Locally', agent: 'LocalTester' },
  { id: 'to-deploy', name: 'To Deploy', agent: 'DevOps' },
  { id: 'in-deployment', name: 'Deploying', agent: 'DevOps' },
  { id: 'to-test-prod', name: 'To Test Prod', agent: 'ProdTester' },
  { id: 'testing-prod', name: 'Testing Prod', agent: 'ProdTester' },
  { id: 'done', name: 'Done', agent: 'DocSystemAnalyst' },
  { id: 'cancelled', name: 'Cancelled', agent: '-' }
];

// Status to row ID mapping
const statusMapping = {
  'new feature': 'new-feature',
  'specification in progress': 'specification-in-progress',
  'to break into tasks': 'to-break-into-tasks',
  'breaking into tasks': 'breaking-into-tasks',
  'to prioritize': 'to-prioritize',
  'prioritization in progress': 'prioritization-in-progress',
  'to write tests': 'to-write-tests',
  'tests coverage in progress': 'tests-coverage-in-progress',
  'to develop': 'to-develop',
  'in development': 'in-development',
  'to test locally': 'to-test-locally',
  'testing locally': 'testing-locally',
  'to deploy': 'to-deploy',
  'in deployment': 'in-deployment',
  'to test prod': 'to-test-prod',
  'testing prod': 'testing-prod',
  'done': 'done',
  'cancelled': 'cancelled'
};

// Normalize status string
function normalizeStatus(status) {
  if (!status) return 'unknown';
  const normalized = status.toLowerCase().trim().replace(/`/g, '');
  return statusMapping[normalized] || normalized;
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
  loadData();
  loadAgents();
  loadLogs();
  // Refresh agents every 30 seconds
  setInterval(loadAgents, 30000);
  // Auto-refresh logs every 10 seconds when on logs tab
  setInterval(() => {
    if (document.getElementById('tab-logs').classList.contains('active')) {
      loadLogs();
    }
  }, 10000);
});

// Refresh all data
function refreshAll() {
  loadData();
  loadAgents();
  loadLogs();
}

// Switch main tabs (Board/Logs)
function switchMainTab(tabName) {
  const buttons = document.querySelectorAll('.main-tab');
  const contents = document.querySelectorAll('.main-content');
  
  buttons.forEach(btn => {
    btn.classList.toggle('active', btn.dataset.tab === tabName);
  });
  
  contents.forEach(content => {
    content.classList.toggle('active', content.id === `tab-${tabName}`);
  });
  
  // Auto-scroll logs when switching to logs tab
  if (tabName === 'logs') {
    setTimeout(() => {
      const container = document.getElementById('logs-container');
      container.scrollTop = container.scrollHeight;
    }, 100);
  }
}

// Load all data from API
async function loadData() {
  try {
    const response = await fetch('/api/functionalities');
    allData = await response.json();
    currentFunctionalities = allData.all || [];
    renderBoard();
  } catch (error) {
    console.error('Failed to load data:', error);
  }
}

// Load active agents from API
async function loadAgents() {
  try {
    const response = await fetch('/api/agents');
    const data = await response.json();
    activeAgents = data.agents || [];
    renderAgents();
  } catch (error) {
    console.error('Failed to load agents:', error);
    renderAgents();
  }
}

// Render active agents in header
function renderAgents() {
  const container = document.getElementById('agents-list');
  
  if (activeAgents.length === 0) {
    container.innerHTML = '<span class="agents-empty">None</span>';
    return;
  }
  
  container.innerHTML = activeAgents.map(agent => `
    <div class="agent-chip">
      <span class="agent-icon"></span>
      <span class="agent-name">${escapeHtml(agent.name)}</span>
      ${agent.label && agent.label !== agent.name ? `<span class="agent-item">${escapeHtml(agent.label)}</span>` : ''}
    </div>
  `).join('');
}

// Render vertical workflow board
function renderBoard() {
  const board = document.getElementById('workflow-board');
  board.innerHTML = '';

  // Group by status
  const byStatus = {};
  
  // Initialize all known statuses
  for (const status of statusOrder) {
    byStatus[status.id] = [];
  }
  
  // Sort functionalities by priority then risk
  const sorted = [...currentFunctionalities].sort((a, b) => {
    const prioA = a.sortOrder || 2;
    const prioB = b.sortOrder || 2;
    if (prioA !== prioB) return prioA - prioB;
    
    const riskA = a.riskSortOrder || 3;
    const riskB = b.riskSortOrder || 3;
    return riskA - riskB;
  });

  // Group items
  for (const item of sorted) {
    const statusKey = normalizeStatus(item.status);
    if (byStatus[statusKey]) {
      byStatus[statusKey].push(item);
    } else {
      // Unknown status - add to end
      if (!byStatus['unknown']) byStatus['unknown'] = [];
      byStatus['unknown'].push(item);
    }
  }

  // Render rows (known statuses first)
  for (const status of statusOrder) {
    const items = byStatus[status.id] || [];
    // Only show rows with items or important statuses
    if (items.length === 0 && !['done', 'cancelled'].includes(status.id)) continue;
    if (items.length === 0) continue; // Skip empty rows
    
    const row = createRow(status, items);
    board.appendChild(row);
  }
  
  // Render unknown statuses at the end
  if (byStatus['unknown'] && byStatus['unknown'].length > 0) {
    const row = createRow({ id: 'unknown', name: 'Unknown', agent: '-' }, byStatus['unknown']);
    board.appendChild(row);
  }
}

// Create a row element
function createRow(status, items) {
  const row = document.createElement('div');
  row.className = 'workflow-row';
  row.dataset.status = status.id;

  // Header
  const header = document.createElement('div');
  header.className = 'row-header';
  header.innerHTML = `
    <span class="row-status">${escapeHtml(status.name)}</span>
    <span class="row-count">${items.length} item${items.length !== 1 ? 's' : ''} · ${status.agent}</span>
  `;
  row.appendChild(header);

  // Cards container
  const cardsContainer = document.createElement('div');
  cardsContainer.className = 'row-cards';

  if (items.length === 0) {
    cardsContainer.innerHTML = '<span class="row-empty">No items</span>';
  } else {
    for (const item of items) {
      const card = createCard(item);
      cardsContainer.appendChild(card);
    }
  }

  row.appendChild(cardsContainer);
  return row;
}

// Create a card element
function createCard(item) {
  const card = document.createElement('div');
  card.className = `card ${item.type}`;
  card.onclick = () => openDetail(item);

  const priorityClass = `priority-${(item.priority || 'medium').toLowerCase()}`;
  const riskClass = `risk-${(item.risk || 'low').toLowerCase()}`;

  card.innerHTML = `
    <div class="card-header">
      <span class="card-id">${item.id}</span>
      <span class="card-type ${item.type}">${item.type.slice(0, 4)}</span>
    </div>
    <div class="card-title">${escapeHtml(item.title)}</div>
    <div class="card-meta">
      <span class="badge ${priorityClass}">${item.priority || 'Medium'}</span>
      <span class="badge ${riskClass}">${item.risk || 'Low'}</span>
    </div>
  `;

  return card;
}

// Open detail modal
async function openDetail(item) {
  const modal = document.getElementById('detail-modal');
  
  document.getElementById('modal-type').className = `type-badge ${item.type}`;
  document.getElementById('modal-type').textContent = item.type;
  document.getElementById('modal-title').textContent = item.title;
  document.getElementById('modal-status').textContent = item.status;
  document.getElementById('modal-priority').textContent = item.priority || 'Medium';
  document.getElementById('modal-priority').className = `priority-badge ${(item.priority || 'medium').toLowerCase()}`;
  document.getElementById('modal-risk').textContent = `${item.risk || 'Low'} Risk`;
  document.getElementById('modal-risk').className = `risk-badge ${(item.risk || 'low').toLowerCase()}`;
  
  // Render markdown content
  document.getElementById('modal-content').innerHTML = item.htmlContent || '<p>No content</p>';
  
  // Render history
  renderHistory(item.comments);
  
  // Reset to details tab
  switchTab('details');
  
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
}

// Render history timeline
function renderHistory(comments) {
  const container = document.getElementById('history-timeline');
  
  if (!comments || comments.length === 0) {
    container.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.8rem;">No history recorded yet.</p>';
    return;
  }

  container.innerHTML = comments.map(c => `
    <div class="history-item">
      <div class="history-header">
        <span class="history-agent">${escapeHtml(c.agent)}</span>
        <span class="history-timestamp">${escapeHtml(c.timestamp)}</span>
      </div>
      <div class="history-content">${escapeHtml(c.content)}</div>
    </div>
  `).join('');
}

// Close modal
function closeModal() {
  const modal = document.getElementById('detail-modal');
  modal.classList.remove('active');
  document.body.style.overflow = '';
}

// Switch tabs in modal
function switchTab(tabName) {
  const buttons = document.querySelectorAll('.tab-btn');
  const contents = document.querySelectorAll('.tab-content');
  
  buttons.forEach(btn => {
    btn.classList.toggle('active', btn.dataset.tab === tabName);
  });
  
  contents.forEach(content => {
    content.classList.toggle('active', content.id === `tab-${tabName}`);
  });
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Close modal on outside click
document.getElementById('detail-modal').addEventListener('click', (e) => {
  if (e.target.id === 'detail-modal') {
    closeModal();
  }
});

// Close modal on Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeModal();
  }
});

// Load logs from API
async function loadLogs() {
  try {
    const lines = document.getElementById('log-lines').value;
    const response = await fetch(`/api/logs?lines=${lines}`);
    const data = await response.json();
    
    const container = document.getElementById('logs-content');
    const logCount = document.getElementById('log-count');
    
    if (data.error && !data.logs.length) {
      container.innerHTML = `<div class="logs-error">Error: ${escapeHtml(data.error)}</div>`;
      return;
    }
    
    // Update badge
    logCount.textContent = data.showing;
    
    // Format log lines with colors
    container.innerHTML = data.logs.map(line => {
      let className = 'log-line';
      if (line.includes('[ERROR]')) className += ' log-error';
      else if (line.includes('[WARN]')) className += ' log-warn';
      else if (line.includes('[INFO]')) className += ' log-info';
      else if (line.includes('[DEBUG]')) className += ' log-debug';
      
      return `<div class="${className}">${escapeHtml(line)}</div>`;
    }).join('');
    
    // Auto-scroll if enabled
    if (document.getElementById('auto-scroll').checked) {
      const logsContainer = document.getElementById('logs-container');
      logsContainer.scrollTop = logsContainer.scrollHeight;
    }
  } catch (error) {
    console.error('Failed to load logs:', error);
    document.getElementById('logs-content').innerHTML = `<div class="logs-error">Failed to load logs: ${escapeHtml(error.message)}</div>`;
  }
}
