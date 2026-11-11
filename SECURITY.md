# Security Policy

## Reporting a vulnerability

Please report security issues **privately** rather than opening a public
issue.

Use [GitHub's private vulnerability reporting](https://github.com/Raitix/seoindicator.com/security/advisories/new)
on this repository, or email **raioz@inbox.lv**.

Please include:

- what the issue is and what an attacker could do with it,
- the steps to reproduce,
- the affected version or commit,
- any suggested mitigation.

You can expect an acknowledgement within a few days. Once a fix is ready I
will open a public advisory, and credit you in it unless you would prefer
otherwise.

## Scope

In scope:

- the web application at https://seoindicator.com,
- the API at `/api/v1/...`,
- authentication, authorisation and session handling,
- the analysis pipeline and its external API integrations,
- credential handling and deployment configuration.

Out of scope:

- findings that require an already-compromised account,
- self-XSS, missing security headers with no demonstrable impact, or
  rate-limit gaps that do not lead to resource exhaustion,
- vulnerabilities in upstream dependencies with no viable mitigation in this
  codebase — please report those to the upstream project instead,
- the behaviour of third-party services (OpenRouter, Vercel, Stripe, PayPal,
  Google) beyond how this application integrates with them.

## Secrets

**Never commit a credential to this repository.** This is the rule most likely
to be broken by accident, so the repository is set up to catch it:

- `config.php`, `db_config.php`, `.env` and `secrets/` are gitignored.
- `config.php.example`, `db_config.php.example` and `backend/.env.example`
  hold placeholders only.
- GitHub secret scanning **and push protection** are enabled, so most
  credentials are blocked at push time.

The deployment secrets file (`../secrets/seoindicator.env`, outside the
repository) holds the SSH and GitHub credentials used by the deploy script. It
must never be copied into the repository.

If you find a credential that was committed — including in history — do not
open a public issue. Report it privately so it can be revoked and purged
promptly.

## Running the project safely

- Use SQLite for local development where possible, so no shared database is
  needed.
- The crawler fetches URLs you give it. Do not point it at internal or
  private network ranges; there is an SSRF guard in the domain validator, but
  treat it as a defence in depth rather than a guarantee.
- Keep `APP_DEBUG=false` and `APP_ENV=production` in any shared environment.
