# WinguFi Company Deployment & Setup

This guide is for deploying and onboarding a new company on WinguFi.

The goal is to keep deployment simple:

```text
WinguFi Core
    │
    ├── Tenant
    ├── Router
    ├── Packages
    ├── Clients
    └── Authorizations
          │
          ▼
      FreeRADIUS
          │
          ▼
       MikroTik
          │
          ▼
        Guests
```

The **company application communicates with WinguFi Core through the API**.

Do not manually edit WinguFi Core database records unless specifically instructed for troubleshooting.

---

# 1. Deploy WinguFi Core

Install the WinguFi Core application on the server.

After uploading the application:

```bash
cd /path/to/wingufi-core
composer install --no-dev --optimize-autoloader
```

Configure `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://core.example.com

DB_CONNECTION=wingufi_core
DB_DATABASE=wingufi_core
DB_USERNAME=...
DB_PASSWORD=...
```

Run:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

Set Laravel permissions:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

Verify the application:

```bash
php artisan about
```

---

# 2. Create the Company Tenant

A tenant represents a company in WinguFi Core.

For the initial platform setup, create the tenant using the Core command:

```bash
php artisan tinker
```

```php
$tenant = \App\Models\Tenant::create([
    'name' => 'Company Name',
    'slug' => 'company-name',
    'status' => 'active',
]);
```

Record the tenant ID.

Example:

```text
Tenant ID: 1
```

> This is normally a platform administration task. The company application should use the tenant created here rather than creating database records directly.

---

# 3. Create the Company API Credential

Generate the API credential:

```bash
php artisan wingufi:credential:create --tenant=<TENANT_ID>
```

Example:

```bash
php artisan wingufi:credential:create --tenant=1
```

**Save the token immediately.**

The raw token is only shown when it is created.

The company application will use this token to communicate with WinguFi Core.

---

# 4. Configure the Company Application

In the company's application `.env`:

```env
WINGUFI_CORE_URL=https://core.example.com
WINGUFI_CORE_TOKEN=YOUR_TENANT_TOKEN
```

Then run:

```bash
php artisan optimize:clear
php artisan config:cache
```

Test the application.

The company application should now be able to communicate with WinguFi Core.

---

# 5. Register the RADIUS Server

FreeRADIUS must be installed on the designated RADIUS server.

Check:

```bash
sudo systemctl status freeradius
```

If it is running, verify the authentication port:

```bash
sudo ss -lunp | grep 1812
```

And accounting:

```bash
sudo ss -lunp | grep 1813
```

Expected:

```text
UDP 1812 = Authentication
UDP 1813 = Accounting
```

---

# 6. Configure the MikroTik

On the MikroTik, configure the RADIUS server:

```routeros
/radius add \
    service=hotspot \
    address=<RADIUS_SERVER_IP> \
    secret=<RADIUS_SHARED_SECRET> \
    authentication-port=1812 \
    accounting-port=1813
```

Verify:

```routeros
/radius print detail
```

Make sure:

- RADIUS IP is correct.
- Shared secret is correct.
- Authentication port is `1812`.
- Accounting port is `1813`.
- Service is `hotspot`.

---

# 7. Set the MikroTik Router Identity

Check the identity:

```routeros
/system identity print
```

If required:

```routeros
/system identity set name=Company-MikroTik
```

Keep the router identifier consistent with the router registered in WinguFi Core.

Also check the router IP:

```routeros
/ip address print
```

---

# 8. Register the Router

Once the tenant and API credential exist, the **company application should register the router through the WinguFi Core API**.

Do not manually insert the router into the Core database.

The intended flow is:

```text
Company Application
        │
        │ POST /api/v1/routers
        ▼
WinguFi Core
        │
        ▼
Router registered under current tenant
```

The deployment team only needs to provide the application with the router information required by the API.

Typical information:

```text
Router name
Router identifier
Router/NAS IP
Status
```

The API handles the tenant association.

---

# 9. Register Packages

Packages should also be created through the WinguFi Core API.

Example flow:

```text
Company Application
        │
        │ POST /api/v1/packages
        ▼
WinguFi Core
```

Do not manually create package database records.

The package belongs automatically to the authenticated tenant.

---

# 10. Register Clients

When a guest/client is created, the company application sends it to Core through:

```text
POST /api/v1/clients
```

The application should provide the necessary client information.

