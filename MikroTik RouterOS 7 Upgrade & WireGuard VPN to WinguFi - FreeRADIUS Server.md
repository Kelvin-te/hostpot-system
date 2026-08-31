# MikroTik RouterOS 7 Upgrade & WireGuard VPN to WinguFi / FreeRADIUS Server

## 1. Purpose

This document records the upgrade of a MikroTik RB951Ui-2HnD from RouterOS 6 to RouterOS 7 and the configuration of a WireGuard VPN tunnel between the MikroTik and the WinguFi / FreeRADIUS server.

The VPN allows the WinguFi server to reach MikroTik routers that do not have public IP addresses.

The VPN is intended primarily for:

- MikroTik management
- RouterOS API access
- HotSpot management
- WinguFi monitoring
- RADIUS-related communication where required
- Secure communication between WinguFi Core and remote routers

Customer Internet traffic does not need to pass through the VPN.

---

# 2. Network Architecture

```text
                         INTERNET
                            |
                            |
                     102.0.33.236
                            |
                +-----------+-----------+
                |                       |
                | WinguFi Server        |
                | Ubuntu                |
                | FreeRADIUS             |
                | Laravel / WinguFi Core|
                |                       |
                | WireGuard:             |
                | 10.50.0.1             |
                +-----------+-----------+
                            |
                       UDP 51820
                            |
                       WireGuard
                            |
                     NAT / CGNAT
                            |
                +-----------+-----------+
                | MikroTik RB951        |
                |                       |
                | WireGuard:            |
                | 10.50.0.2             |
                |                       |
                | HotSpot / Router      |
                +-----------+-----------+
                            |
                         LAN/APs
                            |
                         Clients
```

The MikroTik does not require a public IP.

It establishes an outbound WireGuard connection to:

```text
102.0.33.236:51820
```

---

# 3. MikroTik Hardware

```text
Model:              RB951Ui-2HnD
Architecture:       MIPSBE
CPU:                600 MHz
CPU cores:          1
RAM:                128 MB
```

Original RouterOS:

```text
6.48.6
```

Intermediate RouterOS:

```text
6.49.20
```

Final RouterOS:

```text
7.21.5
```

---

# 4. Why RouterOS 7 Was Required

The MikroTik was originally running RouterOS 6.48.6.

WireGuard is not available on RouterOS 6.

Therefore:

```text
RouterOS 6.48.6
        |
        v
RouterOS 6.49.20
        |
        v
RouterOS 7.21.5
        |
        v
WireGuard
```

RouterOS 7 provides the WireGuard implementation required for the WinguFi VPN architecture.

---

# 5. Upgrade Procedure

## 5.1 Check Hardware

```routeros
/system resource print
```

Confirm:

```text
board-name: RB951Ui-2HnD
architecture-name: mipsbe
cpu-frequency: 600MHz
total-memory: 128.0MiB
```

---

## 5.2 Check RouterOS Version

```routeros
/system package update print
```

Initial version:

```text
6.48.6
```

---

## 5.3 Fix DNS if Required

If the router reports:

```text
ERROR: could not resolve dns name
```

check:

```routeros
/ip dns print
```

Test Internet connectivity:

```routeros
/ping 8.8.8.8 count=4
```

Test DNS:

```routeros
/ping google.com count=4
```

If required, configure DNS:

```routeros
/ip dns set servers=1.1.1.1,8.8.8.8 allow-remote-requests=no
```

---

# 6. Upgrade RouterOS 6

The router initially detected:

```text
latest-version: 6.49.20
```

Upgrade to the latest RouterOS 6 release first.

```routeros
/system package update download
```

After the download completes:

```routeros
/system package update install
```

The router reboots.

Verify:

```routeros
/system resource print
```

Expected:

```text
version: 6.49.20
```

---

# 7. Prepare for RouterOS 7

Create a binary backup:

```routeros
/system backup save name=pre-routeros7
```

Create a text configuration export:

```routeros
/export file=pre-routeros7
```

Verify:

```routeros
/file print
```

Expected files:

```text
pre-routeros7.backup
pre-routeros7.rsc
```

---

# 8. Check RouterOS 7 Availability

Switch to the upgrade channel:

```routeros
/system package update set channel=upgrade
```

Check:

```routeros
/system package update check-for-updates
```

The router reported:

```text
installed-version: 6.49.20
latest-version: 7.21.5
status: New version is available
```

---

# 9. Upgrade to RouterOS 7

Download the RouterOS 7 package:

