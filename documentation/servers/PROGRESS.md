# Progress

## Current
ERP-009 and ERP-010 MVP completed; ERP-008 rollups, ERP-011 project detail and ERP-012 alert evaluation remain.

## Done
- ERP-001 module boundary under `app/Domain/Servers`.
- ERP-002 migration and Eloquent models for hosts, agents, projects, samples, buckets, storage, events and ingest batches.
- ERP-003 Artisan registry commands for hosts, agents and projects.
- ERP-004 versioned config endpoint.
- ERP-005 batch ingest endpoint with validation, transaction and idempotency.
- ERP-006 heartbeat endpoint.
- ERP-007 dedicated token and loopback middleware.
- ERP-010 global admin dashboard MVP with stale agent status and project summary.

## Blocked
- S-001 onward requires an authenticated Remote SSH session to the Debian/GCP server.
- ERP-011 project detail dashboard and ERP-012 alert evaluation remain after the first persisted sample is available.

## Last validation
Date: 2026-08-14
Tests: `php artisan test --filter=servers_api_test` passed with 8 tests; Laravel Mix production build passed.

## Notes
No production system files or secrets have been changed from this workspace.
