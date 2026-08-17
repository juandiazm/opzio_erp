# Server Commands

Run only through an authenticated VS Code Remote SSH terminal on the Debian production VM. Do not run these commands from the local Windows workspace.

## S-001 baseline

```bash
hostnamectl
cat /etc/os-release
uname -a
nginx -v
sudo nginx -T > /tmp/nginx-full-before-servers.txt
php -v
find /etc/php -path '*/fpm/pool.d/*.conf' -type f -print
systemctl list-units --type=service | grep -E 'nginx|php|supervisor|redis'
sudo supervisorctl status || true
find /var/www/html -mindepth 1 -maxdepth 1 -type d -printf '%f\n'
stat -fc %T /sys/fs/cgroup
mount | grep cgroup
cat /proc/cgroups
free -h
df -h
```

Record a sanitized result in `server-baseline.md` and update `SERVER_CHANGELOG.md` before changing files.

## S-002 to S-004 agent directories

```bash
sudo useradd --system --no-create-home --shell /usr/sbin/nologin opzio-observer
sudo install -d -o root -g root /opt/opzio-observer/releases
sudo install -d -o root -g opzio-observer -m 0750 /etc/opzio-observer
sudo install -d -o opzio-observer -g opzio-observer /var/lib/opzio-observer/spool
sudo install -d -o opzio-observer -g opzio-observer /var/log/opzio-observer
sudo apt update
sudo apt install -y curl jq acl ca-certificates
```

Use `id opzio-observer` and `sudo -u opzio-observer` read checks before installing the service.

## Security constraints

- Do not use `chmod -R 777` on logs, application or agent directories.
- Do not expose ports `9080` or `9091` on `0.0.0.0`.
- Do not put the observer token in Git.
- Do not install Go on production.
- Do not modify production NGINX/PHP-FPM before recording a backup and validating with `nginx -t`.
