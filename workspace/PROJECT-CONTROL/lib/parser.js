const fs = require('fs').promises;
const path = require('path');
const { marked } = require('marked');

class Parser {
  constructor(projectPath) {
    this.projectPath = projectPath;
    this.statuses = [
      { id: 'new-feature', name: 'New Feature', order: 1, coreType: 'feature' },
      { id: 'specification-in-progress', name: 'Spec In Progress', order: 2, coreType: 'feature' },
      { id: 'to-break-into-tasks', name: 'To Break Into Tasks', order: 3, coreType: 'specification' },
      { id: 'breaking-into-tasks', name: 'Breaking Into Tasks', order: 4, coreType: 'specification' },
      { id: 'to-prioritize', name: 'To Prioritize', order: 5, coreType: 'task' },
      { id: 'prioritization-in-progress', name: 'Prioritizing', order: 6, coreType: 'task' },
      { id: 'to-write-tests', name: 'To Write Tests', order: 7, coreType: 'task' },
      { id: 'tests-coverage-in-progress', name: 'Writing Tests', order: 8, coreType: 'task' },
      { id: 'to-develop', name: 'To Develop', order: 9, coreType: 'task' },
      { id: 'in-development', name: 'In Development', order: 10, coreType: 'task' },
      { id: 'to-test-locally', name: 'To Test Locally', order: 11, coreType: 'task' },
      { id: 'testing-locally', name: 'Testing Locally', order: 12, coreType: 'task' },
      { id: 'to-deploy', name: 'To Deploy', order: 13, coreType: 'task' },
      { id: 'in-deployment', name: 'Deploying', order: 14, coreType: 'task' },
      { id: 'to-test-prod', name: 'To Test Prod', order: 15, coreType: 'task' },
      { id: 'testing-prod', name: 'Testing Prod', order: 16, coreType: 'task' },
      { id: 'documentation', name: 'Documentation', order: 17, coreType: 'task' },
      { id: 'done', name: 'Done', order: 18, coreType: 'task' },
      { id: 'cancelled', name: 'Cancelled', order: 19, coreType: 'task' }
    ];
    
    this.priorityOrder = { 'High': 1, 'Medium': 2, 'Low': 3 };
    this.riskOrder = { 'High': 1, 'Medium': 2, 'Low': 3 };
  }

  getStatuses() {
    return this.statuses;
  }

  normalizeId(id) {
    return id
      .replace(/FEAT-0*(\d+)/i, 'FEAT-$1')
      .replace(/SPEC-0*(\d+)/i, 'SPEC-$1')
      .replace(/TASK-0*(\d+)-0*(\d+)/i, 'TASK-$1-$2');
  }

