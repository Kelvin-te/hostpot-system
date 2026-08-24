# FreeRADIUS Dynamic SQL Clients — Design

## Objective

Make FreeRADIUS treat every row in `wingufi_core.radius_nas` as a valid RADIUS
client, so that adding/updating a router in the company app (which already
upserts this table via `POST /api/v1/routers`) is sufficient to authorize that
router's WireGuard IP to send Access-Requests — with no manual `clients.conf`
editing.

This is server-side FreeRADIUS configuration. It does not require any new
Laravel code for the client lookup itself (WinguFi Core already writes the
correct row). It does require one small addition to actually *reload*
FreeRADIUS after a change (see §5).

---

## 1. Why SQL dynamic clients instead of generated `clients.conf`

| | SQL clients (`read_clients`) | Generated `clients.conf` |
|---|---|---|
| New Laravel/SSH code required | None for lookup | File templating + SSH/exec + reload |
| Existing infra reused | `wingufi_core.radius_nas` (already populated) | Would need to build export logic |
| Reload needed on change | Yes (see §5) | Yes |
| Risk | Low — read-only SQL query | Higher — remote file write to `/etc/freeradius` |

Given neither app has any SSH/exec/job infrastructure today (confirmed by
codebase inspection), SQL clients is the lower-effort, lower-risk option.

---

## 2. Prerequisites

FreeRADIUS and WinguFi Core already run on the same Ubuntu host
(`MikroTik RouterOS 7 Upgrade & WireGuard VPN to WinguFi - FreeRADIUS Server.md`).

```bash
sudo apt install freeradius-mysql
```

Confirm the `rlm_sql_mysql` driver is available:

```bash
sudo freeradius -XC 2>&1 | grep -i sql
```

---

## 3. The shared-secret problem (decision required)

`radius_nas.radius_secret_encrypted` is stored using Laravel's `Crypt`
facade (AES-256-CBC, keyed by `wingufi-core`'s `APP_KEY`, base64 JSON
envelope). **FreeRADIUS's SQL client loader cannot decrypt this format** —
it expects a plain `secret` column, because the RADIUS protocol needs the
raw shared secret to validate/derive `User-Password` and the Request
Authenticator.

This is not a bug in the current design — `radius_secret_encrypted`
correctly protects the value from being leaked via WinguFi Core's own API
responses/logs. It just isn't the value FreeRADIUS itself can consume.

**Implemented:** a `radius_secret_plain` column (nullable `TEXT`) was added via
`2024_01_01_000012_add_radius_secret_plain_to_radius_nas_table.php` and is
now populated alongside `radius_secret_encrypted` in
`Api\V1\RouterController::store()`. It is added to `RadiusNas::$hidden`
(along with `radius_secret_encrypted`) so it is never serialized in any API
response, and the controller's explicit response array never includes it
either. Run `php artisan migrate` on WinguFi Core to apply it.

- Restrict it further at the database level (see §4) so that only
  FreeRADIUS's MySQL user can `SELECT` it — the application's normal MySQL
  user does not strictly need column-level restriction since `$hidden`
  already prevents accidental exposure, but DB-level grants are recommended
  as defense in depth.

This mirrors how FreeRADIUS's own default schema (`nas.secret`) has always
worked — the secret must be recoverable in plaintext by the RADIUS server
by design; the goal is to *scope* that exposure, not encrypt it away
entirely.

---

## 4. Database user for FreeRADIUS (least privilege)

Create a dedicated MySQL user, distinct from the Laravel app's DB user,
with read-only access scoped to exactly what FreeRADIUS needs:

```sql
CREATE USER 'freeradius'@'localhost' IDENTIFIED BY '<STRONG_PASSWORD>';

GRANT SELECT ON wingufi_core.radius_nas TO 'freeradius'@'localhost';
GRANT SELECT ON wingufi_core.network_clients TO 'freeradius'@'localhost';
GRANT SELECT ON wingufi_core.network_authorizations TO 'freeradius'@'localhost';
GRANT SELECT, INSERT, UPDATE ON wingufi_core.radius_sessions TO 'freeradius'@'localhost';
GRANT SELECT, INSERT, UPDATE ON wingufi_core.radius_accounting TO 'freeradius'@'localhost';
GRANT SELECT, INSERT ON wingufi_core.radius_auth_logs TO 'freeradius'@'localhost';

FLUSH PRIVILEGES;
```

Restrict to `localhost` since FreeRADIUS and the DB are co-located. If the
DB is remote, scope the grant to the FreeRADIUS server's WireGuard IP
instead of `%`.

---

## 5. `mods-available/sql` configuration

Edit (or create an override in) `/etc/freeradius/3.0/mods-available/sql`:

```conf
sql {
    driver = "rlm_sql_mysql"
    dialect = "mysql"

    server   = "127.0.0.1"
    port     = 3306
    login    = "freeradius"
    password = "<STRONG_PASSWORD>"          # from a FreeRADIUS-only secrets file, mode 600
    radius_db = "wingufi_core"

    # --- Dynamic SQL clients ---
    read_clients = yes
    client_table = "radius_nas"

    ...
}
```

