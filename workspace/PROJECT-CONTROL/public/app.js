let allData = null;
let currentFunctionalities = [];
let activeAgents = [];
let workflowState = { enabled: true };
let innovatorState = { enabled: true };
let eventSource = null;

// Status to workflow order (top to bottom)
const statusOrder = [
  { id: 'new-feature', name: 'New Feature', agent: 'Architect', coreType: 'feature' },
  { id: 'specification-in-progress', name: 'Spec In Progress', agent: 'Architect', coreType: 'feature' },
  { id: 'to-break-into-tasks', name: 'To Break Into Tasks', agent: 'SpecSystemAnalyst', coreType: 'specification' },
  { id: 'breaking-into-tasks', name: 'Breaking Into Tasks', agent: 'SpecSystemAnalyst', coreType: 'specification' },
  { id: 'to-prioritize', name: 'To Prioritize', agent: 'ProjectManager', coreType: 'task' },
  { id: 'prioritization-in-progress', name: 'Prioritizing', agent: 'ProjectManager', coreType: 'task' },
  { id: 'to-write-tests', name: 'To Write Tests', agent: 'PreTester', coreType: 'task' },
  { id: 'tests-coverage-in-progress', name: 'Writing Tests', agent: 'PreTester', coreType: 'task' },
  { id: 'to-develop', name: 'To Develop', agent: 'Developer', coreType: 'task' },
  { id: 'in-development', name: 'In Development', agent: 'Developer', coreType: 'task' },
  { id: 'to-test-locally', name: 'To Test Locally', agent: 'LocalTester', coreType: 'task' },
  { id: 'testing-locally', name: 'Testing Locally', agent: 'LocalTester', coreType: 'task' },
  { id: 'to-deploy', name: 'To Deploy', agent: 'DevOps', coreType: 'task' },
  { id: 'in-deployment', name: 'Deploying', agent: 'DevOps', coreType: 'task' },
  { id: 'to-test-prod', name: 'To Test Prod', agent: 'ProdTester', coreType: 'task' },
  { id: 'testing-prod', name: 'Testing Prod', agent: 'ProdTester', coreType: 'task' },
  { id: 'done', name: 'Done', agent: 'DocSystemAnalyst', coreType: 'task' },
  { id: 'cancelled', name: 'Cancelled', agent: '-', coreType: 'task' }
];

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

