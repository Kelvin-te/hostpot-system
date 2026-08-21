# WinguFi Core — Central RADIUS Database

## Objective

Create the initial Laravel/database migrations for a dedicated central database named:

```text
wingufi_core
```

This database will be the **central network-access and RADIUS control-plane database** shared by multiple independent Laravel billing applications.

Do NOT modify individual company Laravel databases.

Do NOT duplicate customer/business/payment data unnecessarily.

The architecture is:

```text
                    ┌──────────────────────────┐
                    │      WinguFi Core        │
                    │      wingufi_core        │
                    │                          │
                    │ Tenants / Companies      │
                    │ RADIUS Credentials       │
                    │ MikroTik Routers         │
                    │ Network Clients           │
                    │ Packages / Plans         │
                    │ Authorizations            │
                    │ RADIUS Accounting        │
                    │ Active Sessions           │
                    └────────────┬─────────────┘
                                 │
                             FreeRADIUS
                                 │
             ┌───────────────────┼───────────────────┐
             │                   │                   │
         MikroTik A          MikroTik B          MikroTik C
             │                   │                   │
        Company A           Company B           Company A
             │                   │                   │
        Laravel A           Laravel B           Laravel A
```

---

# 1. Important Architectural Principle

`wingufi_core` is **NOT** the master business database.

Company Laravel databases remain responsible for:

* Customers
* Payments
* M-Pesa transactions
* Invoices
* Orders
* Business records
* Voucher sales
* Application-specific data

`wingufi_core` is responsible for:

* Tenants/companies
* RADIUS credentials
* MikroTik/NAS registration
* Network clients
* Network credentials
* Network packages/plans
* Network authorization
* RADIUS sessions
* RADIUS accounting
* Network usage
* Tenant-level network configuration

Laravel applications synchronize relevant network authorization information into `wingufi_core`.

---

# 2. Database

Create/configure:

```text
wingufi_core
```

The migrations should be designed to run against this database connection rather than an individual company's application database.

If this migration project is itself Laravel-based, use a dedicated database connection such as:

```env
WINGUFI_DB_CONNECTION=wingufi_core
```

Do not assume the default Laravel connection is `wingufi_core`.

---

# 3. Tenants / Companies

Create:

```text
tenants
```

Purpose:

Represent each independent company using the WinguFi network/RADIUS platform.

Suggested fields:

```text
id
uuid
name
slug
code
status
timezone
currency
contact_email
contact_phone
created_at
updated_at
deleted_at
```

Requirements:

* `uuid` must be unique.
* `slug` must be unique.
* `code` must be unique.
* `status` should support at least:

  * active
  * suspended
  * disabled

Do not store company passwords in this table.

---

# 4. Tenant API Credentials

Create:

```text
tenant_credentials
```

Purpose:

Allow each Laravel company instance to securely communicate with WinguFi Core.

Suggested fields:

```text
id
tenant_id
name
client_id
client_secret_hash
status
last_used_at
expires_at
created_at
updated_at
revoked_at
```

Requirements:

* `client_id` unique.
* Never store raw client secrets.
* Store a secure hash where appropriate.
* Support credential rotation.
* Support revocation.
* Credentials must belong to exactly one tenant.

The Laravel application must never need FreeRADIUS secrets directly.

---

# 5. RADIUS NAS / MikroTik Routers

Create:

```text
radius_nas
```

Purpose:

Register every MikroTik router that is allowed to communicate with FreeRADIUS.

Suggested fields:

```text
id
tenant_id
name
nasname
shortname
type
identifier
description
status
radius_secret_encrypted
auth_port
acct_port
coa_port
management_ip
created_at
updated_at
deleted_at
```

Important:

`nasname` should represent the RADIUS client's network address/identity.

`radius_secret_encrypted` must never be exposed to frontend applications.

The database should support:

```text
Company A
 ├── MikroTik A
 ├── MikroTik B
 └── MikroTik C

Company B
 ├── MikroTik D
 └── MikroTik E
```

Each NAS belongs to exactly one tenant.

---

# 6. Network Clients

Create:

```text
network_clients
```

Purpose:

Represent users/devices that are authorized to access a company's network.

Suggested fields:

```text
id
tenant_id
uuid
username
display_name
email
phone
status
password_hash
password_type
mac_address
static_ip
notes
created_at
updated_at
deleted_at
```

Requirements:

* `uuid` unique.
* Username uniqueness should be tenant-scoped.
* Do NOT make username globally unique across all companies.
* Passwords must never be stored in plaintext.
* MAC addresses should use a consistent normalized format.
* Client status should support at least:

  * active
  * suspended
  * disabled

A client belongs to one tenant.

---

# 7. Network Packages

Create:

```text
network_packages
```