Core associates the client with the authenticated tenant.

---

# 11. Guest Authorization

When a guest purchases or activates a package:

```text
Guest
  │
  ▼
Company Application
  │
  │ POST /api/v1/authorizations
  ▼
WinguFi Core
  │
  ▼
Authorization
```

The company application should **not directly create authorization records in the Core database**.

Core becomes the central authorization record.

---

# 12. Verify a New Authorization

After a guest activates a package, check Core:

```bash
php artisan tinker
```

```php
\App\Models\NetworkAuthorization::latest()->first();
```

A new record should appear.

For a quick check:

```php
\App\Models\NetworkAuthorization::latest()
    ->take(5)
    ->get();
```

Confirm that the authorization belongs to the expected tenant and router.

---

# 13. Test RADIUS

For troubleshooting, stop FreeRADIUS:

```bash
sudo systemctl stop freeradius
```

Run it in debug mode:

```bash
sudo freeradius -X
```

You should see:

```text
Ready to process requests
```

Now connect a guest device and authenticate.

Look for:

```text
Received Access-Request
```

Then:

```text
Sent Access-Accept
```

If authentication fails:

```text
Sent Access-Reject
```

When finished:

```text
Ctrl+C
```

Start FreeRADIUS normally:

```bash
sudo systemctl start freeradius
```

---

# 14. Test the MikroTik Session

On the MikroTik:

```routeros
/ip hotspot active print detail
```

The guest should appear as an active session.

Check traffic:

```routeros
/ip hotspot active print stats
```

The byte counters should increase when the guest uses the Internet.

---

# 15. Test Internet Access

From the MikroTik:

```routeros
/ping 8.8.8.8
```

Then:

```routeros
/ping google.com
```

If both work, test from the guest device.

If `8.8.8.8` works but `google.com` does not, check DNS.

If both fail, check:

- WAN connectivity
- routing
- NAT
- firewall

---

# 16. Final Deployment Test

A company deployment is complete when this entire flow works:

```text
Guest connects
      ↓
Captive portal opens
      ↓
Guest purchases/activates package
      ↓
Company application sends authorization
      ↓
WinguFi Core receives authorization
      ↓
Correct tenant identified
      ↓
Correct router identified
      ↓
RADIUS receives request
      ↓
Access-Accept
      ↓
MikroTik shows active session
      ↓
Traffic counters increase
      ↓
Guest gets Internet
      ↓
Accounting/session data is recorded
```

---

# 17. Quick Deployment Checklist

## WinguFi Core

- [ ] Core deployed
- [ ] Database configured
- [ ] Migrations completed
- [ ] Core accessible
- [ ] Tenant created
- [ ] Tenant ID recorded
- [ ] API credential created
- [ ] API token securely stored

## Company Application

- [ ] `WINGUFI_CORE_URL` configured
- [ ] `WINGUFI_CORE_TOKEN` configured
- [ ] Laravel cache cleared
- [ ] Core API reachable
- [ ] Router registered through API
- [ ] Packages registered through API

## FreeRADIUS

- [ ] FreeRADIUS installed
- [ ] Service running
- [ ] UDP 1812 listening
- [ ] UDP 1813 listening
- [ ] MikroTik configured as RADIUS client
- [ ] Shared secret matches

## MikroTik

- [ ] Router identity configured
- [ ] RADIUS configured
- [ ] Hotspot configured
- [ ] Internet working
- [ ] NAT working
- [ ] DNS working

## Final Test

- [ ] Guest connects
- [ ] Portal opens
- [ ] Guest authorization created
- [ ] Authorization appears in Core
- [ ] RADIUS receives Access-Request
- [ ] RADIUS returns Access-Accept
- [ ] MikroTik shows guest as active
- [ ] Traffic counters increase
- [ ] Guest has Internet

---

# 18. Important Rule

**Use the API wherever an API endpoint exists.**

The deployment team should not manually modify:

- tenants
- routers
- clients
- packages
- authorizations
- Core database records

unless troubleshooting or performing an initial platform administration task.

The intended production model is:

```text
                    WinguFi Core
                         ▲
                         │ API
                         │
                Company Application
                         │
                         ▼
                     Guests
```

The company application is responsible for managing the company's WinguFi resources through the API.

WinguFi Core is responsible for maintaining the central network state.

FreeRADIUS and MikroTik handle authentication and network enforcement.