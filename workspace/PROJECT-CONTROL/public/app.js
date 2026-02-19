let allData = null;
let currentFunctionalities = [];
let activeAgents = [];

// Status to column mapping
const statusColumns = {
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
  return statusColumns[normalized] || normalized;
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
  loadData();
  loadAgents();
  // Refresh agents every 30 seconds
  setInterval(loadAgents, 30000);
});

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

// Render active agents bar
function renderAgents() {
  const container = document.getElementById('agents-list');
  
  if (activeAgents.length === 0) {
    container.innerHTML = '<span class="agents-empty">No active agents</span>';
    return;
  }
  
  container.innerHTML = activeAgents.map(agent => `
    <div class="agent-chip">
      <span class="agent-icon"></span>
      <span class="agent-name">${escapeHtml(agent.name)}</span>
      ${agent.label ? `<span class="agent-item">${escapeHtml(agent.label)}</span>` : ''}
    </div>
  `).join('');
}

// Render Kanban board
function renderBoard() {
  const board = document.getElementById('kanban-board');
  board.innerHTML = '';

  // Group by status
  const byStatus = {};
  
  for (const status of allData.statuses) {
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
      // Unknown status - put in first column or create new
      if (!byStatus['unknown']) byStatus['unknown'] = [];
      byStatus['unknown'].push(item);
    }
  }

  // Render columns
  for (const status of allData.statuses) {
    const column = createColumn(status, byStatus[status.id] || []);
    board.appendChild(column);
  }
}

// Create a column element
function createColumn(status, items) {
  const column = document.createElement('div');
  column.className = 'column';
  column.dataset.status = status.id;

  const header = document.createElement('div');
  header.className = 'column-header';
  header.innerHTML = `
    <span>${status.name}</span>
    <span class="count">${items.length}</span>
  `;
  column.appendChild(header);

  const cardsContainer = document.createElement('div');
  cardsContainer.className = 'column-cards';

  if (items.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'empty-column';
    empty.textContent = 'No items';
    cardsContainer.appendChild(empty);
  } else {
    for (const item of items) {
      const card = createCard(item);
      cardsContainer.appendChild(card);
    }
  }

  column.appendChild(cardsContainer);
  return column;
}

// Create a card element
function createCard(item) {
  const card = document.createElement('div');
  card.className = `card ${item.type}`;
  card.onclick = () => openDetail(item);

  const priorityClass = `priority-${item.priority.toLowerCase()}`;
  const riskClass = `risk-${item.risk.toLowerCase()}`;

  card.innerHTML = `
    <div class="card-header">
      <span class="card-id">${item.id}</span>
      <span class="card-type ${item.type}">${item.type.slice(0, 4)}</span>
    </div>
    <div class="card-title">${escapeHtml(item.title)}</div>
    <div class="card-meta">
      <span class="badge ${priorityClass}">${item.priority}</span>
      <span class="badge ${riskClass}">${item.risk} Risk</span>
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
  document.getElementById('modal-priority').textContent = item.priority;
  document.getElementById('modal-priority').className = `priority-badge ${item.priority.toLowerCase()}`;
  document.getElementById('modal-risk').textContent = `${item.risk} Risk`;
  document.getElementById('modal-risk').className = `risk-badge ${item.risk.toLowerCase()}`;
  
  // Render markdown content
  document.getElementById('modal-content').innerHTML = item.htmlContent;
  
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
    container.innerHTML = '<p class="empty-column">No history recorded yet.</p>';
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