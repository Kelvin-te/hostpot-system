# FreeRADIUS SQL Accounting — Design

## Objective

Have FreeRADIUS write real Accounting-Request data (Start / Interim-Update /
Stop) directly into `wingufi_core.radius_sessions` and
`wingufi_core.radius_accounting`, so the company app can query live and
historical session data via `GET /api/v1/sessions` and
`GET /api/v1/accounting` (implemented — see `app/Http/Controllers/Api/V1/SessionController.php`
and `AccountingController.php`).

This complements `FREERADIUS_SQL_CLIENTS.md` (dynamic clients). Same server,
same `sql` module instance, same `freeradius` MySQL user — just the
`accounting` query block instead of `client_table`.

---

## 1. Resolving tenant_id / nas_id / client_id at accounting time

An Accounting-Request only carries `NAS-IP-Address`, `User-Name`,
`Acct-Session-Id`, etc. — it doesn't know our `tenant_id`. Resolve it via a
join on `radius_nas.nasname` (already used for client authentication):

```sql
-- resolves nas_id + tenant_id from the request's NAS-IP-Address
SELECT id, tenant_id FROM radius_nas
WHERE nasname = '%{NAS-IP-Address}' AND status = 'active' AND deleted_at IS NULL
LIMIT 1
```

`client_id` (optional FK to `network_clients`) is resolved by username within
that tenant:

```sql
SELECT id FROM network_clients
WHERE tenant_id = <resolved tenant_id> AND username = '%{User-Name}'
LIMIT 1
```

FreeRADIUS's `sql` module supports this via nested `%{sql:...}` xlat calls in
`queries.conf`, or via `accounting { reference }` sub-queries per
`Acct-Status-Type`. Both patterns are shown below.

---

## 2. `mods-available/sql` — accounting block

Add to the same `sql { ... }` block configured in `FREERADIUS_SQL_CLIENTS.md`:

```conf
sql {
    ...
    accounting {
        reference = "%{tolower:type.%{Acct-Status-Type}.query}"

        type {
            start {
                query = "\
                    INSERT INTO radius_sessions \
                        (tenant_id, nas_id, client_id, username, acct_session_id, \
                         client_mac, client_ip, framed_ip, start_time, last_update_time, status) \
                    SELECT n.tenant_id, n.id, \
                           (SELECT id FROM network_clients WHERE tenant_id = n.tenant_id AND username = '%{SQL-User-Name}' LIMIT 1), \
                           '%{SQL-User-Name}', '%{Acct-Session-Id}', \
                           '%{Calling-Station-Id}', '%{Framed-IP-Address}', '%{Framed-IP-Address}', \
                           FROM_UNIXTIME(%{integer:Event-Timestamp}), NOW(), 'active' \
                    FROM radius_nas n \
                    WHERE n.nasname = '%{NAS-IP-Address}' AND n.status = 'active' AND n.deleted_at IS NULL \
                    ON DUPLICATE KEY UPDATE last_update_time = NOW(), status = 'active'"
            }

            interim_update {
                query = "\
                    UPDATE radius_sessions SET \
                        last_update_time = NOW(), \
                        session_time = '%{Acct-Session-Time}', \
                        input_octets = '%{Acct-Input-Octets}', \
                        output_octets = '%{Acct-Output-Octets}', \
                        input_packets = '%{Acct-Input-Packets}', \
                        output_packets = '%{Acct-Output-Packets}', \
                        framed_ip = '%{Framed-IP-Address}' \
                    WHERE acct_session_id = '%{Acct-Session-Id}'"
            }

            stop {
                query = "\
                    UPDATE radius_sessions SET \
                        stop_time = NOW(), \
                        session_time = '%{Acct-Session-Time}', \
                        input_octets = '%{Acct-Input-Octets}', \
                        output_octets = '%{Acct-Output-Octets}', \
                        input_packets = '%{Acct-Input-Packets}', \
                        output_packets = '%{Acct-Output-Packets}', \
                        terminate_cause = '%{Acct-Terminate-Cause}', \
                        status = 'stopped' \
                    WHERE acct_session_id = '%{Acct-Session-Id}'"
            }
        }
    }
}
```