  extractFeatureNumber(id) {
    const match = id.match(/(?:FEAT|SPEC|TASK)-0*(\d+)/i);
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

    const normalizedId = this.normalizeId(id);
    
    const statusMatch = content.match(/##\s*STATUS\s*\n`?([^`\n]+)`?/i);
    const priorityMatch = content.match(/##\s*PRIORITY\s*\n`?([^`\n]+)`?/i);
    const riskMatch = content.match(/##\s*RISK\s*\n`?([^`\n]+)`?/i);
    const titleMatch = content.match(/^#\s*(FEAT|SPEC|TASK)[\-\d]*:\s*(.+)$/m);
    
    let title = titleMatch ? titleMatch[2].trim() : normalizedId;
    
    const status = statusMatch ? statusMatch[1].trim().replace(/`/g, '') : 'Unknown';
    const priority = priorityMatch ? priorityMatch[1].trim().replace(/`/g, '') : 'Medium';
    const risk = riskMatch ? riskMatch[1].trim().replace(/`/g, '') : 'Low';

    const descMatch = content.match(/##\s*Description\s*\n([\s\S]*?)(?=\n##|\n---|$)/i);
    const description = descMatch ? descMatch[1].trim() : '';

    const commentsSection = content.match(/##\s*Comments[\s\S]*$/i);
    const comments = this.parseComments(commentsSection ? commentsSection[0] : '');

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

    const combined = this.createCombinedView(features, specifications, tasks);
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

  // Create workflow items based on status
  createCombinedView(features, specifications, tasks) {
    const items = [];

    // Index by feature number
    const featuresByNum = {};
    const specsByNum = {};
    const tasksBySpecNum = {};

    for (const f of features) {
      if (f.featureNumber) featuresByNum[f.featureNumber] = f;
    }
    for (const s of specifications) {
      if (s.featureNumber) specsByNum[s.featureNumber] = s;
    }
    for (const t of tasks) {
      const match = t.id.match(/TASK-(\d+)-\d+/i);
      if (match) {
        const specNum = parseInt(match[1], 10);
        if (!tasksBySpecNum[specNum]) tasksBySpecNum[specNum] = [];
        tasksBySpecNum[specNum].push(t);
      }
    }

    // Process each feature based on its status
    for (const feature of features) {
      const spec = specsByNum[feature.featureNumber];
      const relatedTasks = tasksBySpecNum[feature.featureNumber] || [];
      const statusKey = feature.status.toLowerCase().trim();

      // Determine what should be the core object for this status
      if (statusKey === 'new feature' || statusKey === 'specification in progress') {
        // Core: Feature
        items.push({
          coreType: 'feature',
          core: feature,
          feature,
          specification: spec,
          tasks: relatedTasks,
          status: feature.status,
          priority: feature.priority,
          risk: feature.risk,
          title: feature.title,
          id: feature.id,
          featureNumber: feature.featureNumber
        });
      } 
      else if (statusKey === 'to break into tasks' || statusKey === 'breaking into tasks') {
        // Core: Specification (if exists), otherwise feature
        if (spec) {
          items.push({
            coreType: 'specification',
            core: spec,
            feature,
            specification: spec,
            tasks: relatedTasks,
            status: feature.status,
            priority: spec.priority || feature.priority,
            risk: spec.risk || feature.risk,
            title: spec.title,
            id: spec.id,
            featureNumber: feature.featureNumber
          });
        } else {
          // No spec yet, show feature
          items.push({
            coreType: 'feature',
            core: feature,
            feature,
            specification: null,
            tasks: [],
            status: feature.status,
            priority: feature.priority,
            risk: feature.risk,
            title: feature.title,
            id: feature.id,
            featureNumber: feature.featureNumber
          });
        }
      }
      else {
        // To Prioritize onwards: Core is Task
        if (relatedTasks.length > 0) {
          // Each task is a separate item
          for (const task of relatedTasks) {
            items.push({
              coreType: 'task',
              core: task,
              feature,
              specification: spec,
              tasks: [task],
              allTasks: relatedTasks,
              status: task.status || feature.status,
              priority: task.priority || spec?.priority || feature.priority,
              risk: task.risk || spec?.risk || feature.risk,
              title: task.title,
              id: task.id,
              featureNumber: feature.featureNumber
            });
          }
        } else if (spec) {
          // No tasks yet, show spec
          items.push({
            coreType: 'specification',
            core: spec,
            feature,
            specification: spec,
            tasks: [],
            status: feature.status,
            priority: spec.priority || feature.priority,
            risk: spec.risk || feature.risk,
            title: spec.title,
            id: spec.id,
            featureNumber: feature.featureNumber
          });
        } else {
          // No spec or tasks, show feature
          items.push({
            coreType: 'feature',
            core: feature,
            feature,
            specification: null,
            tasks: [],
            status: feature.status,
            priority: feature.priority,
            risk: feature.risk,
            title: feature.title,
            id: feature.id,
            featureNumber: feature.featureNumber
          });
        }
      }
    }

    // Sort by status order, then priority
    const statusOrderMap = {};
    this.statuses.forEach((s, i) => statusOrderMap[s.name.toLowerCase()] = i);
    
    items.sort((a, b) => {
      const statusA = statusOrderMap[a.status.toLowerCase()] ?? 99;
      const statusB = statusOrderMap[b.status.toLowerCase()] ?? 99;
      if (statusA !== statusB) return statusA - statusB;
      
      const prioA = a.core?.sortOrder || 2;
      const prioB = b.core?.sortOrder || 2;
      if (prioA !== prioB) return prioA - prioB;
      
      return (a.core?.riskSortOrder || 3) - (b.core?.riskSortOrder || 3);
    });

    return items;
  }

  async getById(type, id) {
    let folder;
    switch (type) {
      case 'feature': folder = 'FEATURES'; break;
      case 'specification': folder = 'SPECIFICATIONS'; break;
      case 'task': folder = 'TASKS'; break;
      default: return null;
    }

    const normalizedId = this.normalizeId(id);
    let filePath = path.join(this.projectPath, folder, `${normalizedId}.md`);
    let content = await this.readFile(filePath);
    
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
