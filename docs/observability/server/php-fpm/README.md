# PHP-FPM pilot

Do not copy this file directly into production. First inspect the real PHP version, pool names and sockets during S-001.

For one pilot project, configure a dedicated pool status path such as:

```ini
pm.status_path = /__fpm_status/project
ping.path = /__fpm_ping/project
```

Expose it only through an NGINX server listening on `127.0.0.1:9091`. The ERP registry stores the resulting local URL and pool name. Do not expose the status endpoint publicly and do not change all pools in one deployment.
