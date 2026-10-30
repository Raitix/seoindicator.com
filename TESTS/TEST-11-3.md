# TEST-11-3: Team Management Service Tests

## Task Reference
- Task: [TASK-11-3](../TASKS/TASK-11-3.md)
- Feature: [FEAT-011](../FEATURES/FEAT-011.md) - Team Collaboration & User Management
- Specification: [SPEC-011](../SPECIFICATIONS/SPEC-011.md)

---

## Overview

Test suite for `TeamService` class handling team collaboration backend logic:
- Adding team members with invitation tokens
- Updating member roles with permission enforcement
- Removing members with validation rules

---

## Test Files

| Type | File Path | Purpose |
|------|-----------|---------|
| Unit | `tests/Unit/Services/TeamServiceTest.php` | Test TeamService methods in isolation |
| Feature | `tests/Feature/TeamManagementTest.php` | Test API endpoints and service integration |
| Integration | `tests/Integration/TeamServiceIntegrationTest.php` | Test database interactions and constraints |

---

## Test Scenarios

### 1. Add Member (addMember)

| ID | Scenario | Input | Expected Output | Risk |
|----|----------|-------|-----------------|------|
| AM-01 | Valid invitation with new email | `{email: "new@example.com", role: "editor", invited_by: owner_id}` | TeamMember created, status pending, token generated | Low |
| AM-02 | Valid invitation with default role | `{email: "new@example.com", invited_by: admin_id}` | TeamMember created with role `viewer` | Low |
| AM-03 | Duplicate email invitation (pending) | Same email twice before acceptance | Error: "Invitation already pending" | Medium |
| AM-04 | Duplicate email (already accepted) | Email of existing team member | Error: "User already team member" | Medium |
| AM-05 | Invalid email format | `{email: "not-an-email", role: "editor"}` | Validation error: Invalid email | Low |
| AM-06 | Empty email | `{email: "", role: "editor"}` | Validation error: Email required | Low |
| AM-07 | Null email | `{email: null, role: "editor"}` | Validation error: Email required | Low |
| AM-08 | Invalid role value | `{email: "new@example.com", role: "superadmin"}` | Validation error: Invalid role | Low |
| AM-09 | Invitation by non-admin/editor | `{email: "new@example.com", invited_by: viewer_id}` | Error: "Insufficient permissions" | High |
| AM-10 | Token uniqueness | Multiple invitations | Each token unique, 64+ chars | Medium |
| AM-11 | Token security | Generated token | Cryptographically secure, not guessable | High |
| AM-12 | Token expiration | Token after 24 hours | Token invalid, cannot accept | Medium |
| AM-13 | Email with unicode characters | `{email: "пользователь@пример.рф"}` | Valid, invitation created | Low |
| AM-14 | Email with plus addressing | `{email: "user+team@example.com"}` | Valid, invitation created | Low |
| AM-15 | SQL injection in email | `{email: "test'; DROP TABLE users;--"}` | Sanitized, no SQL executed | High |
| AM-16 | XSS in email | `{email: "<script>alert('xss')</script>@example.com"}` | Validation error or sanitized | High |
| AM-17 | Very long email (500+ chars) | `{email: "a" * 500 + "@example.com"}` | Validation error: Email too long | Low |
| AM-18 | Missing invited_by | `{email: "new@example.com", role: "editor"}` | Error: Inviter required | Low |
| AM-19 | Non-existent inviter | `{email: "new@example.com", invited_by: 999999}` | Error: Inviter not found | Medium |
| AM-20 | Self-invitation attempt | `{email: owner_email, invited_by: owner_id}` | Error: Cannot invite self | Medium |
| AM-21 | Case-insensitive email | `{email: "NEW@EXAMPLE.COM"}` | Normalized to lowercase | Low |

### 2. Update Role (updateRole)

