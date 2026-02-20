# SPEC-011: Team Collaboration & User Management - Technical Specification

## STATUS
`To Break Into Tasks`

## PRIORITY
`Medium`

## RISK
`Medium`

## Related
- Feature: [FEAT-011](../FEATURES/FEAT-011.md)
- Tasks: (To be created by SpecSystemAnalyst)
- Builds on: SPEC-008 (multi-site project management)

---

## Overview

Enable multi-user access with role-based permissions for collaborative SEO management. Owners can invite team members, assign roles, and control access to specific projects. This transforms SEOIndicator into a collaborative tool for agencies and teams.

---

## Architecture

### System Design

```
┌───────────────────────────────────────────┐
│       Frontend (Next.js)                  │
├───────────────────────────────────────────┤
│   Components:                            │
│   ├── TeamManagementPage                 │
│   ├── InviteTeamMemberModal              │
│   ├── ProjectMemberList                  │
│   └── RoleAssignmentDropdown             │
├───────────────────────────────────────────┤
│   Integration Points:                     │
│   ├── Team Settings                      │
│   ├── Project Settings                   │
│   ├── Activity Log Viewer                │
│   └── Keyword Management (role-based UI) │
└───────────────────────────────────────────┘
          │
          ▼
┌───────────────────────────────────────────┐
│        Backend API (Laravel)              │
├───────────────────────────────────────────┤
│    Controllers:                          │
│    ├── TeamController                     │
│    ├── InvitationController               │
│    └── RoleAssignmentController           │
├───────────────────────────────────────────┤
│    Services:                             │
│    ├── TeamService                        │
│    ├── PermissionValidator                │
│    └── InvitationTokenHandler             │
└───────────────────────────────────────────┘
          │
          ▼
┌───────────────────────────────────────────┐
│        Database (MySQL)                   │
├───────────────────────────────────────────┤
│   Tables:                                │
│   ├── team_members                        │
│   ├── project_members                     │
│   ├── activity_log                        │
│   └── invitation_tokens                   │
└───────────────────────────────────────────┘
```

### Security and GDPR Compliance

- **Invite Tokens**: Secure tokens, short TTL (24h expiration).
- **Logging**: Store user actions (who added/edited what).
- **Data Isolation**: Role-based access control ensures users only see their assigned projects.
- **Revocation**: Team owners can revoke invitations and remove members anytime.

---

## Database Schema

### Tables

#### Table: team_members

```sql
CREATE TABLE team_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('owner', 'admin', 'editor', 'viewer') DEFAULT 'viewer',
    invited_by BIGINT UNSIGNED NOT NULL,
    invited_at TIMESTAMP NOT NULL,
    accepted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL,

    UNIQUE KEY idx_team_account_user (account_id, user_id),
    INDEX idx_role (role)
);
```

#### Table: project_members

```sql
CREATE TABLE project_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('editor', 'viewer') DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    UNIQUE KEY idx_project_user (project_id, user_id)
);
```

#### Table: activity_log

```sql
CREATE TABLE activity_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL,
    action VARCHAR(255) NOT NULL,
    details JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_project_user (project_id, user_id)
);
```

---

## API Design

### Team Management

```yaml
# Get team members
GET /api/team
Response:
{
    "data": [
        {
            "id": 1,
            "email": "team1@example.com",
            "role": "admin",
            "invited_at": "2026-02-20T10:00:00Z",
            "accepted_at": "2026-02-21T08:00:00Z"
        }
    ]
}

# Invite team member
POST /api/team/invite
Body: { "email": "newuser@example.com", "role": "editor" }
Response: { "success": true, "invitation_token": "abc123" }

# Revoke invitation
DELETE /api/team/invite/{id}
Response: { "success": true }

# Accept invitation
POST /api/team/accept
Body: { "invitation_token": "abc123" }
Response: { "success": true }

# Remove team member
DELETE /api/team/{userId}
Response: { "success": true }
```

### Project Member Management

```yaml
# Assign user to project
POST /api/projects/{projectId}/assign
Body: { "userId": 123, "role": "editor" }
Response: { "success": true }

# Remove user from project
DELETE /api/projects/{projectId}/assign/{userId}
Response: { "success": true }

# Get project members
GET /api/projects/{projectId}/members
Response: {
    "data": [
        { "id": 123, "email": "team1@example.com", "role": "editor" }
    ]
}
```

---

## Tests

### Unit Tests
- Validate role change logic (owner cannot change their own role).
- Validate secure token invitation flow.
- Validate project access control checks.

### Feature Tests
- Team invite acceptance and rejection.
- Role-based access to endpoints.
- Activity log data tracking changes.

### Integration Tests
- Team management API flows.
- Project assignment logic.

---

## Rollout Plan

1. Create database tables for team members, project members, and activity logging.
2. Implement invitation flow and secure token handling.
3. Build role-based access middleware and validators.
4. Update dashboard UI with new team management components.
5. Test thoroughly for GDPR compliance and data isolation.

---

## Comments
_Agent communication log_

### [2026-02-20 17:20] Agent: Architect
Created specification SPEC-011 for Team Collaboration & User Management (FEAT-011).

Key architectural decisions:
- Invitation-only team joining with secure tokens.
- Role-based access control with global and project-specific roles.
- Activity logging for accountability.
Ready for SpecSystemAnalyst breakdown.
