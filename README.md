# Client Close Ticket — osTicket Plugin

Adds a **"Close My Ticket"** button to the client-side ticket view.  
Clients can self-close their own tickets (when the ticket is in an allowed status)  
after confirming a popup dialog.

---

## Features

v1.0.0
- Close button appears only when the ticket belongs to the logged-in client
- Button is hidden if the ticket is not in an allowed status (default: Open, Answered)
- Confirmation modal prevents accidental closes
- CSRF-protected AJAX request — no full page reload needed
- Adds an audit-trail note to the ticket thread on close
- Configurable button label, dialog message, success message, and allowed statuses via Admin Panel

v1.0.1
- Collaborators on a ticket can also self-close

---

## File Structure

```
client-close-ticket/
├── plugin.php                      ← Plugin manifest (required)
├── client-close-ticket.php         ← Main plugin class (Signal hooks)
├── config.php                      ← Admin settings form
├── ajax.php                        ← AJAX close-action handler
└── templates/
    └── close-button.tmpl.php       ← Button + modal HTML/JS template
```

---

## Installation

1. Copy the entire `client-close-ticket/` folder to:
   ```
   <osticket_root>/include/plugins/client-close-ticket/
   ```

2. Log in to the **Admin Panel**.

3. Go to **Manage → Plugins → Add New Plugin**.

4. Select **Client Close Ticket** and click **Install**.

5. Once installed, click on the plugin and set its status to **Active**.

6. *(Optional)* Click **Settings** to customise labels, the confirmation message, and allowed statuses.

---

## Configuration Options

| Setting | Default | Description |
|---|---|---|
| Button Label | `Close My Ticket` | Text shown on the button |
| Allowed Statuses | Open, Answered | Button is shown only when ticket is in one of these statuses |
| Confirmation Message | *(see config)* | Text shown in the popup before closing |
| Success Message | *(see config)* | Message shown after a successful close |

---

## How It Works

1. The plugin hooks into osTicket's `ajax.client` Signal, fired on every client-facing page load.
2. When the client visits their ticket view (`tickets.php?id=NNN`), the plugin injects the button + modal HTML just before `</body>` via PHP output buffering.
3. The button is rendered only when the ticket belongs to the current user **and** is in an allowed status.
4. When the client clicks **Yes, Close It**, a CSRF-protected `fetch()` POST is sent to `ajax.php` with `do=client-close-ticket`.
5. The server-side handler (`ajax.php`) re-validates ownership, status, and CSRF, then calls `$ticket->close()`.
6. On success the client sees a success message and is redirected to their ticket list.

---

## Compatibility

- osTicket **1.17.x** and **1.18.x**
- PHP 7.4+
- Requires client login (guests cannot self-close tickets)

---

## Troubleshooting

**Button does not appear**
- Confirm the plugin is installed *and* set to **Active**.
- Confirm the ticket's current status is in the *Allowed Statuses* setting.
- Confirm you are logged in as the ticket owner (not an agent).

**"Invalid or missing security token" error**
- Ensure PHP sessions are working correctly on your server.
- Try clearing browser cookies and logging in again.

**Ticket does not close / unexpected error**
- Check the osTicket system log: Admin Panel → Dashboard → System Logs.
- Enable PHP error logging and check your server error log for stack traces.