```routeros
/system package update download
```

After the download completes, install the update:

```routeros
/system package update install
```

The router reboots.

Verify:

```routeros
/system resource print
```

and:

```routeros
/system package print
```

Expected:

```text
version: 7.21.5
```

---

# 10. Post-Upgrade Verification

Check resources:

```routeros
/system resource print
```

Check interfaces:

```routeros
/interface print
```

Check IP addresses:

```routeros
/ip address print
```

Check routes:

```routeros
/ip route print
```

Check DNS:

```routeros
/ip dns print
```

Test Internet connectivity:

```routeros
/ping 8.8.8.8 count=4
```

Test DNS:

```routeros
/ping google.com count=4
```

Check HotSpot:

```routeros
/ip hotspot print
```

Check firewall:

```routeros
/ip firewall filter print
```

Check NAT:

```routeros
/ip firewall nat print
```

Confirm that the existing network and HotSpot configuration still works.

---

# 11. WireGuard VPN Design

The WireGuard VPN network is:

```text
10.50.0.0/24
```

Server:

```text
10.50.0.1
```

First MikroTik:

```text
10.50.0.2
```

Server public IP:

```text
102.0.33.236
```

WireGuard UDP port:

```text
51820
```

---

# 12. WireGuard Server

The Ubuntu WinguFi / FreeRADIUS server has WireGuard installed.

Configuration file:

```text
/etc/wireguard/wg0.conf
```

Basic server configuration:

```ini
[Interface]
Address = 10.50.0.1/24
ListenPort = 51820
PrivateKey = SERVER_PRIVATE_KEY

[Peer]
PublicKey = MIKROTIK_PUBLIC_KEY
AllowedIPs = 10.50.0.2/32
```

The server private key must remain secret.

The server public key is exchanged with MikroTik peers.

---

# 13. MikroTik WireGuard Interface

After upgrading to RouterOS 7, create the WireGuard interface:

```routeros
/interface wireguard add name=wg-wingufi listen-port=51820
```

Check:

```routeros
/interface wireguard print detail
```

RouterOS automatically generates a WireGuard key pair.

The MikroTik public key used for this router was:

```text
C0jV82J4L+z1Saw42C5rAAcF/jttu/45+NZraNafZVk=
```

The private key must remain secret.

---

# 14. Assign the WireGuard IP

Assign:

```text
10.50.0.2/24
```

using:

```routeros
/ip address add address=10.50.0.3/24 interface=wg-wingufi
```

Verify:

```routeros
/ip address print
```

---

# 15. Add the WinguFi Server as MikroTik Peer

The WinguFi server public key is:

```text
V5HxNG5fNl4A3XmeU2ODW/AN5+M2QmvGA1dbgwDPnng=
```

Add the peer:

```routeros
/interface wireguard peers add interface=wg-wingufi public-key="V5HxNG5fNl4A3XmeU2ODW/AN5+M2QmvGA1dbgwDPnng=" endpoint-address=102.0.33.236 endpoint-port=51820 allowed-address=10.50.0.1/32 persistent-keepalive=25s

```

Verify:

```routeros
/interface wireguard peers print detail
```

Expected:

```text
endpoint-address=102.0.33.236
endpoint-port=51820
allowed-address=10.50.0.1/32
persistent-keepalive=25s
```

---

# 16. Add MikroTik Peer on Ubuntu

Edit:

```bash
sudo nano /etc/wireguard/wg0.conf
```

Add:

```ini
[Peer]
PublicKey = C0jV82J4L+z1Saw42C5rAAcF/jttu/45+NZraNafZVk=
AllowedIPs = 10.50.0.2/32
```

Do not replace the existing server private key.

Secure the configuration:

```bash
sudo chmod 600 /etc/wireguard/wg0.conf
```

Restart:

```bash
sudo systemctl restart wg-quick@wg0
```

Check:

```bash
sudo wg show
```

---

# 17. Ubuntu Firewall Configuration

The WinguFi server uses an INPUT firewall policy of:

```text
DROP
```

Therefore WireGuard UDP traffic must explicitly be permitted.

The required firewall rule is:

```bash
sudo iptables -I INPUT 1 -p udp --dport 51820 -j ACCEPT
```

Verify:

```bash
sudo iptables -L INPUT -n -v --line-numbers
```

The rule should show:

```text
ACCEPT
udp
dpt:51820
```

The server uses `iptables-nft`, so the iptables command is used rather than directly modifying the nftables ruleset.

