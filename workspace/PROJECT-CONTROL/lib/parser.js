const fs = require('fs').promises;
const path = require('path');
const { marked } = require('marked');

class Parser {
  constructor(projectPath) {
    this.projectPath = projectPath;
    this.statuses = [
      { id: 'new-feature', name: 'New Feature', order: 1, agent: 'Architect' },
      { id: 'specification-in-progress', name: 'Spec In Progress', order: 2, agent: 'Architect' },
      { id: 'to-break-into-tasks', name: 'To Break Into Tasks', order: 3, agent: 'SpecSystemAnalyst' },
      { id: 'breaking-into-tasks', name: 'Breaking Into Tasks', order: 4, agent: 'SpecSystemAnalyst' },
      { id: 'to-prioritize', name: 'To Prioritize', order: 5, agent: 'ProjectManager' },
      { id: 'prioritization-in-progress', name: 'Prioritizing', order: 6, agent: 'ProjectManager' },
      { id: 'to-write-tests', name: 'To Write Tests', order: 7, agent: 'PreTester' },
      { id: 'tests-coverage-in-progress', name: 'Writing Tests', order: 8, agent: 'PreTester' },
      { id: 'to-develop', name: 'To Develop', order: 9, agent: 'Developer' },
      { id: 'in-development', name: 'In Development', order: 10, agent: 'Developer' },
      { id: 'to-test-locally', name: 'To Test Locally', order: 11, agent: 'LocalTester' },
      { id: 'testing-locally', name: 'Testing Locally', order: 12, agent: 'LocalTester' },
      { id: 'to-deploy', name: 'To Deploy', order: 13, agent: 'DevOps' },
      { id: 'in-deployment', name: 'Deploying', order: 14, agent: 'DevOps' },
      { id: 'to-test-prod', name: 'To Test Prod', order: 15, agent: 'ProdTester' },
      { id: 'testing-prod', name: 'Testing Prod', order: 16, agent: 'ProdTester' },
      { id: 'done', name: 'Done', order: 17, agent: 'DocSystemAnalyst' },
      { id: 'cancelled', name: 'Cancelled', order: 18, agent: '-' }
    ];
    
    this.priorityOrder = { 'High': 1, 'Medium': 2, 'Low': 3 };
    this.riskOrder = { 'High': 1, 'Medium': 2, 'Low': 3 };
  }

  getStatuses() {
    return this.statuses;
  }

  // Normalize ID: FEAT-001 -> FEAT-1, SPEC-003-02 -> SPEC-3-2
  normalizeId(id) {
    return id
      .replace(/FEAT-0*(\d+)/i, 'FEAT-$1')
      .replace(/SPEC-0*(\d+)-0*(\d+)/i, 'SPEC-$1-$2')
      .replace(/SPEC-0*(\d+)/i, 'SPEC-$1');
  }

  // Extract numeric ID for grouping (FEAT-001 -> 1, SPEC-003 -> 3)
  extractFeatureNumber(id) {
    const match = id.match(/(?:FEAT|SPEC)-0*(\d+)/i);
    return match ? parseInt(match[1], 10) : null;
  }

  async readFile(filePath) {
    try {
      const content = await fs.readFile(filePath, 'utf-8');
      return content;
    } catch {
      return null;
    }
  }

