const fs = require('fs').promises;
const path = require('path');
const { marked } = require('marked');

class Parser {
  constructor(projectPath) {
    this.projectPath = projectPath;
    this.statuses = [
      { id: 'new-feature', name: 'New Feature', order: 1 },
      { id: 'specification-in-progress', name: 'Specification In Progress', order: 2 },
      { id: 'to-break-into-tasks', name: 'To Break Into Tasks', order: 3 },
      { id: 'breaking-into-tasks', name: 'Breaking Into Tasks', order: 4 },
      { id: 'to-prioritize', name: 'To Prioritize', order: 5 },
      { id: 'prioritization-in-progress', name: 'Prioritization In Progress', order: 6 },
      { id: 'to-write-tests', name: 'To Write Tests', order: 7 },
      { id: 'tests-coverage-in-progress', name: 'Tests Coverage In Progress', order: 8 },
      { id: 'to-develop', name: 'To Develop', order: 9 },
      { id: 'in-development', name: 'In Development', order: 10 },
      { id: 'to-test-locally', name: 'To Test Locally', order: 11 },
      { id: 'testing-locally', name: 'Testing Locally', order: 12 },
      { id: 'to-deploy', name: 'To Deploy', order: 13 },
      { id: 'in-deployment', name: 'In Deployment', order: 14 },
      { id: 'to-test-prod', name: 'To Test Prod', order: 15 },
      { id: 'testing-prod', name: 'Testing Prod', order: 16 },
      { id: 'done', name: 'Done', order: 17 },
      { id: 'cancelled', name: 'Cancelled', order: 18 }
    ];
    
    this.priorityOrder = { 'High': 1, 'Medium': 2, 'Low': 3 };
    this.riskOrder = { 'High': 1, 'Medium': 2, 'Low': 3 };
  }

  getStatuses() {
    return this.statuses;
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

    const lines = content.split('\n');
    
    // Extract metadata
    const statusMatch = content.match(/##\s*STATUS\s*\n`?([^`\n]+)`?/i);
    const priorityMatch = content.match(/##\s*PRIORITY\s*\n`?([^`\n]+)`?/i);
    const riskMatch = content.match(/##\s*RISK\s*\n`?([^`\n]+)`?/i);
    const titleMatch = content.match(/^#\s*(FEAT|SPEC|SPEC-\d+-\d+):\s*(.+)$/m);
    
    // Extract title from first heading
    let title = titleMatch ? titleMatch[2].trim() : id;
    
    // Extract status, priority, risk
    const status = statusMatch ? statusMatch[1].trim().replace(/`/g, '') : 'Unknown';
    const priority = priorityMatch ? priorityMatch[1].trim().replace(/`/g, '') : 'Medium';
    const risk = riskMatch ? riskMatch[1].trim().replace(/`/g, '') : 'Low';

    // Extract description (text after "## Description" until next ##)
    const descMatch = content.match(/##\s*Description\s*\n([\s\S]*?)(?=\n##|\n---|$)/i);
    const description = descMatch ? descMatch[1].trim() : '';

    // Extract comments/history
    const commentsSection = content.match(/##\s*Comments[\s\S]*$/i);
    const comments = this.parseComments(commentsSection ? commentsSection[0] : '');

    // Parse full content as HTML for detail view
    const htmlContent = marked(content);

    return {
      id,
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
      riskSortOrder: this.riskOrder[risk] || 3
    };
  }

  parseComments(commentsText) {
    if (!commentsText) return [];
    
    const comments = [];
    const lines = commentsText.split('\n');
    let currentComment = null;

    for (const line of lines) {
      // Match: ### [YYYY-MM-DD HH:MM] Agent: [Name]
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
      // Folder doesn't exist or can't be read
    }

    return items;
  }

  async getAll() {
    const [features, specifications, tasks] = await Promise.all([
      this.scanFolder('FEATURES', 'feature'),
      this.scanFolder('SPECIFICATIONS', 'specification'),
      this.scanFolder('TASKS', 'task')
    ]);

    // Combine and sort by status order, then priority, then risk
    const all = [...features, ...specifications, ...tasks];
    
    return {
      features,
      specifications,
      tasks,
      all,
      statuses: this.statuses
    };
  }

  async getById(type, id) {
    let folder;
    switch (type) {
      case 'feature': folder = 'FEATURES'; break;
      case 'specification': folder = 'SPECIFICATIONS'; break;
      case 'task': folder = 'TASKS'; break;
      default: return null;
    }

    const filePath = path.join(this.projectPath, folder, `${id}.md`);
    const content = await this.readFile(filePath);
    
    if (!content) return null;
    
    return this.parseMarkdown(content, type, id);
  }
}

module.exports = Parser;
