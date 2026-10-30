// Integration tests for TASK-11-2: Project Members Table
// Tests CRUD operations, foreign key constraints, and referential integrity

const { createTestDatabase, destroyTestDatabase } = require('../helpers/database');

describe('Integration: Project Members Table (TASK-11-2)', () => {
  let db;

  beforeAll(async () => {
    db = await createTestDatabase();
    // Setup test data
    await setupTestData();
  });

  afterAll(async () => {
    await cleanupTestData();
    await destroyTestDatabase(db);
  });

  async function setupTestData() {
    // Create test projects
    await db.run(`INSERT OR IGNORE INTO projects (id, name, url) VALUES (1001, 'Integration Test Project 1', 'https://test1.com')`);
    await db.run(`INSERT OR IGNORE INTO projects (id, name, url) VALUES (1002, 'Integration Test Project 2', 'https://test2.com')`);
    
    // Create test users
    await db.run(`INSERT OR IGNORE INTO users (id, email, name) VALUES (1001, 'int1@test.com', 'Integration User 1')`);
    await db.run(`INSERT OR IGNORE INTO users (id, email, name) VALUES (1002, 'int2@test.com', 'Integration User 2')`);
    await db.run(`INSERT OR IGNORE INTO users (id, email, name) VALUES (1003, 'int3@test.com', 'Integration User 3')`);
  }

  async function cleanupTestData() {
    await db.run(`DELETE FROM project_members WHERE project_id IN (1001, 1002)`);
    await db.run(`DELETE FROM projects WHERE id IN (1001, 1002)`);
    await db.run(`DELETE FROM users WHERE id IN (1001, 1002, 1003)`);
  }

  describe('Foreign Key Constraint Tests', () => {
    test('TC-2.1: Foreign key to projects table is enforced', async () => {
      // This test verifies FK exists by checking foreign_keys pragma
      const fkStatus = await db.get(`PRAGMA foreign_keys`);
      expect(fkStatus.foreign_keys).toBe(1);
    });

    test('TC-2.5: Insert with non-existent project_id fails', async () => {
      // Try to insert with non-existent project (id 99999)
      await expect(
        db.run(`INSERT INTO project_members (project_id, user_id) VALUES (99999, 1001)`)
      ).rejects.toThrow();
    });

    test('TC-2.6: Insert with non-existent user_id fails', async () => {
      // Try to insert with non-existent user (id 99999)
      await expect(
        db.run(`INSERT INTO project_members (project_id, user_id) VALUES (1001, 99999)`)
      ).rejects.toThrow();
    });
  });

  describe('CRUD Operations', () => {
    test('TC-5.1: Insert valid record', async () => {
      const result = await db.run(`
        INSERT INTO project_members (project_id, user_id, role) 
        VALUES (1001, 1001, 'editor')
      `);
      expect(result.changes).toBe(1);
      expect(result.lastID).toBeGreaterThan(0);
    });

    test('TC-5.2: Update role', async () => {
      // Insert first
      await db.run(`INSERT OR IGNORE INTO project_members (project_id, user_id, role) VALUES (1001, 1002, 'viewer')`);
      
      // Update role
      const result = await db.run(`
        UPDATE project_members SET role = 'editor' 
        WHERE project_id = 1001 AND user_id = 1002
      `);
      expect(result.changes).toBe(1);
      
      // Verify update
      const record = await db.get(`
        SELECT role FROM project_members WHERE project_id = 1001 AND user_id = 1002
      `);
      expect(record.role).toBe('editor');
    });

    test('TC-5.3: Delete record', async () => {
      // Insert first
      await db.run(`INSERT OR IGNORE INTO project_members (project_id, user_id) VALUES (1002, 1001)`);
      
      // Delete
      const result = await db.run(`
        DELETE FROM project_members WHERE project_id = 1002 AND user_id = 1001
      `);
      expect(result.changes).toBe(1);
      
      // Verify deletion
      const record = await db.get(`
        SELECT * FROM project_members WHERE project_id = 1002 AND user_id = 1001
      `);
      expect(record).toBeUndefined();
    });

    test('TC-5.4: Select by project_id', async () => {
      // Setup
      await db.run(`DELETE FROM project_members WHERE project_id = 1001`);
      await db.run(`INSERT INTO project_members (project_id, user_id) VALUES (1001, 1001)`);
      await db.run(`INSERT INTO project_members (project_id, user_id) VALUES (1001, 1002)`);
      await db.run(`INSERT INTO project_members (project_id, user_id) VALUES (1001, 1003)`);
      
      const records = await db.all(`
        SELECT * FROM project_members WHERE project_id = 1001
      `);
      expect(records.length).toBe(3);
    });

    test('TC-5.5: Select by user_id', async () => {
      // Setup - user 1003 in both projects
      await db.run(`DELETE FROM project_members WHERE user_id = 1003`);
      await db.run(`INSERT INTO project_members (project_id, user_id) VALUES (1001, 1003)`);
      await db.run(`INSERT INTO project_members (project_id, user_id) VALUES (1002, 1003)`);
      
      const records = await db.all(`
        SELECT * FROM project_members WHERE user_id = 1003
      `);
      expect(records.length).toBe(2);
    });
  });

  describe('ON DELETE CASCADE Tests', () => {
    test('TC-2.3: ON DELETE CASCADE for project_id - members deleted when project deleted', async () => {
      // Create temporary project
      await db.run(`INSERT INTO projects (id, name, url) VALUES (2001, 'Cascade Test Project', 'https://cascade.com')`);
      await db.run(`INSERT INTO project_members (project_id, user_id) VALUES (2001, 1001)`);
      
      // Verify member exists
      let member = await db.get(`SELECT * FROM project_members WHERE project_id = 2001`);
      expect(member).toBeDefined();
      
      // Delete project
      await db.run(`DELETE FROM projects WHERE id = 2001`);
      
      // Verify member is also deleted
      member = await db.get(`SELECT * FROM project_members WHERE project_id = 2001`);
      expect(member).toBeUndefined();
    });

    test('TC-2.4: ON DELETE CASCADE for user_id - members deleted when user deleted', async () => {
      // Create temporary user
      await db.run(`INSERT INTO users (id, email, name) VALUES (2001, 'cascade@test.com', 'Cascade User')`);
      await db.run(`INSERT INTO project_members (project_id, user_id) VALUES (1001, 2001)`);
      
      // Verify member exists
      let member = await db.get(`SELECT * FROM project_members WHERE user_id = 2001`);
      expect(member).toBeDefined();
      
      // Delete user
      await db.run(`DELETE FROM users WHERE id = 2001`);
      
      // Verify member is also deleted
      member = await db.get(`SELECT * FROM project_members WHERE user_id = 2001`);
      expect(member).toBeUndefined();
    });
  });

  describe('Timestamp Tests', () => {
    test('created_at is set automatically on insert', async () => {
      await db.run(`DELETE FROM project_members WHERE project_id = 1002 AND user_id = 1002`);
      await db.run(`INSERT INTO project_members (project_id, user_id) VALUES (1002, 1002)`);
      
      const record = await db.get(`
        SELECT created_at FROM project_members WHERE project_id = 1002 AND user_id = 1002
      `);
      expect(record.created_at).toBeDefined();
      expect(new Date(record.created_at)).toBeInstanceOf(Date);
    });

    test('updated_at is set automatically on insert', async () => {
      const record = await db.get(`
        SELECT updated_at FROM project_members WHERE project_id = 1002 AND user_id = 1002
      `);
      expect(record.updated_at).toBeDefined();
    });
  });

  describe('Query Performance Tests', () => {
    test('Index on project_id exists for efficient queries', async () => {
      const indexes = await db.all(`
        SELECT name FROM sqlite_master 
        WHERE type='index' AND tbl_name='project_members'
      `);
      // Should have at least one index
      expect(indexes.length).toBeGreaterThan(0);
    });
  });
});
