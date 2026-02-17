# GIT-BRANCHING-STRATEGY.md

## Branch Types

| Branch Type | Pattern | Purpose |
|-------------|---------|---------|
| `main` | `main` | Production-ready code |
| `feature` | `feature/FEAT-X` or `feat/X-short-desc` | New feature development |
| `bugfix` | `bugfix/X-short-desc` | Bug fixes |
| `hotfix` | `hotfix/X-short-desc` | Urgent production fixes |
| `release` | `release/vX.Y.Z` | Release preparation |

---

## Branch Rules

### Main Branch
- Protected — no direct commits
- Requires PR approval
- Must pass all tests

### Feature Branches
- Created from `main`
- Named: `feature/FEAT-X` or `feat/short-description`
- One feature per branch
- Deleted after merge

### Bugfix Branches
- Created from `main` or `feature` branch
- Named: `bugfix/short-description`
- Include test for the fix

### Hotfix Branches
- Created from `main`
- Named: `hotfix/short-description`
- Merged ASAP with expedited review

---

## Merge Rules

1. **PR required** for all merges to main
2. **Squash merge** preferred for feature branches
3. **Rebase** feature branch before PR if needed
4. **Delete branch** after merge

---

## Commit Messages

Format: `type(scope): description`

Types:
- `feat` — New feature
- `fix` — Bug fix
- `docs` — Documentation
- `refactor` — Code refactoring
- `test` — Adding tests
- `chore` — Maintenance tasks

Example: `feat(seo-analysis): add keyword density calculator`
