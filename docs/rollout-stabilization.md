# Rollout Stabilization

## Purpose

This document defines the rollout stabilization process for the portal before any new feature work resumes. The goal is to make production issues reproducible, diagnosable, and fixable with a clear release discipline.

## Severity Model

| Severity | Meaning | Examples |
| --- | --- | --- |
| Blocker | Cannot safely roll out or continue operating a critical flow | login failure, upload failure, credit corruption, repeated 500s |
| High | Serious workflow breakage or access issue, but partial operation remains possible | illegal order transition, broken admin action, wrong redirect by role |
| Medium | Incorrect or misleading behavior without immediate data loss or access breakage | inconsistent counters, stale dashboard data, misleading status labels |
| Low | Cosmetic, copy, encoding, or non-critical UX issues | placeholder issues, minor layout inconsistencies |

## Critical Flows

- Client login and dashboard access
- Vendor login and verified-only dashboard access
- Admin login and verified-only dashboard access
- Session timeout, logout, and forced re-login
- Client upload from dashboard and public upload link
- Multi-file credit deduction and restoration
- Vendor claim, unclaim, start processing, upload reports, deliver
- Client order deletion restrictions
- Admin account freeze, unfreeze, delete, restore, and force delete
- Admin dashboard loading and active-order visibility
- Client report download and public tracking links
- Mobile standby and resume on client-facing pages

## Release Blockers

- Any blocker-severity bug remains open in a critical flow
- Unknown or uncorrelated 500 errors in auth, upload, order, or admin flows
- Credit usage/restoration disagrees with stored account totals
- Order lifecycle transitions are inconsistent across controller, policy, and service layers
- Role-based redirects or access protections are inconsistent
- A production issue cannot be traced to a request ID, route, and affected user

## Rollout Batch Plan

### Batch 1: Observability

- Add request correlation IDs
- Add request start/finish logs
- Add rollout docs and bug register template

### Batch 2: Reproducibility

- Add manual QA checklists for critical flows
- Capture enough route/user/request context to diagnose reported issues quickly

### Batch 3: Critical Flow Regression Coverage

- Add regression tests for auth, lifecycle, credit accounting, and session safety

### Batch 4: Business Action Diagnostics

- Add audit records and denial diagnostics for high-risk actions

### Batch 5: Production/Environment Hooks

- Wire non-production debugging tools and production exception tracking if available in the repo

## Exit Criteria

- No open blocker or high-severity issues in the critical flows
- Every request includes a correlation ID in logs and response headers
- Manual QA for critical flows passes in staging
- Rollout bug reports can be tied to a route, role, user, and request ID
- Regression coverage exists for the highest-risk auth and order flows
- Stabilization docs are current and usable by engineering or QA

## Scope Boundaries

- Do not add product features during stabilization
- Prefer additive, low-risk changes
- Do not change business rules unless required to make the app observable, testable, or correct
- Do not log secrets, tokens, passwords, raw session data, or uploaded document contents

## Local And Staging Notes

- If Telescope is added later, keep it non-production only
- If Sentry is added later, attach request ID, user ID, role, and route name to exceptions
- Phase 1 and Phase 2 intentionally stop at docs plus request correlation
