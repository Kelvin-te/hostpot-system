# WinguFi Core Entity Relationship Diagram

```mermaid
erDiagram
    TENANT ||--o{ TENANT_CREDENTIAL : has
    TENANT ||--o{ RADIUS_NAS : has
    TENANT ||--o{ NETWORK_CLIENT : has
    TENANT ||--o{ NETWORK_PACKAGE : has
    TENANT ||--o{ NETWORK_AUTHORIZATION : has
    TENANT ||--o{ RADIUS_SESSION : has
    TENANT ||--o{ RADIUS_ACCOUNTING : has
    TENANT ||--o{ RADIUS_AUTH_LOG : has
    
    RADIUS_NAS ||--o{ RADIUS_SESSION : hosts
    RADIUS_NAS ||--o{ RADIUS_ACCOUNTING : records
    RADIUS_NAS ||--o{ RADIUS_AUTH_LOG : logs
    
    NETWORK_CLIENT ||--o{ NETWORK_AUTHORIZATION : has
    NETWORK_CLIENT ||--o{ RADIUS_SESSION : has
    NETWORK_CLIENT ||--o{ RADIUS_ACCOUNTING : has
    NETWORK_CLIENT ||--o{ RADIUS_AUTH_LOG : has
    
    NETWORK_PACKAGE ||--o{ NETWORK_AUTHORIZATION : defines
    
    TENANT {
        bigint id PK
        uuid uuid UK
        string name
        string slug UK
        string code UK
        enum status
        string timezone
        char currency
        string contact_email
        string contact_phone
        timestamps
        deleted_at
    }
    
    TENANT_CREDENTIAL {
        bigint id PK
        bigint tenant_id FK
        string name
        string client_id UK
        string client_secret_hash
        enum status
        timestamp last_used_at
        timestamp expires_at
        timestamp revoked_at
        timestamps
    }
    
    RADIUS_NAS {
        bigint id PK
        bigint tenant_id FK
        string name
        string nasname
        string shortname
        string type
        string identifier UK
        text description
        enum status
        string radius_secret_encrypted
        int auth_port
        int acct_port
        int coa_port
        string management_ip
        timestamps
        deleted_at
    }
    
    NETWORK_CLIENT {
        bigint id PK
        bigint tenant_id FK
        uuid uuid UK
        string username
        string display_name
        string email
        string phone
        enum status
        string password_hash
        string password_type
        string mac_address
        string static_ip
        text notes
        string external_id
        string external_type
        string source_system
        timestamps
        deleted_at
    }
    
    NETWORK_PACKAGE {
        bigint id PK
        bigint tenant_id FK
        string name
        string code
        text description
        enum status
        int download_speed
        int upload_speed
        int session_timeout
        int validity_seconds
        bigint data_limit_bytes
        int simultaneous_sessions
        decimal price
        char currency
        string external_id
        string external_type
        string source_system
        timestamps
        deleted_at
    }
    
    NETWORK_AUTHORIZATION {
        bigint id PK
        bigint tenant_id FK
        bigint client_id FK
        bigint package_id FK
        string source_type
        string source_id
        string username
        enum status
        timestamp starts_at
        timestamp expires_at
        int session_timeout
        int download_speed
        int upload_speed
        bigint data_limit_bytes
        bigint data_used_bytes
        int simultaneous_sessions
        string external_id
        string external_type
        string source_system
        timestamp revoked_at
        timestamps
    }
    
    RADIUS_SESSION {
        bigint id PK
        bigint tenant_id FK
        bigint nas_id FK
        bigint client_id FK
        string username
        string acct_session_id UK
        string client_mac
        string client_ip
        string framed_ip
        timestamp start_time
        timestamp last_update_time
        timestamp stop_time
        int session_time
        bigint input_octets
        bigint output_octets
        int input_packets
        int output_packets
        string terminate_cause
        enum status
        timestamps
    }
    
    RADIUS_ACCOUNTING {
        bigint id PK
        bigint tenant_id FK
        bigint nas_id FK
        bigint client_id FK
        string username
        string acct_session_id
        enum acct_status_type
        int session_time
        bigint input_octets
        bigint output_octets
        int input_packets
        int output_packets
        string client_ip
        string client_mac
        string framed_ip
        timestamp event_time
        string terminate_cause
        json raw_attributes
        timestamps
    }
    
    RADIUS_AUTH_LOG {
        bigint id PK
        bigint tenant_id FK
        bigint nas_id FK
        bigint client_id FK
        string username
        string client_ip
        string client_mac
        string request_type
        enum result
        string failure_reason
        timestamp event_time
        string request_id
        timestamps
    }
```

## Table Relationships Summary

### Tenant (Central Entity)
- Has many tenant credentials (API keys for authentication)
- Has many NAS devices (routers)
- Has many network clients (users)
- Has many network packages (internet plans)
- Has many network authorizations (active permissions)
- Has many RADIUS sessions (active connections)
- Has many RADIUS accounting records (usage data)
- Has many RADIUS auth logs (authentication attempts)

### RADIUS NAS (Network Access Server)
- Belongs to a tenant
- Has many RADIUS sessions (active connections on this router)
- Has many RADIUS accounting records (usage from this router)
- Has many RADIUS auth logs (auth attempts on this router)

### Network Client (User/Device)
- Belongs to a tenant
- Has many network authorizations (permissions granted to this user)
- Has many RADIUS sessions (connections by this user)
- Has many RADIUS accounting records (usage by this user)
- Has many RADIUS auth logs (auth attempts by this user)

### Network Package (Internet Plan)
- Belongs to a tenant
- Has many network authorizations (authorizations using this package)

### Network Authorization (Permission)
- Belongs to a tenant
- Belongs to a network client
- Belongs to a network package
- Represents the authorization to access the network

### RADIUS Session (Active Connection)
- Belongs to a tenant
- Belongs to a NAS device
- Belongs to a network client (optional)
- Represents an active network session

### RADIUS Accounting (Usage Record)
- Belongs to a tenant
- Belongs to a NAS device
- Belongs to a network client (optional)
- Represents usage data (Start, Interim-Update, Stop events)

### RADIUS Auth Log (Authentication Log)
- Belongs to a tenant
- Belongs to a NAS device (optional)
- Belongs to a network client (optional)
- Records all authentication attempts