Enable the module:

```bash
sudo ln -s ../mods-available/sql /etc/freeradius/3.0/mods-enabled/sql
```

### Column mapping

FreeRADIUS's built-in SQL client query expects specific column names
(`nasname`, `shortname`, `type`, `secret`, `server`, `community`,
`description`). `radius_nas` already has `nasname`, `shortname`, `type`,
`description` with matching names. It does **not** have a plaintext
`secret` column (see §3) — this must be added, or FreeRADIUS's default
client-loading query must be overridden in `queries.conf` to select
`radius_secret_plain AS secret`. Overriding the query is preferable to
renaming the column, since `network_clients`/other consumers should not be
affected.

Add to `mods-config/sql/main/mysql/queries.conf` (or an override file) if
the default query needs adjusting:

```sql
client_query = "\
    SELECT id, nasname, shortname, type, secret, server, description \
    FROM ( \
        SELECT id, nasname, shortname, type, \
               radius_secret_plain AS secret, \
               NULL AS server, description \
        FROM radius_nas \
        WHERE status = 'active' AND deleted_at IS NULL \
    ) clients"
```

**Only `status = 'active'` and non-soft-deleted rows should be loaded** —
this matches `radius_migrations_plan.md`'s requirement that soft-deleted
records must never be treated as live by FreeRADIUS.

---

## 6. Reload trigger — the remaining gap

FreeRADIUS's SQL client loader reads `radius_nas` **at startup**, and (in
FreeRADIUS ≥ 3.0.20) again on a `SIGHUP`/`systemctl reload`. It does **not**
poll continuously. So "automatic" provisioning still needs something to
signal a reload after WinguFi Core's `RadiusNas::updateOrCreate()` runs.

Two options, in increasing order of immediacy/complexity:

**A. Scheduled reload (simplest, no new Laravel privileges)**

A cron job on the FreeRADIUS server, independent of Laravel:

```cron
* * * * * root /usr/bin/systemctl reload freeradius
```

or, to avoid unnecessary reloads, gate it on `radius_nas.updated_at`:

```bash
#!/usr/bin/env bash
LAST=$(cat /var/lib/wingufi/last_client_reload 2>/dev/null || echo 0)
LATEST=$(mysql -N -B -ufreeradius -p"$PW" wingufi_core \
  -e "SELECT UNIX_TIMESTAMP(MAX(updated_at)) FROM radius_nas")
if [ "$LATEST" -gt "$LAST" ]; then
  systemctl reload freeradius
  echo "$LATEST" > /var/lib/wingufi/last_client_reload
fi
```
Run every 30–60s via cron. Propagation delay ≤ 1 minute; zero new Laravel
code or sudo grants.

**B. Immediate reload from WinguFi Core (requires new code — not yet built)**

A narrowly-scoped sudoers rule:

```
www-data ALL=(root) NOPASSWD: /bin/systemctl reload freeradius
```

and a dispatched job in `Api\V1\RouterController::store()` (WinguFi Core)
running `Illuminate\Support\Facades\Process::run('sudo systemctl reload freeradius')`.
This is the first place either app would use process execution — new
attack surface, so it should be queued (not inline in the HTTP request) and
rate-limited/debounced so a burst of router syncs doesn't hammer
`systemctl reload`.

**Recommendation:** start with Option A (cron-based, zero new app code,
~30s propagation is acceptable for router onboarding). Revisit Option B
only if sub-minute propagation becomes a real requirement.

---

## 7. Verification checklist

- [ ] `freeradius-mysql` installed
- [ ] `freeradius` MySQL user created with least-privilege grants (§4)
- [x] `radius_secret_plain` column added and populated (§3)
- [ ] `mods-enabled/sql` symlinked, `read_clients = yes`, `client_table = "radius_nas"`
- [ ] `client_query` overridden to filter `status = 'active' AND deleted_at IS NULL`
- [ ] `sudo freeradius -X` shows the router's WireGuard IP loaded as a client on startup
- [ ] Reload mechanism in place (cron, §6 Option A) and tested: add a router → wait for reload → confirm `/radius print detail` on the MikroTik + a real Access-Request succeeds without editing `clients.conf`
- [ ] Deactivating/deleting a router in the company app removes it as a valid client after the next reload

---

## 8. Summary of what's still needed to fully close this out

1. ~~Code (WinguFi Core): migration + controller change to add/populate
   `radius_secret_plain`~~ — **done**.
2. **Server (FreeRADIUS host)**: MySQL user (§4), `mods-available/sql`
   config + `client_query` override (§5), cron reload script (§6) — pure
   ops, no application code, must be applied on the actual Ubuntu host
   (not reachable from this workspace).
3. Already done: company app's `RouterController::update()` re-syncs
   `radius_nas.nasname`/secret whenever a router's IP changes, so the
   source-of-truth table stays current for whichever reload mechanism is
   chosen.