### Important

The WireGuard firewall rule must be made persistent according to the server's firewall management system.

Do not assume that a manually inserted iptables rule will survive a reboot.

---

# 18. NAT / CGNAT Handling

The MikroTik may be behind NAT or CGNAT.

Example:

```text
MikroTik
    |
    | private/NAT address
    |
10.10.0.12
    |
    | Internet
    |
102.0.33.236
WinguFi Server
```

This is not a problem.

The MikroTik initiates the WireGuard connection outbound.

The configuration:

```text
persistent-keepalive=25s
```

keeps the NAT mapping alive.

Do not configure the server to use the MikroTik's private/NAT address as its endpoint.

The MikroTik endpoint remains:

```text
102.0.33.236:51820
```

---

# 19. Testing the Tunnel

## 19.1 MikroTik

Run:

```routeros
/interface wireguard peers print detail
```

A successful tunnel should show:

```text
last-handshake=...
rx=...
tx=...
```

Then:

```routeros
/ping 10.50.0.1 count=5
```

Expected:

```text
10.50.0.1 reachable
```

---

## 19.2 Ubuntu

Run:

```bash
sudo wg show
```

Expected:

```text
interface: wg0
  public key: SERVER_PUBLIC_KEY
  listening port: 51820

peer: MIKROTIK_PUBLIC_KEY
  allowed ips: 10.50.0.2/32
  latest handshake: ...
  transfer: ...
```

A non-zero transfer count indicates WireGuard traffic is flowing.

---

# 20. Troubleshooting the Handshake

If MikroTik shows:

```text
tx > 0
rx = 0
```

check the Ubuntu firewall.

Run:

```bash
sudo nft list ruleset
```

and:

```bash
sudo iptables -L INPUT -n -v
```

Confirm UDP 51820 is allowed.

Also capture traffic:

```bash
sudo tcpdump -ni enp1s0 udp port 51820
```

Expected traffic:

```text
MikroTik -> 102.0.33.236:51820
102.0.33.236:51820 -> MikroTik
```

If packets arrive at Ubuntu but there is no response, check:

1. Ubuntu firewall
2. Server WireGuard peer public key
3. MikroTik peer public key
4. Server private/public key pairing
5. MikroTik allowed-address
6. Server AllowedIPs
7. UDP 51820 availability

---

# 21. WinguFi Router Management

Once the tunnel is established:

```text
WinguFi Server
10.50.0.1
      |
      | WireGuard
      |
10.50.0.2
      |
MikroTik
```

WinguFi Core can use:

```text
10.50.0.2
```

as the MikroTik's VPN-reachable address.

The MikroTik's RouterOS API does not need to be exposed to the public Internet.

Recommended management flow:

```text
WinguFi Core
     |
     | RouterOS API
     |
10.50.0.2
     |
WireGuard
     |
10.50.0.1
     |
WinguFi Server
```

---

# 22. RADIUS Considerations

The VPN provides secure network connectivity between the WinguFi server and the MikroTik.

The actual RADIUS architecture remains:

```text
MikroTik
    |
    | RADIUS authentication/accounting
    |
    v
FreeRADIUS
    |
    v
WinguFi / RADIUS database
```

The WireGuard tunnel can be used to provide a private route between the MikroTik and the server where required.

For example:

```text
MikroTik
10.50.0.2
    |
    | WireGuard
    |
Server
10.50.0.1
    |
FreeRADIUS
```

RADIUS ports commonly used are:

```text
UDP 1812    Authentication
UDP 1813    Accounting
```

Only open these ports as required by the actual RADIUS architecture.

Do not expose RADIUS to the entire Internet unnecessarily.

---

# 23. Recommended Security Model

The MikroTik should not expose management services directly to the Internet.

Prefer:

```text
Internet
   |
   v
WireGuard
   |
10.50.0.0/24
   |
   +---- WinguFi Server
   |
   +---- MikroTik
```

Restrict management services to the WireGuard network where practical.

For example, RouterOS API access can be restricted to the WinguFi server's VPN address.

The exact firewall rule should be added after confirming which RouterOS API service WinguFi Core uses.

---

# 24. Scaling to Additional MikroTik Routers

Each router receives its own WireGuard address.

Example:

```text
WinguFi Server     10.50.0.1

Router 1           10.50.0.2
Router 2           10.50.0.3
Router 3           10.50.0.4
Router 4           10.50.0.5
```

Each router gets its own peer entry on the Ubuntu server.

Example:

