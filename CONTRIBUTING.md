# Contributing

Thanks for looking at SEOIndicator. This is a solo-maintained project, but
issues and pull requests are welcome.

## Before you start

- **Open an issue first** for anything larger than a bug fix, so we can agree
  on the approach before you spend time on it.
- For a bug, please include the URL that triggered it, what you expected, and
  what happened instead.

## Setting up locally

See [README.md → Quick start](README.md#quick-start). In short:

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

cd ../frontend
npm install
npm run dev
```

For the E2E suite you also need the app served (the root `playwright.config.js`
points at `http://localhost` by default).

## Secrets

**Never commit a real credential.** This is the single most important rule
in this repository.

- Use `backend/.env.example` and `config.php.example` / `db_config.php.example`
  as templates; the real files are gitignored.
- If you need to add a new setting, add it to the `.example` file with an
  empty or placeholder value, and to the relevant service.
- GitHub secret scanning and push protection are enabled — a leaked key will
  be blocked at push time and flagged in the repository's Security tab.

## Tests

The project is test-first. The expectation is that a change lands with a test
that fails without it.

```bash
# Frontend unit / component tests
cd frontend && npm test

# Backend tests
cd backend && ./vendor/bin/phpunit

# E2E
npx playwright test
npx playwright test --headed --reporter=list   # watch it run
BASE_URL=https://seoindicator.com npx playwright test   # against production
```

### Conventions

- **Frontend**: Vitest. Test files live in `frontend/__tests__/` (mirroring
  the source tree) and `frontend/components/**/__tests__/`.
- **Backend**: PHPUnit. Feature tests in `backend/tests/Feature/`, unit tests
  in `backend/tests/Unit/`.
- **E2E**: Playwright, in the root `tests/` directory. Keep specs focused —
  one behaviour per spec.
- The HTML report lands in `local-test-results/` (local) or
  `prod-test-results/` (production). Both are gitignored.

## Working on an indicator

New indicators are added in five places. Keep them in sync:

1. the static check logic (`backend/app/Services/SEO/Checkers/` or
   `backend/app/Services/Audit/Checkers/`),
2. the AI prompt (`backend/app/Services/AI/Prompts/`),
3. the API response shape and score calculation,
4. the guide page (`seo-guides/`),
5. the automated test.

## Commit messages

Conventional-ish, imperative, and specific about *why*:

```
feat(indicators): add manifest validator for PWA checks
fix(auth): keep public route list in sync between middleware and provider
docs(readme): document the deployment secrets file location
```

## Style

- Match the surrounding code. The project has no formatter config; do not
  reformat unrelated lines.
- Keep comments for the *why*, not the *what*.
- Do not reformat or reorganise files you are not otherwise changing.

## Pull requests

1. Branch from `main`.
2. Make the change with a test.
3. Run the relevant suites locally.
4. Update `ROADMAP.md` if the change affects planned work.
5. Open the PR with a short description of what changed and why.

## Security issues

Do not open a public issue for a vulnerability. See
[SECURITY.md](SECURITY.md).

## License

By contributing, you agree that your contributions are licensed under the
MIT License. See [LICENSE](LICENSE).