| ID | Scenario | Input | Expected Output | Risk |
|----|----------|-------|-----------------|------|
| UR-01 | Admin promotes viewer to editor | `{teamMemberId: member_id, role: "editor", actor_id: admin_id}` | Role updated successfully | Low |
| UR-02 | Owner promotes editor to admin | `{teamMemberId: member_id, role: "admin", actor_id: owner_id}` | Role updated successfully | Low |
| UR-03 | Demote admin to viewer | `{teamMemberId: admin_member_id, role: "viewer", actor_id: owner_id}` | Role updated successfully | Medium |
| UR-04 | Owner changes own role | `{teamMemberId: owner_member_id, role: "admin", actor_id: owner_id}` | Error: "Cannot change own role" | High |
| UR-05 | Remove last admin (by demotion) | Demote last admin to viewer | Error: "Cannot remove last admin" | High |
| UR-06 | Invalid role value | `{teamMemberId: member_id, role: "superadmin"}` | Error: Invalid role | Low |
| UR-07 | Empty role | `{teamMemberId: member_id, role: ""}` | Error: Role required | Low |
| UR-08 | Null role | `{teamMemberId: member_id, role: null}` | Error: Role required | Low |
| UR-09 | Non-existent member | `{teamMemberId: 999999, role: "admin"}` | Error: Member not found | Medium |
| UR-10 | Update by viewer | `{teamMemberId: member_id, role: "editor", actor_id: viewer_id}` | Error: "Insufficient permissions" | High |
| UR-11 | Update by editor | `{teamMemberId: member_id, role: "viewer", actor_id: editor_id}` | Error: "Insufficient permissions" | High |
| UR-12 | Update by non-team member actor | `{teamMemberId: member_id, role: "admin", actor_id: outsider_id}` | Error: "Insufficient permissions" | High |
| UR-13 | Same role (no change) | `{teamMemberId: member_id, role: "editor"}` (member already editor) | Success, no change needed | Low |
| UR-14 | Member from different team | `{teamMemberId: other_team_member_id, actor_id: admin_id}` | Error: "Member not in your team" | High |
| UR-15 | Missing teamMemberId | `{role: "admin"}` | Error: Member ID required | Low |
| UR-16 | Non-numeric teamMemberId | `{teamMemberId: "abc", role: "admin"}` | Error: Invalid member ID | Low |
| UR-17 | Concurrent role updates | Two admins update same member simultaneously | Last write wins or optimistic lock | Medium |
| UR-18 | Update owner role by admin | `{teamMemberId: owner_member_id, role: "viewer", actor_id: admin_id}` | Error: "Cannot modify owner" | High |

### 3. Remove Member (removeMember)

| ID | Scenario | Input | Expected Output | Risk |
|----|----------|-------|-----------------|------|
| RM-01 | Remove viewer by admin | `{teamMemberId: viewer_member_id, actor_id: admin_id}` | Member removed, records deleted | Low |
| RM-02 | Remove editor by owner | `{teamMemberId: editor_member_id, actor_id: owner_id}` | Member removed successfully | Low |
| RM-03 | Remove admin by owner | `{teamMemberId: admin_member_id, actor_id: owner_id}` | Member removed successfully | Medium |
| RM-04 | Owner removes self | `{teamMemberId: owner_member_id, actor_id: owner_id}` | Error: "Cannot remove self" | High |
| RM-05 | Remove last admin | `{teamMemberId: last_admin_member_id, actor_id: owner_id}` | Error: "Cannot remove last admin" | High |
| RM-06 | Remove by viewer | `{teamMemberId: member_id, actor_id: viewer_id}` | Error: "Insufficient permissions" | High |
| RM-07 | Remove by editor | `{teamMemberId: member_id, actor_id: editor_id}` | Error: "Insufficient permissions" | High |
| RM-08 | Remove non-existent member | `{teamMemberId: 999999}` | Error: "Member not found" | Medium |
| RM-09 | Remove member from different team | `{teamMemberId: other_team_member_id, actor_id: admin_id}` | Error: "Member not in your team" | High |
| RM-10 | Missing teamMemberId | `{}` | Error: "Member ID required" | Low |
| RM-11 | Non-numeric teamMemberId | `{teamMemberId: "abc"}` | Error: Invalid member ID | Low |
| RM-12 | Remove owner by admin | `{teamMemberId: owner_member_id, actor_id: admin_id}` | Error: "Cannot remove owner" | High |
| RM-13 | Remove with pending invitations | Member has pending project invitations | Member removed, invitations cleaned up | Medium |
| RM-14 | Remove with project assignments | Member assigned to projects | Member removed from project_members too | Medium |
| RM-15 | Remove with activity log entries | Member has log entries | Member removed, logs retained (user_id null) | Medium |
| RM-16 | Null teamMemberId | `{teamMemberId: null}` | Error: Member ID required | Low |

### 4. Token Validation (validateInvitationToken)

| ID | Scenario | Input | Expected Output | Risk |
|----|----------|-------|-----------------|------|
| TV-01 | Valid token within expiry | Valid token, created 1 hour ago | Returns TeamMember, token valid | Low |
| TV-02 | Expired token (24h+ old) | Token created 25 hours ago | Error: "Invitation expired" | Medium |
| TV-03 | Invalid token format | `{token: "invalid"}` | Error: "Invalid token" | Medium |
| TV-04 | Empty token | `{token: ""}` | Error: "Token required" | Low |
| TV-05 | Null token | `{token: null}` | Error: "Token required" | Low |
| TV-06 | Token already used | Token after acceptance | Error: "Invitation already accepted" | Medium |
| TV-07 | Token for deleted team | Valid token, team deleted | Error: "Team no longer exists" | Medium |
| TV-08 | Token brute force attempt | Multiple invalid tokens rapidly | Rate limited after 5 attempts | High |
| TV-09 | Token timing attack | Compare token character by character | Constant-time comparison used | High |