```ini
[Peer]
PublicKey = ROUTER_1_PUBLIC_KEY
AllowedIPs = 10.50.0.2/32

[Peer]
PublicKey = ROUTER_2_PUBLIC_KEY
AllowedIPs = 10.50.0.3/32

[Peer]
PublicKey = ROUTER_3_PUBLIC_KEY
AllowedIPs = 10.50.0.4/32
```

Do not assign the same WireGuard IP to two routers.

---

# 25. Final Configuration

## WinguFi / FreeRADIUS Server

```text
Public IP:        102.0.33.236
LAN IP:            10.5.0.9
WireGuard IP:      10.50.0.1
WireGuard port:    UDP 51820
Interface:         wg0
```

## MikroTik RB951

```text
Model:             RB951Ui-2HnD
RouterOS:          7.21.5
WireGuard:         wg-wingufi
WireGuard IP:      10.50.0.2
```

## MikroTik Peer

```text
Endpoint:          102.0.33.236:51820
Allowed address:   10.50.0.1/32
Keepalive:         25 seconds
```

## VPN Network

```text
10.50.0.0/24
```

---

# 26. Operational Commands

### MikroTik

Check WireGuard:

```routeros
/interface wireguard print detail
```

Check peers:

```routeros
/interface wireguard peers print detail
```

Check VPN address:

```routeros
/ip address print
```

Test server:

```routeros
/ping 10.50.0.1 count=5
```

### Ubuntu

Check WireGuard:

```bash
sudo wg show
```

Check interface:

```bash
ip addr show wg0
```

Check route:

```bash
ip route
```

Check UDP:

```bash
sudo ss -lunp | grep 51820
```

Capture WireGuard traffic:

```bash
sudo tcpdump -ni enp1s0 udp port 51820
```

Restart WireGuard:

```bash
sudo systemctl restart wg-quick@wg0
```

Check service:

```bash
sudo systemctl status wg-quick@wg0
```

---

# 27. Important Security Notes

Never share:

- MikroTik WireGuard private keys
- WinguFi server WireGuard private key
- RADIUS shared secrets
- API passwords
- SSH private keys

Public WireGuard keys are safe to exchange between peers.

The server's WireGuard private key was regenerated during this setup after an earlier accidental exposure.

The current private key must remain secret.

---

# 28. Final Result

The RB951 has been upgraded from RouterOS 6.48.6 through RouterOS 6.49.20 to RouterOS 7.21.5.

WireGuard is now used instead of L2TP/IPsec.

The resulting architecture allows:

```text
                         INTERNET
                            |
                    102.0.33.236
                            |
                   WinguFi Server
                FreeRADIUS / Laravel
                            |
                       WireGuard
                            |
                     10.50.0.0/24
                            |
                       10.50.0.2
                            |
                    MikroTik RB951
                            |
                         HotSpot
                            |
                         Clients
```

The MikroTik can therefore be managed by WinguFi without requiring a public IP on the MikroTik.

The next implementation stage is to secure and expose only the required RouterOS API/RADIUS services over the WireGuard interface and integrate the VPN address into WinguFi Core's router-management configuration.

# Mikrotik Configuration Procedure

[ ] Mikrotic Firmware    7.21.

[ ] Internet Settings
[ ] Wireguard Profile - Add the peer to server and router
[ ] Open Firewall !LAN Block
[ ] Add Wireguard client to wireguard Server
     
     Create wireguard profile in router
     Add Wireguard server as peer
     On server , add router as peer
     Restart Wireguard on server
     
[ ] Add Router in WinguFi App (syncs with Core)
[ ] Enable Hotspot in Router, add IP and DNS  in Hotspot profile
[ ] Run radoius provisioning in the app

     Summary: Provision RADIUS wires the MikroTik to FreeRADIUS (local NAS record + /radius entry + use-radius=yes + Core sync); Configure External Portal sets the hotspot login methods (PAP/CHAP/mac-cookie) and whitelists the portal domain in the walled garden — the redirect itself lives in the uploaded hotspot files.
### Make sure to add source address in the radius settings

[ ] Generate hotspot files in app and upload in the router
[ ] Add captive page to walled garden
[ ] Test Purchases from Router
[ ] Test Radius Authentication

### IMPORTANT FINDINGS
31/8/2026 spent full day trying to deploy hotspot on mikrotik. Shit didnt work, reason?
          THE CAPTIVE PORTAL PAGE DIDN'T save link login and other session params while opening the remote captive portal page.