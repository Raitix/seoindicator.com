# DEPLOYMENT-SETUP.md

## Environments

| Environment | URL | Purpose |
|-------------|-----|---------|
| Development | _local_ | Local development |
| Staging | _(to be defined)_ | Pre-production testing |
| Production | `https://seoindicator.com` | Live environment |

---

## Deployment Process

### Pre-deployment Checklist
- [ ] All tests passing
- [ ] Documentation updated
- [ ] CHANGELOG updated
- [ ] Environment variables configured
- [ ] Database migrations ready (if any)

### Deployment Steps

1. **Merge PR** to main branch
2. **Run tests** in CI pipeline
3. **Build** production artifacts
4. **Deploy to staging** for verification
5. **Deploy to production**
6. **Verify deployment** — smoke tests
7. **Monitor** for errors

---

## Rollback Procedure
1. Identify issue
2. Revert commit or deploy previous version
3. Verify rollback successful
4. Document incident

---

## Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `NODE_ENV` | Environment mode | Yes |
| `DATABASE_URL` | Database connection | Yes |
| `API_KEY` | External API key | Yes |

_(Add project-specific variables)_

---

## Deployment Commands

```bash
# Build
npm run build

# Deploy to staging
npm run deploy:staging

# Deploy to production
npm run deploy:prod

# Rollback
npm run rollback
```

---

Last updated: 2026-02-17