  parseMarkdown(content, type, id) {
    if (!content) return null;

    // Normalize the ID
    const normalizedId = this.normalizeId(id);
    
    // Extract metadata
    const statusMatch = content.match(/##\s*STATUS\s*\n`?([^`\n]+)`?/i);
    const priorityMatch = content.match(/##\s*PRIORITY\s*\n`?([^`\n]+)`?/i);
    const riskMatch = content.match(/##\s*RISK\s*\n`?([^`\n]+)`?/i);
    const titleMatch = content.match(/^#\s*(FEAT|SPEC)[\-\d]*:\s*(.+)$/m);
    
    // Extract title from first heading
    let title = titleMatch ? titleMatch[2].trim() : normalizedId;
    
    // Extract status, priority, risk
    const status = statusMatch ? statusMatch[1].trim().replace(/`/g, '') : 'Unknown';
    const priority = priorityMatch ? priorityMatch[1].trim().replace(/`/g, '') : 'Medium';
    const risk = riskMatch ? riskMatch[1].trim().replace(/`/g, '') : 'Low';

    // Extract description
    const descMatch = content.match(/##\s*Description\s*\n([\s\S]*?)(?=\n##|\n---|$)/i);
    const description = descMatch ? descMatch[1].trim() : '';

    // Extract comments/history
    const commentsSection = content.match(/##\s*Comments[\s\S]*$/i);
    const comments = this.parseComments(commentsSection ? commentsSection[0] : '');

    // Parse full content as HTML
    const htmlContent = marked(content);

    return {
      id: normalizedId,
      originalId: id,
      type,
      title,
      status,
      priority,
      risk,
      description,
      comments,
      htmlContent,
      rawContent: content,
      sortOrder: this.priorityOrder[priority] || 2,
      riskSortOrder: this.riskOrder[risk] || 3,
      featureNumber: this.extractFeatureNumber(id)
    };
  }

  parseComments(commentsText) {
    if (!commentsText) return [];
    
    const comments = [];
    const lines = commentsText.split('\n');
    let currentComment = null;

    for (const line of lines) {
      const headerMatch = line.match(/###\s*\[([^\]]+)\]\s*Agent:\s*(.+)/i);
      if (headerMatch) {
        if (currentComment) {
          comments.push(currentComment);
        }
        currentComment = {
          timestamp: headerMatch[1],
          agent: headerMatch[2].trim(),
          content: ''
        };
      } else if (currentComment && line.trim() && !line.startsWith('## Comments')) {
        currentComment.content += (currentComment.content ? '\n' : '') + line.trim();
      }
    }

    if (currentComment) {
      comments.push(currentComment);
    }

    return comments;
  }

  async scanFolder(folderName, type) {
    const folderPath = path.join(this.projectPath, folderName);
    const items = [];

    try {
      const files = await fs.readdir(folderPath);
      
      for (const file of files) {
        if (file.startsWith('.') || !file.endsWith('.md')) continue;
        
        const id = file.replace('.md', '');
        const filePath = path.join(folderPath, file);
        const content = await this.readFile(filePath);
        
        if (content) {
          const parsed = this.parseMarkdown(content, type, id);
          if (parsed) {
            items.push(parsed);
          }
        }
      }
    } catch {
      // Folder doesn't exist
    }

    return items;
  }

  async getAll() {
    const [features, specifications, tasks] = await Promise.all([
      this.scanFolder('FEATURES', 'feature'),
      this.scanFolder('SPECIFICATIONS', 'specification'),
      this.scanFolder('TASKS', 'task')
    ]);

    // Create combined workflow items
    const combined = this.createCombinedView(features, specifications, tasks);

    // Combine all for backward compatibility
    const all = [...features, ...specifications, ...tasks];
    
    return {
      features,
      specifications,
      tasks,
      all,
      combined,
      statuses: this.statuses
    };
  }

  // Create combined view: feature + related spec + related tasks
  createCombinedView(features, specifications, tasks) {
    const combined = [];

    // Group specs and tasks by feature number
    const specsByFeature = {};
    const tasksBySpec = {};

    for (const spec of specifications) {
      if (spec.featureNumber) {
        specsByFeature[spec.featureNumber] = spec;
      }
    }

    for (const task of tasks) {
      // Task ID like SPEC-3-1 -> feature number 3
      const match = task.id.match(/SPEC-(\d+)-\d+/i);
      if (match) {
        const specNum = parseInt(match[1], 10);
        if (!tasksBySpec[specNum]) tasksBySpec[specNum] = [];
        tasksBySpec[specNum].push(task);
      }
    }

    // Create combined items for each feature
    for (const feature of features) {
      const spec = specsByFeature[feature.featureNumber];
      const relatedTasks = tasksBySpec[feature.featureNumber] || [];

      combined.push({
        id: feature.id,
        feature,
        specification: spec || null,
        tasks: relatedTasks,
        // Use feature's status as the primary status
        status: feature.status,
        priority: feature.priority,
        risk: feature.risk,
        title: feature.title,
        featureNumber: feature.featureNumber,
        hasSpec: !!spec,
        hasTasks: relatedTasks.length > 0,
        taskCount: relatedTasks.length
      });
    }

    // Sort by status order, then priority, then risk
    const statusOrderMap = {};
    this.statuses.forEach((s, i) => statusOrderMap[s.name.toLowerCase()] = i);
    
    combined.sort((a, b) => {
      const statusA = statusOrderMap[a.status.toLowerCase()] ?? 99;
      const statusB = statusOrderMap[b.status.toLowerCase()] ?? 99;
      if (statusA !== statusB) return statusA - statusB;
      
      const prioA = a.feature.sortOrder || 2;
      const prioB = b.feature.sortOrder || 2;
      if (prioA !== prioB) return prioA - prioB;
      
      return (a.feature.riskSortOrder || 3) - (b.feature.riskSortOrder || 3);
    });

    return combined;
  }

  async getById(type, id) {
    let folder;
    switch (type) {
      case 'feature': folder = 'FEATURES'; break;
      case 'specification': folder = 'SPECIFICATIONS'; break;
      case 'task': folder = 'TASKS'; break;
      default: return null;
    }

    // Try both normalized and original ID
    const normalizedId = this.normalizeId(id);
    let filePath = path.join(this.projectPath, folder, `${normalizedId}.md`);
    let content = await this.readFile(filePath);
    
    // Try with leading zeros if not found
    if (!content) {
      const numMatch = id.match(/\d+/);
      if (numMatch) {
        const padded = numMatch[0].padStart(3, '0');
        const paddedId = id.replace(/\d+/, padded);
        filePath = path.join(this.projectPath, folder, `${paddedId}.md`);
        content = await this.readFile(filePath);
      }
    }
    
    if (!content) return null;
    
    return this.parseMarkdown(content, type, id);
  }
}

module.exports = Parser;