function normalizeStatus(status) {
  if (!status) return 'unknown';
  const normalized = status.toLowerCase().trim().replace(/`/g, '');
  return statusMapping[normalized] || normalized;
}

document.addEventListener('DOMContentLoaded', () => {
  loadData();
  loadAgents();
  loadLogs();
  loadWorkflowState();
  connectSSE();
});

// Connect to SSE for real-time updates
function connectSSE() {
  if (eventSource) {
    eventSource.close();
  }
  
  eventSource = new EventSource('/api/events');
  
  eventSource.addEventListener('agents', (e) => {
    try {
      const data = JSON.parse(e.data);
      activeAgents = data.agents || [];
      renderAgents();
    } catch (err) {
      console.error('Failed to parse agents update:', err);
    }
  });
  
  eventSource.addEventListener('logs', (e) => {
    try {
      const data = JSON.parse(e.data);
      appendLogs(data.lines);
    } catch (err) {
      console.error('Failed to parse logs update:', err);
    }
  });
  
  eventSource.addEventListener('workflow', (e) => {
    try {
      const data = JSON.parse(e.data);
      if (data.workflow) {
        workflowState = data.workflow;
        renderWorkflowStatus();
      }
    } catch (err) {
      console.error('Failed to parse workflow update:', err);
    }
  });
  
  eventSource.addEventListener('innovator', (e) => {
    try {
      const data = JSON.parse(e.data);
      if (data.innovator) {
        innovatorState = data.innovator;
        renderInnovatorStatus();
      }
    } catch (err) {
      console.error('Failed to parse innovator update:', err);
    }
  });
  
  eventSource.onerror = () => {
    console.log('SSE disconnected, reconnecting in 3s...');
    setTimeout(connectSSE, 3000);
  };
}

// Append new log lines to the display
function appendLogs(newLines) {
  if (!newLines || newLines.length === 0) return;
  
  const container = document.getElementById('logs-content');
  const logCount = document.getElementById('log-count');
  
  for (const line of newLines) {
    if (!line.trim()) continue;
    // Filter out diagnostic noise
    if (line.includes('[diagnostic]') && line.includes('stuck session')) continue;
    if (line.includes('[diagnostic]') && line.includes('queueDepth')) continue;
    
    let className = 'log-line';
    if (line.includes('[ERROR]')) className += ' log-error';
    else if (line.includes('[WARN]')) className += ' log-warn';
    else if (line.includes('[INFO]')) className += ' log-info';
    else if (line.includes('[DEBUG]')) className += ' log-debug';
    
    const div = document.createElement('div');
    div.className = className;
    div.textContent = line;
    container.appendChild(div);
  }
  
  // Update count
  const totalLines = container.querySelectorAll('.log-line').length;
  logCount.textContent = totalLines;
  
  // Auto-scroll if enabled
  if (document.getElementById('auto-scroll').checked) {
    const logsContainer = document.getElementById('logs-container');
    logsContainer.scrollTop = logsContainer.scrollHeight;
  }
}

function refreshAll() {
  loadData();
  loadAgents();
  loadLogs();
}

function switchMainTab(tabName) {
  document.querySelectorAll('.main-tab').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.tab === tabName);
  });
  document.querySelectorAll('.main-content').forEach(content => {
    content.classList.toggle('active', content.id === `tab-${tabName}`);
  });
  
  if (tabName === 'logs') {
    setTimeout(() => {
      document.getElementById('logs-container').scrollTop = document.getElementById('logs-container').scrollHeight;
    }, 100);
  }
}

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

async function loadWorkflowState() {
  try {
    const response = await fetch('/api/workflow');
    const data = await response.json();
    workflowState = data.workflow || { enabled: true };
    innovatorState = data.innovator || { enabled: true };
    renderWorkflowStatus();
    renderInnovatorStatus();
  } catch (error) {
    console.error('Failed to load workflow state:', error);
    renderWorkflowStatus();
    renderInnovatorStatus();
  }
}

function renderWorkflowStatus() {
  const statusEl = document.getElementById('workflow-status');
  if (!statusEl) return;
  
  if (workflowState.enabled) {
    statusEl.className = 'status-indicator active';
    statusEl.textContent = 'ACTIVE';
  } else {
    statusEl.className = 'status-indicator paused';
    statusEl.textContent = 'PAUSED';
  }
}

function renderInnovatorStatus() {
  const statusEl = document.getElementById('innovator-status');
  if (!statusEl) return;
  
  if (innovatorState.enabled) {
    statusEl.className = 'status-indicator active';
    statusEl.textContent = 'ACTIVE';
  } else {
    statusEl.className = 'status-indicator paused';
    statusEl.textContent = 'PAUSED';
  }
}

async function toggleWorkflow() {
  const btn = document.getElementById('workflow-toggle');
  btn.disabled = true;
  
  try {
    const newState = !workflowState.enabled;
    const response = await fetch('/api/workflow', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        enabled: newState,
        pausedBy: newState ? null : 'UI',
        pauseReason: newState ? null : 'Toggled via Workflow Control UI'
      })
    });
    
    const data = await response.json();
    workflowState = { enabled: data.enabled, pausedAt: data.pausedAt };
    renderWorkflowStatus();
  } catch (error) {
    console.error('Failed to toggle workflow:', error);
    alert('Failed to update workflow state: ' + error.message);
  }
  
  btn.disabled = false;
}

async function toggleInnovator() {
  const btn = document.getElementById('innovator-toggle');
  btn.disabled = true;
  
  try {
    const newState = !innovatorState.enabled;
    const response = await fetch('/api/innovator', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        enabled: newState,
        pausedBy: newState ? null : 'UI',
        pauseReason: newState ? null : 'Toggled via Workflow Control UI'
      })
    });
    
    const data = await response.json();
    innovatorState = { enabled: data.enabled, pausedAt: data.pausedAt };
    renderInnovatorStatus();
  } catch (error) {
    console.error('Failed to toggle innovator:', error);
    alert('Failed to update innovator state: ' + error.message);
  }
  
  btn.disabled = false;
}

function renderBoard() {
  const board = document.getElementById('workflow-board');
  board.innerHTML = '';

  const combined = allData.combined || [];
  
  if (combined.length === 0) {
    board.innerHTML = '<div class="row-empty">No items found</div>';
    return;
  }

  // Group by status
  const byStatus = {};
  for (const status of statusOrder) {
    byStatus[status.id] = [];
  }
  
  for (const item of combined) {
    const statusKey = normalizeStatus(item.status);
    if (byStatus[statusKey]) {
      byStatus[statusKey].push(item);
    } else {
      if (!byStatus['unknown']) byStatus['unknown'] = [];
      byStatus['unknown'].push(item);
    }
  }

  // Render ALL rows (even empty ones)
  for (const status of statusOrder) {
    const items = byStatus[status.id] || [];
    const row = createRow(status, items);
    board.appendChild(row);
  }
}

function createRow(status, items) {
  const row = document.createElement('div');
  row.className = 'workflow-row' + (items.length === 0 ? ' empty' : '');
  row.dataset.status = status.id;

  const header = document.createElement('div');
  header.className = 'row-header';
  header.innerHTML = `
    <span class="row-status">${escapeHtml(status.name)}</span>
    <span class="row-count">${items.length} item${items.length !== 1 ? 's' : ''} · ${status.agent}</span>
  `;
  row.appendChild(header);

  const cardsContainer = document.createElement('div');
  cardsContainer.className = 'row-cards';

  if (items.length === 0) {
    cardsContainer.innerHTML = '<span class="row-empty">No items</span>';
  } else {
    for (const item of items) {
      const card = createHierarchicalCard(item, status);
      cardsContainer.appendChild(card);
    }
  }

  row.appendChild(cardsContainer);
  return row;
}

// Create hierarchical card with prepended parents showing titles
function createHierarchicalCard(item, status) {
  const card = document.createElement('div');
  card.className = 'card hierarchical';
  
  const priorityClass = `priority-${(item.priority || 'medium').toLowerCase()}`;
  const riskClass = `risk-${(item.risk || 'low').toLowerCase()}`;

  // Build hierarchical chain based on coreType
  let prependHtml = '';
  
  if (item.coreType === 'task' && item.specification && item.feature) {
    // Task with spec and feature: [Feature] [Spec] Task
    prependHtml = `
      <div class="card-prepend">
        <div class="prepend-item">
          <span class="prepend-id feature">${item.feature.id}</span>
          <span class="prepend-title" title="${escapeHtml(item.feature.title)}">${escapeHtml(item.feature.title)}</span>
        </div>
        <span class="prepend-arrow">↓</span>
        <div class="prepend-item">
          <span class="prepend-id spec">${item.specification.id}</span>
          <span class="prepend-title" title="${escapeHtml(item.specification.title)}">${escapeHtml(item.specification.title)}</span>
        </div>
        <span class="prepend-arrow">↓</span>
      </div>
    `;
  } else if (item.coreType === 'specification' && item.feature) {
    // Spec with feature: [Feature] Spec
    prependHtml = `
      <div class="card-prepend">
        <div class="prepend-item">
          <span class="prepend-id feature">${item.feature.id}</span>
          <span class="prepend-title" title="${escapeHtml(item.feature.title)}">${escapeHtml(item.feature.title)}</span>
        </div>
        <span class="prepend-arrow">↓</span>
      </div>
    `;
  }

  // Core card content
  const coreTypeClass = item.coreType || 'feature';
  
  card.innerHTML = `
    ${prependHtml}
    <div class="card-core ${coreTypeClass}">
      <div class="card-header">
        <span class="card-id">${escapeHtml(item.id)}</span>
        <span class="card-type ${coreTypeClass}">${coreTypeClass.slice(0, 4)}</span>
      </div>
      <div class="card-title">${escapeHtml(item.title)}</div>
      <div class="card-meta">
        <span class="badge ${priorityClass}">P: ${item.priority || 'Medium'}</span>
        <span class="badge ${riskClass}">R: ${item.risk || 'Low'}</span>
      </div>
    </div>
  `;

  card.onclick = () => openDetail(item);
  return card;
}

// Open detail modal with parent context
async function openDetail(item) {
  const modal = document.getElementById('detail-modal');
  const core = item.core || item;
  
  document.getElementById('modal-type').className = `type-badge ${core.type}`;
  document.getElementById('modal-type').textContent = core.type;
  document.getElementById('modal-title').textContent = core.title;
  document.getElementById('modal-status').textContent = core.status;
  document.getElementById('modal-priority').textContent = core.priority || 'Medium';
  document.getElementById('modal-priority').className = `priority-badge ${(core.priority || 'medium').toLowerCase()}`;
  document.getElementById('modal-risk').textContent = `${core.risk || 'Low'} Risk`;
  document.getElementById('modal-risk').className = `risk-badge ${(core.risk || 'low').toLowerCase()}`;
  
  // Render parent context if available
  renderParentContext(item);
  
  document.getElementById('modal-content').innerHTML = core.htmlContent || '<p>No content</p>';
  
  renderHistory(core.comments);
  
  switchTab('details');
  
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
}

// Render parent feature/spec context
function renderParentContext(item) {
  const container = document.getElementById('parent-context');
  
  if (!item.feature && !item.specification) {
    container.style.display = 'none';
    return;
  }
  
  let html = '<h4>Related Items</h4>';
  
  // Show feature if this is a spec or task
  if (item.feature && item.coreType !== 'feature') {
    const feat = item.feature;
    html += `
      <div class="parent-item feature">
        <div class="parent-item-header">
          <span class="parent-item-id" style="color: var(--accent-blue);">${feat.id}</span>
          <span class="parent-item-title">${escapeHtml(feat.title)}</span>
        </div>
        <div class="parent-item-desc">${escapeHtml(feat.description || 'No description')}</div>
      </div>
    `;
  }
  
  // Show specification if this is a task
  if (item.specification && item.coreType === 'task') {
    const spec = item.specification;
    html += `
      <div class="parent-item specification">
        <div class="parent-item-header">
          <span class="parent-item-id" style="color: var(--accent-purple);">${spec.id}</span>
          <span class="parent-item-title">${escapeHtml(spec.title)}</span>
        </div>
        <div class="parent-item-desc">${escapeHtml(spec.description || 'No description')}</div>
      </div>
    `;
  }
  
  container.innerHTML = html;
  container.style.display = 'block';
}

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

function closeModal() {
  document.getElementById('detail-modal').classList.remove('active');
  document.body.style.overflow = '';
}

function switchTab(tabName) {
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.tab === tabName);
  });
  document.querySelectorAll('.tab-content').forEach(content => {
    content.classList.toggle('active', content.id === `tab-${tabName}`);
  });
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

document.getElementById('detail-modal').addEventListener('click', (e) => {
  if (e.target.id === 'detail-modal') closeModal();
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeModal();
});

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
    
    logCount.textContent = data.showing;
    
    container.innerHTML = data.logs
      .filter(line => {
        // Filter out diagnostic noise
        if (line.includes('[diagnostic]') && line.includes('stuck session')) return false;
        if (line.includes('[diagnostic]') && line.includes('queueDepth')) return false;
        return true;
      })
      .map(line => {
        let className = 'log-line';
        if (line.includes('[ERROR]')) className += ' log-error';
        else if (line.includes('[WARN]')) className += ' log-warn';
        else if (line.includes('[INFO]')) className += ' log-info';
        else if (line.includes('[DEBUG]')) className += ' log-debug';
        
        return `<div class="${className}">${escapeHtml(line)}</div>`;
      }).join('');
    
    if (document.getElementById('auto-scroll').checked) {
      const logsContainer = document.getElementById('logs-container');
      logsContainer.scrollTop = logsContainer.scrollHeight;
    }
  } catch (error) {
    console.error('Failed to load logs:', error);
    document.getElementById('logs-content').innerHTML = `<div class="logs-error">Failed to load logs: ${escapeHtml(error.message)}</div>`;
  }
}
