# PreTester.md — Persistent Memory

Agent-specific memory across all projects.

---

## Test Patterns

_(Effective test design patterns)_

---

## Adversarial Testing Checklist

**Input Validation:**
- [ ] Empty/null inputs
- [ ] Oversized inputs (buffer overflow attempts)
- [ ] Special characters, Unicode, escape sequences
- [ ] SQL/HTML/JS injection attempts
- [ ] Type mismatches

**Boundary Testing:**
- [ ] Off-by-one errors
- [ ] Integer overflow/underflow
- [ ] Maximum limits (file size, request size)
- [ ] Rate limiting bypass attempts

**Security Testing:**
- [ ] Authentication bypass
- [ ] Authorization holes (IDOR)
- [ ] Data exposure (logs, errors)
- [ ] CSRF/XSS vulnerabilities
- [ ] Session handling flaws

**Concurrency:**
- [ ] Race conditions
- [ ] Deadlock scenarios
- [ ] Resource exhaustion

**Error Handling:**
- [ ] Unhandled exceptions
- [ ] Information leakage in errors
- [ ] Graceful degradation

---

## Edge Case Library

_(Edge cases to remember)_

---

## Lessons Learned

### General
_(Cross-project lessons)_

---

Last updated: 2026-02-18