Purpose:

Represent the network-level characteristics that FreeRADIUS/MikroTik needs.

Suggested fields:

```text
id
tenant_id
name
code
description
status
download_speed
upload_speed
session_timeout
validity_seconds
data_limit_bytes
simultaneous_sessions
price
currency
created_at
updated_at
deleted_at
```

Important:

This is the **network representation** of a package.

The company's Laravel billing system may have a richer package/product table.

Do not duplicate the entire billing system here.

This table should contain only the information needed for network authorization.

Examples:

```text
1 Day Unlimited
10M/10M
86400 seconds
No data limit

1 Week
20M/20M
604800 seconds
100 GB

1 Month
10M/10M
2592000 seconds
Unlimited
```

Do not hard-code these values.

---

# 8. Client Authorizations

Create:

```text
network_authorizations
```

This is one of the most important tables.

Purpose:

Represent the actual network access granted to a client.

Suggested fields:

```text
id
tenant_id
client_id
package_id
source_type
source_id
username
status
starts_at
expires_at
session_timeout
download_speed
upload_speed
data_limit_bytes
data_used_bytes
simultaneous_sessions
created_at
updated_at
revoked_at
```

`source_type` and `source_id` allow Laravel to identify what created the authorization.

Examples:

```text
payment
voucher
subscription
manual
promotion
```

The authorization table should be the primary source FreeRADIUS uses to determine whether a client is currently authorized.

---

# 9. RADIUS Authentication Credentials

If authentication credentials need to be separated from client records, create:

```text
radius_credentials
```

Suggested fields:

```text
id
tenant_id
client_id
username
credential_type
value
status
expires_at
created_at
updated_at
```

However:

**Do not duplicate credentials if `network_clients` already provides the correct authentication model.**

The coding agent must inspect the intended FreeRADIUS authentication method before deciding whether this table is necessary.

If a credential table is used:

* Never store plaintext passwords unless technically unavoidable for a specific RADIUS authentication method.
* Document the credential type.
* Support rotation/revocation.

---

# 10. RADIUS Sessions

Create:

```text
radius_sessions
```

Purpose:

Track currently active and historical network sessions.

Suggested fields:

```text
id
tenant_id
nas_id
client_id
username
acct_session_id
client_mac
client_ip
framed_ip
start_time
last_update_time
stop_time
session_time
input_octets
output_octets
input_packets
output_packets
terminate_cause
status
created_at
updated_at
```

`acct_session_id` must be indexed.

The combination of:

```text
tenant_id
nas_id
acct_session_id
```

should be efficiently searchable.

---

# 11. RADIUS Accounting

If session data should be separated from raw accounting events, create:

```text
radius_accounting
```

Suggested fields:

```text
id
tenant_id
nas_id
client_id
username
acct_session_id
acct_status_type
session_time
input_octets
output_octets
input_packets
output_packets
client_ip
client_mac
framed_ip
event_time
terminate_cause
raw_attributes
created_at
updated_at
```

Support:

```text
Start
Interim-Update
Stop
```

`raw_attributes` may be JSON if useful for debugging/auditing.

Do not rely exclusively on raw attributes for business logic.

---

# 12. RADIUS Authentication Logs

Create:

```text
radius_auth_logs
```

Purpose:

Audit authentication attempts.

Suggested fields:

```text
id
tenant_id
nas_id
client_id
username
client_ip
client_mac
request_type
result
failure_reason
event_time
request_id
created_at
```

Possible results:

```text
accepted
rejected
error
```

Do not store passwords or sensitive authentication secrets in logs.

---

# 13. Tenant-Router Relationship

A router must belong to exactly one tenant.

Enforce this at database level where practical:

```text
radius_nas.tenant_id → tenants.id
```

All client, package, authorization and session relationships should also preserve tenant ownership.

Do not allow cross-tenant relationships.

For example:

```text
Tenant A Client
       X
Tenant B Package
```

must be impossible.

The application must validate this in addition to database foreign keys.

---

# 14. External Laravel References

Because company Laravel databases are separate from `wingufi_core`, do not create foreign keys to company databases.

Instead support external references such as:

```text
external_id
external_type
source_system
```

For example, an authorization may contain:

```text
source_system = company_a_laravel
source_type   = payment
source_id     = 58291
```

This allows WinguFi Core to know which Laravel application created the authorization without coupling the databases.

Consider adding these fields to:

```text
network_clients
network_packages
network_authorizations
```

where useful.

---

# 15. Idempotency

Laravel synchronization must be safe to repeat.

Add appropriate unique identifiers such as:

```text
external_uuid
external_id
source_system
```

where needed.

The same payment or authorization request must not create duplicate network authorizations.

For example:

