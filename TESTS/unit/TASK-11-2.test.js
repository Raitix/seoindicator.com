// Unit tests for TASK-11-2: Project Members Table
// Tests schema validation, constraints, and migration integrity

const { createTestDatabase, destroyTestDatabase } = require('../helpers/database');

describe('Unit: Project Members Table (TASK-11-2)', () => {
  let db;

  beforeAll(async () => {
    db = await createTestDatabase();
  });

  afterAll(async () => {
    await destroyTestDatabase(db);
  });

  describe('Schema Validation', () => {
    test('TC-1.1: project_members table exists after migration', async () => {
      const result = await db.get(`
        SELECT name FROM sqlite_master 
        WHERE type='table' AND name='project_members'
      `);
      expect(result).not.toBeNull();
      expect(result.name).toBe('project_members');
    });

    test('TC-1.2: Primary key constraint exists on id column', async () => {
      const tableInfo = await db.all(`PRAGMA table_info(project_members)`);
      const idColumn = tableInfo.find(col => col.name === 'id');
      expect(idColumn).toBeDefined();
      expect(idColumn.pk).toBe(1); // Primary key flag
    });

    test('TC-1.3: id column is auto-increment', async () => {
      const tableInfo = await db.all(`PRAGMA table_info(project_members)`);
      const idColumn = tableInfo.find(col => col.name === 'id');
      expect(idColumn).toBeDefined();
      // SQLite autoincrement check
      const result = await db.get(`
        SELECT sql FROM sqlite_master WHERE name='project_members'
      `);
      expect(result.sql).toMatch(/AUTOINCREMENT/i);
    });

    test('TC-1.4: Default values are set correctly', async () => {
      const tableInfo = await db.all(`PRAGMA table_info(project_members)`);
      
      const roleColumn = tableInfo.find(col => col.name === 'role');
      expect(roleColumn).toBeDefined();
      expect(roleColumn.dflt_value).toBe("'viewer'");
      
      const createdAtColumn = tableInfo.find(col => col.name === 'created_at');
      expect(createdAtColumn).toBeDefined();
      // Timestamps have default values
    });

    test('TC-1.5: Required columns exist', async () => {
      const tableInfo = await db.all(`PRAGMA table_info(project_members)`);
      const columnNames = tableInfo.map(col => col.name);
      
      expect(columnNames).toContain('id');
      expect(columnNames).toContain('project_id');
      expect(columnNames).toContain('user_id');
      expect(columnNames).toContain('role');
      expect(columnNames).toContain('created_at');
      expect(columnNames).toContain('updated_at');
    });
  });

  describe('Role Column Validation', () => {
    test('TC-4.1: Role "editor" is valid', async () => {
      // Insert test project and user first
      await db.run(`INSERT OR IGNORE INTO projects (id, name) VALUES (9991, 'Test Project')`);
      await db.run(`INSERT OR IGNORE INTO users (id, email, name) VALUES (9991, 'editor@test.com', 'Editor User')`);
      
      const result = await db.run(`
        INSERT INTO project_members (project_id, user_id, role) 
        VALUES (9991, 9991, 'editor')
      `);
      expect(result.changes).toBe(1);
      
      // Cleanup
      await db.run(`DELETE FROM project_members WHERE project_id = 9991`);
    });

    test('TC-4.2: Role "viewer" is valid', async () => {
      await db.run(`INSERT OR IGNORE INTO projects (id, name) VALUES (9992, 'Test Project 2')`);
      await db.run(`INSERT OR IGNORE INTO users (id, email, name) VALUES (9992, 'viewer@test.com', 'Viewer User')`);
      
      const result = await db.run(`
        INSERT INTO project_members (project_id, user_id, role) 
        VALUES (9992, 9992, 'viewer')
      `);
      expect(result.changes).toBe(1);
      
      // Cleanup
      await db.run(`DELETE FROM project_members WHERE project_id = 9992`);
    });

    test('TC-4.4: Default role is "viewer"', async () => {
      await db.run(`INSERT OR IGNORE INTO projects (id, name) VALUES (9993, 'Test Project 3')`);
      await db.run(`INSERT OR IGNORE INTO users (id, email, name) VALUES (9993, 'default@test.com', 'Default User')`);
      
      const result = await db.run(`
        INSERT INTO project_members (project_id, user_id) 
        VALUES (9993, 9993)
      `);
      expect(result.changes).toBe(1);
      
      const record = await db.get(`
        SELECT role FROM project_members WHERE project_id = 9993
      `);
      expect(record.role).toBe('viewer');
      
      // Cleanup
      await db.run(`DELETE FROM project_members WHERE project_id = 9993`);
    });
  });

  describe('Unique Constraint Tests', () => {
    test('TC-3.1: Unique constraint on (project_id, user_id) exists', async () => {
      const result = await db.get(`
        SELECT sql FROM sqlite_master WHERE name='project_members'
      `);
      // Check for UNIQUE constraint in table definition
      expect(result.sql).toMatch(/UNIQUE.*project_id.*user_id/i);
    });

    test('TC-3.2: Duplicate project-user pair is rejected', async () => {
      await db.run(`INSERT OR IGNORE INTO projects (id, name) VALUES (9981, 'Unique Test Project')`);
      await db.run(`INSERT OR IGNORE INTO users (id, email, name) VALUES (9981, 'unique@test.com', 'Unique User')`);
      
      // First insert should succeed
      await db.run(`INSERT INTO project_members (project_id, user_id) VALUES (9981, 9981)`);
      
      // Second insert with same pair should fail
      await expect(
        db.run(`INSERT INTO project_members (project_id, user_id) VALUES (9981, 9981)`)
      ).rejects.toThrow();
      
      // Cleanup
      await db.run(`DELETE FROM project_members WHERE project_id = 9981`);
    });

    test('TC-3.3: Same user can be in multiple projects', async () => {
      await db.run(`INSERT OR IGNORE INTO projects (id, name) VALUES (9971, 'Multi Project A')`);
      await db.run(`INSERT OR IGNORE INTO projects (id, name) VALUES (9972, 'Multi Project B')`);
      await db.run(`INSERT OR IGNORE INTO users (id, email, name) VALUES (9971, 'multi@test.com', 'Multi User')`);
      
      // Same user in different projects
      const result1 = await db.run(`INSERT INTO project_members (project_id, user_id) VALUES (9971, 9971)`);
      const result2 = await db.run(`INSERT INTO project_members (project_id, user_id) VALUES (9972, 9971)`);
      
      expect(result1.changes).toBe(1);
      expect(result2.changes).toBe(1);
      
      // Cleanup
      await db.run(`DELETE FROM project_members WHERE user_id = 9971`);
    });

    test('TC-3.4: Multiple users can be in same project', async () => {
      await db.run(`INSERT OR IGNORE INTO projects (id, name) VALUES (9961, 'Shared Project')`);
      await db.run(`INSERT OR IGNORE INTO users (id, email, name) VALUES (9961, 'user1@test.com', 'User 1')`);
      await db.run(`INSERT OR IGNORE INTO users (id, email, name) VALUES (9962, 'user2@test.com', 'User 2')`);
      
      // Different users in same project
      const result1 = await db.run(`INSERT INTO project_members (project_id, user_id) VALUES (9961, 9961)`);
      const result2 = await db.run(`INSERT INTO project_members (project_id, user_id) VALUES (9961, 9962)`);
      
      expect(result1.changes).toBe(1);
      expect(result2.changes).toBe(1);
      
      // Cleanup
      await db.run(`DELETE FROM project_members WHERE project_id = 9961`);
    });
  });
});
