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
- Production-ready code only
- Protected from unstable code
- Production tests ensure stability

### Feature Branches
- Created from `main`
- Named: `feature/FEAT-X` or `feat/short-description`
- One feature per branch
- Deleted after deployment

### Bugfix Branches
- Created from `main` or `feature` branch
- Named: `bugfix/short-description`
- Include test for the fix

### Hotfix Branches
- Created from `main`
- Named: `hotfix/short-description`
- Merged ASAP with expedited review

---

## Commit Rules

### No Merge Requests Required
- Production tests cover stability
- Direct commits to branches allowed
- No code review process

### Commit Messages

Format: `type(scope): description`

Types:
- `feat` — New feature
- `fix` — Bug fix
- `docs` — Documentation
- `refactor` — Code refactoring
- `test` — Adding tests
- `chore` — Maintenance tasks

Example: `feat(seo-analysis): add keyword density calculator`

---

## Workflow

1. Create branch from `main`
2. Implement feature with tests
3. Run local tests → pass
4. Deploy → prod tests validate
5. Delete branch after deployment

---

## Branch Lifecycle

```
main → feature/FEAT-X → develop → test → deploy → delete branch
```
