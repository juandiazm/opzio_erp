# Server Bootstrap Boundary

The production VM receives two different kinds of changes:

1. **System changes through authenticated Remote SSH:** NGINX JSON logs, PHP-FPM local status, read-only permissions, the `opzio-observer` user, systemd unit, token file and release installer.
2. **Application/agent code through CI/CD:** Laravel deploy remains in the existing `ridder_erp` workflow; the Go agent has its own workflow and uploads only a compiled Linux binary to a release directory.

Do not upload the Go source tree to the VM. Do not run Go on production. Do not place the ERP token or observer token in either repository.

The first remote session must execute only S-001 baseline commands and record their sanitized output in `SERVER_CHANGELOG.md` and `server-baseline.md`. No password should be sent through chat; enter it directly in the VS Code Remote SSH prompt.
