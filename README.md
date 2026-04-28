# Client Close Ticket — osTicket Plugin

> Adds a **"Close My Ticket"** button to the client-side ticket view, allowing authenticated users to self-close their own tickets.

---

## Requirements

- osTicket **1.17.x** or **1.18.x**
- PHP **8.0+**
- Apache **2.4+**

---

## Installation

```bash
# 1. Copy plugin folder to osTicket plugins directory
sudo cp -r client-close-ticket /var/www/html/osticket/upload/include/plugins/

# 2. Set ownership
sudo chown -R www-data:www-data /var/www/html/osticket/upload/include/plugins/client-close-ticket/

# 3. Restart web server
sudo systemctl restart apache2
```

Then in the **Admin Panel**:
1. Go to **Manage → Plugins → Add New Plugin**
2. Install **Client Close Ticket**
3. Click the plugin → **Add Instance** → set Status to **Active** → **Save**

---

## File Structure

```
client-close-ticket/
├── plugin.php                  ← Plugin manifest
├── client-close-ticket.php     ← Main class (bootstrap, HTML injection)
├── config.php                  ← Admin settings form
├── ajax.php                    ← AJAX handler (ticket close logic)
└── templates/
    └── close-button.tmpl.php   ← Legacy template (not active)
```

---

## Configuration

Admin Panel → Manage → Plugins → Client Close Ticket → (instance) → **Settings**

| Setting | Default |
|---|---|
| Button Label | `Close My Ticket` |
| Allowed Statuses | Open, Answered |
| Confirmation Message | `Are you sure you want to close this ticket?...` |
| Success Message | `Your ticket has been closed. Thank you!` |

---

## How It Works

1. Plugin injects the Close button inline with **Post Reply / Reset / Cancel**
2. Client clicks button → native osTicket overlay + confirmation dialog appears
3. Client confirms → CSRF-protected AJAX POST to `ajax.php/tickets/close`
4. Server validates: ownership ✓ status ✓ → calls `$ticket->setStatus(closed)`
5. Success message shown → client redirected to ticket list

### Security
- CSRF token validated on every request (`__CSRFToken__`)
- Ticket ownership verified — only the ticket owner can close
- Status gate — button hidden if ticket is not in an allowed status

---

## Troubleshooting

**Button not showing?**
- Confirm plugin instance is created and set to **Active**
- Confirm ticket status is in the Allowed Statuses list
- Confirm you are logged in as the client (ticket owner), not as an agent

**Error on close?**
```bash
sudo tail -30 /var/log/apache2/error.log
```

**Verify plugin is active:**
```bash
sudo mysql -u root -p -e "SELECT id, name, isactive FROM ost_plugin;" osticket
```

**Test AJAX route:**
```bash
curl -s -X POST http://localhost/upload/ajax.php/tickets/close -d "ticket_id=1"
# Expected: {"success":false,"message":"You must be logged in."} or similar JSON
```

---

## Changelog

| Version | Date | Notes |
|---|---|---|
| 1.0.0 | April 2026 | Initial release |

---

## License

MIT — free to use, modify, and distribute.
