# RouterOS 7 Upgrade — RB951Ui-2HnD

## Device

- **Model:** MikroTik RB951Ui-2HnD
- **Architecture:** MIPSBE
- **CPU:** 600 MHz, 1 core
- **RAM:** 128 MB
- **Previous RouterOS:** 6.48.6
- **Intermediate RouterOS:** 6.49.20
- **Target RouterOS:** 7.21.5
- **Purpose:** WinguFi HotSpot / router management

## Why We Upgraded

The original router was running RouterOS 6.48.6, which does not provide WireGuard.

The upgrade was performed so the router can establish a WireGuard management tunnel to the WinguFi server.

Target architecture:

```text
                         INTERNET
                            |
                            |
                  102.0.33.236
                  WinguFi Server
                            |
                       WireGuard
                            |
                     10.50.0.0/24
                            |
                     10.50.0.2
                            |
                    MikroTik RB951
                            |
                       HotSpot LAN
```

The WireGuard tunnel is intended primarily for management/API communication. Customer Internet traffic does not need to be routed through the VPN.

## Upgrade Procedure Used

### 1. Verify original hardware

```routeros
/system resource print
```

Original hardware:

```text
board-name: RB951Ui-2HnD
architecture-name: mipsbe
cpu-frequency: 600MHz
cpu-count: 1
total-memory: 128.0MiB
```

### 2. Verify original RouterOS

```routeros
/system package update print
```

Original version:

```text
6.48.6
```

### 3. Fix DNS/update connectivity

The router initially returned:

```text
ERROR: could not resolve dns name
```

DNS/connectivity was corrected and the router was subsequently able to reach the MikroTik update server.

### 4. Upgrade within RouterOS 6

The router initially detected:

```text
latest-version: 6.49.20
```

It was upgraded from:

```text
6.48.6
```

to:

```text
6.49.20
```

This was important because MikroTik recommends moving an older RouterOS 6 installation to a current v6 release before the major v7 upgrade.

### 5. Check RouterOS 7 availability

The upgrade channel was changed:

```routeros
/system package update set channel=upgrade
```

Then:

```routeros
/system package update check-for-updates
```

The router reported:

```text
installed-version: 6.49.20
latest-version: 7.21.5
status: New version is available
```

### 6. Backup before major upgrade

A binary backup was created:

```routeros
/system backup save name=pre-routeros7
```

A human-readable configuration export was also created:

```routeros
/export file=pre-routeros7
```

The files should appear under:

```routeros
/file print
```

Expected files:

```text
pre-routeros7.backup
pre-routeros7.rsc
```

The binary backup and text export serve different purposes. RouterOS documentation notes that binary backups clone the configuration, while text exports are useful for inspecting/recreating configuration.

### 7. Upgrade to RouterOS 7

The router was upgraded to:

```text
7.21.5
```

After reboot, verify:

```routeros
/system resource print
/system package print
```

Expected:

```text
version: 7.21.5
```

## Post-Upgrade Verification

Run these commands after the upgrade:

```routeros
/system resource print
```

```routeros
/system package print
```

```routeros
/ip address print
```

```routeros
/ip route print
```

```routeros
/ip dns print
```

Verify that:

- WAN connectivity works.
- LAN connectivity works.
- HotSpot is running.
- Existing interfaces are present.
- Existing IP addresses are present.
- Default route is present.
- DNS works.
- Existing firewall rules remain present.
- Existing NAT rules remain present.

### Test Internet connectivity

```routeros
/ping 8.8.8.8 count=4
```

Then:

```routeros
/ping google.com count=4
```

### Check CPU and memory

```routeros
/system resource print
```

Pay particular attention to:

```text
cpu-load
free-memory
```

RouterOS 7 introduces a new kernel and can require more CPU/RAM for some processes, so resource usage should be monitored on this 128 MB device.

## RouterBOOT / Firmware

After confirming RouterOS 7 is stable, check:

```routeros
/system routerboard print
```

If appropriate, upgrade the RouterBOOT firmware:

```routeros
/system routerboard upgrade
```

Then reboot:

```routeros
/system reboot
```

MikroTik recommends upgrading the bootloader after upgrading RouterOS.

Do not perform this blindly if the router reports a special RouterBOOT compatibility condition; check the displayed firmware versions first.

## WireGuard Preparation

RouterOS 7 provides WireGuard support.

Check that it is available:

```routeros
/interface wireguard print
```

The WinguFi server will use:

```text
Public IP:       102.0.33.236
WireGuard port:  51820/UDP
WireGuard IP:    10.50.0.1
```

The first MikroTik will use:

```text
WireGuard IP:    10.50.0.2
```

Planned VPN network:

```text
10.50.0.0/24
```

### Planned tunnel

```text
WinguFi Server
102.0.33.236
10.50.0.1
      |
      | UDP 51820
      |
      | WireGuard
      |
10.50.0.2
MikroTik RB951
```

The MikroTik will initiate the tunnel outbound to the WinguFi server.

This means the router does not need its own public IP and can remain behind NAT/CGNAT, provided it can make outbound UDP connections.

## Important Security Notes

The WinguFi server's original WireGuard private key was accidentally exposed during initial setup.

That key was subsequently regenerated.

The replacement private key must remain secret.

Never place a WireGuard private key in:

- Chat messages
- Git repositories
- Public documentation
- Configuration exports shared with others
- Screenshots
- Public support tickets

Only the WireGuard **public key** should be exchanged with the remote peer.

## WinguFi Integration Plan

Once WireGuard is configured, WinguFi Core can communicate with the MikroTik through its VPN address.

Example:

```text
WinguFi Core
     |
     | RouterOS API
     |
10.50.0.2
     |
MikroTik RB951
```

The VPN is intended to provide secure management connectivity without exposing the MikroTik's RouterOS API directly to the public Internet.

This is preferable to opening RouterOS management ports such as API, API-SSL, WinBox, or SSH directly to the Internet.

## Final Target Architecture

```text
                       INTERNET
                          |
                          |
                   102.0.33.236
                          |
              +-----------+-----------+
              |                       |
       WinguFi Core              FreeRADIUS
              |                       |
              +-----------+-----------+
                          |
                     WireGuard
                          |
                   10.50.0.0/24
                          |
              +-----------+-----------+
              |                       |
        10.50.0.2                 10.50.0.3
        MikroTik A                MikroTik B
              |                       |
           HotSpot                 HotSpot
              |                       |
           Clients                 Clients
```

## Next Step

Configure the first WireGuard interface on the RB951:

```routeros
/interface wireguard add \
    name=wg-wingufi \
    listen-port=51820
```

Then assign:

```text
10.50.0.2/24
```

to the WireGuard interface.

After that, exchange:

- MikroTik public key → WinguFi server
- WinguFi server public key → MikroTik

Then configure the peer, routing, firewall, and persistent keepalive.

MikroTik's WireGuard documentation confirms that creating a WireGuard interface automatically generates its key pair and that peers are configured using the remote public key and allowed addresses.