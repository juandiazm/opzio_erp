# Servers Plan

The canonical architecture and phased plan is maintained in [documentation/PLAN_SERVIDORES_OPZIO_ERP.md](../../documentation/PLAN_SERVIDORES_OPZIO_ERP.md).

This module owns the ERP-* responsibilities only:

- Registry of hosts, agents and projects.
- Versioned configuration response for agents.
- Authenticated loopback-only ingest and heartbeat APIs.
- Canonical persistence and idempotency by `batch_id`.
- Future rollups, dashboards and alert evaluation.

Linux collectors and system configuration belong to `opzio_observer_agent` and the production server, not to Laravel requests.