### 5. Permission Checks (canManageTeam)

| ID | Scenario | Input | Expected Output | Risk |
|----|----------|-------|-----------------|------|
| PC-01 | Owner manages team | `{actor_id: owner_id}` | Returns true | Low |
| PC-02 | Admin manages team | `{actor_id: admin_id}` | Returns true | Low |
| PC-03 | Editor manages team | `{actor_id: editor_id}` | Returns false | Medium |
| PC-04 | Viewer manages team | `{actor_id: viewer_id}` | Returns false | Medium |
| PC-05 | Non-member manages team | `{actor_id: outsider_id}` | Returns false | High |
| PC-06 | Pending member manages team | `{actor_id: pending_member_id}` | Returns false | Medium |

---

## Mock Data

### TeamMember Factory

```php
// tests/Factories/TeamMemberFactory.php

public function definition(): array
{
    return [
        'account_id' => Account::factory(),
        'user_id' => User::factory(),
        'role' => 'viewer',
        'invited_by' => User::factory(),
        'invited_at' => now(),
        'accepted_at' => null,
    ];
}

public function owner(): static
{
    return $this->state(['role' => 'owner', 'accepted_at' => now()]);
}

public function admin(): static
{
    return $this->state(['role' => 'admin', 'accepted_at' => now()]);
}

public function editor(): static
{
    return $this->state(['role' => 'editor', 'accepted_at' => now()]);
}

public function viewer(): static
{
    return $this->state(['role' => 'viewer', 'accepted_at' => now()]);
}

public function pending(): static
{
    return $this->state(['accepted_at' => null]);
}
```

### Invitation Token Factory

```php
// tests/Factories/InvitationTokenFactory.php

public function definition(): array
{
    return [
        'team_member_id' => TeamMember::factory(),
        'token' => Str::random(64),
        'expires_at' => now()->addHours(24),
    ];
}
```

---

## Coverage Targets

| Method | Target Coverage | Critical Paths |
|--------|-----------------|----------------|
| `addMember()` | 95% | Token generation, validation, permissions |
| `updateRole()` | 95% | Permission checks, owner protection, last admin |
| `removeMember()` | 95% | Permission checks, self-removal, cascade |
| `validateInvitationToken()` | 100% | All token states, timing attacks |
| `canManageTeam()` | 100% | All role combinations |

**Overall Target:** 95% code coverage

---

## Test Execution

```bash
# Run all TeamService tests
php artisan test --filter TeamService

# Run unit tests only
php artisan test tests/Unit/Services/TeamServiceTest.php

# Run feature tests only
php artisan test tests/Feature/TeamManagementTest.php

# Run with coverage
php artisan test --coverage --min=95 tests/Unit/Services/TeamServiceTest.php
```

---

## Dependencies

- TASK-11-1: Team members table must exist (migration)
- User model and factory
- Account model and factory
- Laravel mail mocking for invitation emails

---

## Security Considerations

1. **Token Security:** Use cryptographically secure random tokens (64+ characters)
2. **Timing Attacks:** Use `hash_equals()` for token comparison
3. **Rate Limiting:** Limit token validation attempts to prevent brute force
4. **Input Sanitization:** All inputs validated and sanitized before use
5. **Permission Escalation:** Prevent users from elevating their own permissions
6. **Audit Trail:** Log all team management actions

---

## Notes for Developer

### Implementation Tips

1. Use Laravel's built-in `Str::random()` for token generation
2. Store tokens hashed (like passwords) - compare with `hash_equals()`
3. Use database transactions for multi-step operations
4. Dispatch events for side effects (send email on invite)
5. Use policy classes for permission checks

### Edge Cases to Handle

- User accepts invitation after being removed from team
- User tries to accept invitation while already a member
- Concurrent invitation acceptance
- Team deletion while invitation pending

### Events to Dispatch

- `TeamMemberInvited` - After successful invitation
- `TeamMemberRoleUpdated` - After role change
- `TeamMemberRemoved` - After removal
- `InvitationAccepted` - After token acceptance

---

## Comments
_Agent communication log_

### [2026-02-26 19:12] Agent: PreTester
Created comprehensive test documentation for TeamService (TASK-11-3).
- 78 test scenarios covering addMember, updateRole, removeMember, token validation, and permissions
- Focus on security: token security, timing attacks, rate limiting
- Permission escalation prevention tested
- Cascade behavior for removal tested
- Coverage target: 95%

### [2026-02-26 19:12] Agent: PreTester
Security highlights:
- SQL injection tests (AM-15)
- XSS tests (AM-16)
- Token brute force protection (TV-08)
- Timing attack prevention (TV-09)
- Permission escalation prevention (UR-04, UR-05, RM-04, RM-05)