**Every status type should also insert a row into `radius_accounting`** (the
raw event log) so history isn't lost when a session row is overwritten:

```sql
INSERT INTO radius_accounting
    (tenant_id, nas_id, client_id, username, acct_session_id, acct_status_type,
     session_time, input_octets, output_octets, input_packets, output_packets,
     client_ip, client_mac, framed_ip, event_time, terminate_cause, raw_attributes)
SELECT n.tenant_id, n.id,
       (SELECT id FROM network_clients WHERE tenant_id = n.tenant_id AND username = '%{SQL-User-Name}' LIMIT 1),
       '%{SQL-User-Name}', '%{Acct-Session-Id}', '%{Acct-Status-Type}',
       '%{Acct-Session-Time}', '%{Acct-Input-Octets}', '%{Acct-Output-Octets}',
       '%{Acct-Input-Packets}', '%{Acct-Output-Packets}',
       '%{Framed-IP-Address}', '%{Calling-Station-Id}', '%{Framed-IP-Address}',
       FROM_UNIXTIME(%{integer:Event-Timestamp}), '%{Acct-Terminate-Cause}', NULL
FROM radius_nas n
WHERE n.nasname = '%{NAS-IP-Address}' AND n.status = 'active' AND n.deleted_at IS NULL
```

Append this `INSERT` to each of the `start`/`interim_update`/`stop` queries
above (FreeRADIUS allows multiple `;`-separated statements per query, or
chain via `query = "..."` + a second `query` line depending on version —
verify against the installed FreeRADIUS version's `sql` module syntax).

Exact column/xlat names (`%{SQL-User-Name}` vs `%{User-Name}`,
`Acct-Terminate-Cause` availability at `stop`) must be verified against the
live FreeRADIUS version with `radiusd -X` debug output before enabling in
production — the snippets above follow the standard FreeRADIUS 3.x `sql`
module conventions but were not tested against a running server from this
workspace.

---

## 3. Enable accounting processing in the virtual server

In `sites-enabled/default`, the `accounting { }` section must include `sql`:

```conf
accounting {
    ...
    sql
}
```

(This section usually already lists `detail`, `unix`, etc. — add `sql`
alongside them; don't replace.)

---

## 4. Grants (already covered)

`FREERADIUS_SQL_CLIENTS.md` §4 already grants `INSERT, UPDATE` on
`radius_sessions` and `radius_accounting` to the `freeradius` MySQL user —
no additional grant needed.

---

## 5. Verification checklist

- [ ] `accounting { sql }` added to `sites-enabled/default`
- [ ] `type { start/interim_update/stop }` queries added to `mods-available/sql`
- [ ] Exact attribute names verified against `radiusd -X` output for this
      FreeRADIUS version
- [ ] A real hotspot login produces a `radius_sessions` row with `status='active'`
- [ ] `GET /api/v1/sessions?router_external_id=...` (company app → WinguFi Core)
      shows that session
- [ ] Disconnecting produces a `Stop` accounting packet → `radius_sessions.status='stopped'`,
      `terminate_cause` populated, and a matching `Stop` row in `radius_accounting`
- [ ] `GET /api/v1/accounting?router_external_id=...&acct_status_type=Stop` returns it

---

## 6. What's implemented vs. pending

| | Status |
|---|---|
| `radius_sessions` / `radius_accounting` schema | Already existed |
| `GET /api/v1/sessions`, `GET /api/v1/accounting` (WinguFi Core, tenant-scoped) | **Implemented** (`SessionController`, `AccountingController`) |
| FreeRADIUS `sql` accounting queries | **Pending** — server-side config, apply per §2–§3 above |
| MikroTik sending Acct packets to FreeRADIUS on port 1813 | Already configured per `provisionRadiusClient()` (auth+acct ports set) — verify actual delivery once server-side accounting is enabled |