```text
Company A Laravel
     ↓
Payment #12345
     ↓
Authorization
```

If Laravel retries the synchronization:

```text
Payment #12345
     ↓
Existing authorization found
     ↓
Update instead of duplicate
```

---

# 16. Indexing

Create indexes for frequent RADIUS queries.

At minimum consider:

```text
tenants.slug
tenants.code

tenant_credentials.client_id

radius_nas.nasname
radius_nas.identifier
radius_nas.tenant_id

network_clients.username + tenant_id
network_clients.mac_address + tenant_id

network_packages.code + tenant_id

network_authorizations.username + tenant_id
network_authorizations.client_id
network_authorizations.status
network_authorizations.expires_at

radius_sessions.acct_session_id
radius_sessions.username
radius_sessions.client_id
radius_sessions.nas_id
radius_sessions.status
```

Design indexes based on the actual SQL queries FreeRADIUS will use.

Do not create excessive indexes blindly.

---

# 17. Soft Deletes

Use soft deletes only where appropriate.

Good candidates:

```text
tenants
radius_nas
network_clients
network_packages
```

Do NOT use soft deletion in a way that causes RADIUS authentication queries to accidentally find deleted records.

FreeRADIUS queries must explicitly filter active records.

---

# 18. Status and Expiry

Authorization should be determined using both:

```text
status
```

and:

```text
expires_at
```

Conceptually:

```text
status = active
AND
starts_at <= NOW()
AND
expires_at > NOW()
```

unless the package is explicitly unlimited.

Do not rely solely on a Laravel scheduled job to change status after expiry.

The authorization query itself must recognize expiry.

---

# 19. RADIUS Attribute Strategy

Do not hard-code MikroTik attributes into random application tables.

Create a clean mechanism for returning authorization attributes.

At minimum the architecture should be capable of producing:

```text
Session-Timeout
Mikrotik-Rate-Limit
Framed-IP-Address
Framed-IP-Netmask
```

Only return attributes applicable to the client's active authorization.

The exact FreeRADIUS SQL schema/query implementation will be configured after the migrations are complete.

---

# 20. Do Not Create Standard FreeRADIUS Tables Blindly

Do NOT simply copy the default FreeRADIUS:

```text
radcheck
radreply
radgroupcheck
radgroupreply
radacct
nas
```

schema into `wingufi_core` without first deciding how it maps to the WinguFi domain model.

We need WinguFi Core to be the clean central model.

If FreeRADIUS requires standard SQL tables/views, create them deliberately as:

* dedicated RADIUS compatibility tables, or
* SQL views mapped to WinguFi Core tables.

The final choice should be made after inspecting the installed FreeRADIUS version and SQL module.

---

# 21. Migration Requirements

Create migrations only.

Do not yet create:

* Controllers
* APIs
* FreeRADIUS configuration
* MikroTik configuration
* Laravel synchronization code
* Authentication endpoints

The immediate deliverable is the database schema.

Migrations must:

* Be reversible where practical.
* Use foreign keys.
* Use appropriate indexes.
* Use UUIDs where specified.
* Avoid cross-database foreign keys.
* Be compatible with MySQL/MariaDB supported by the server.
* Follow Laravel migration conventions.

---

# 22. Required Deliverables

Provide:

1. Database creation/migration strategy for `wingufi_core`.
2. Migration files.
3. Table relationship diagram.
4. Explanation of every table.
5. Explanation of tenant isolation.
6. Explanation of how Laravel instances will reference WinguFi Core.
7. Recommended indexes.
8. Recommended unique constraints.
9. Seed data for a development tenant and test MikroTik router if useful.
10. No production credentials in seeders.

---

# 23. Final Relationship Model

The intended model is:

```text
TENANT
  │
  ├───────────────┐
  │               │
  ▼               ▼
NAS/ROUTERS     CLIENTS
  │               │
  │               │
  │               ▼
  │           AUTHORIZATIONS
  │               │
  │               ▼
  │            PACKAGE
  │
  ▼
RADIUS SESSIONS
  │
  ▼
RADIUS ACCOUNTING
```

And externally:

```text
Company Laravel
      │
      │ API/synchronization
      ▼
WinguFi Core
      │
      ▼
FreeRADIUS
      │
      ▼
MikroTik
```

## Critical requirement

**WinguFi Core must remain independent of any single company's Laravel application.**

It is a central network platform that can serve:

```text
Company A → Laravel A → WinguFi Core
Company B → Laravel B → WinguFi Core
Company C → Laravel C → WinguFi Core
```

while maintaining strict tenant isolation.

Build the schema with the future FreeRADIUS integration in mind, but **do not couple the schema directly to FreeRADIUS's default tables until the exact authentication and accounting queries have been designed.**
