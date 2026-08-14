# Observability Data Contract v1

Base path: `/api/internal/observability/v1`.

Required authentication headers:

- `X-Opzio-Observer-Token`
- `X-Opzio-Observer-Agent` for config requests

All timestamps are UTC. The agent sends batches, never one row per HTTP request.

## Config

`GET /config` returns the active projects assigned to the registered agent host, including NGINX log paths, PHP-FPM status URL, pool and attribution mode.

## Ingest

`POST /ingest` accepts `agent_id`, `batch_id`, `captured_at`, an optional host sample, project samples, one-minute HTTP buckets, storage samples and events. The ERP validates every project key against the active registry and responds `202` after a short transaction. Reusing a `batch_id` with a different payload returns `409`.

## Heartbeat

`POST /heartbeat` updates version, commit SHA, config version, uptime, spool size/count and isolated collector errors. The ERP responds `204`.

Payload limits and loopback enforcement are configured with `OPZIO_OBSERVER_MAX_PAYLOAD_BYTES` and `OPZIO_OBSERVER_LOOPBACK_ONLY`.
