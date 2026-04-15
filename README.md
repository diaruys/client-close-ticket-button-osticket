<<<<<<< HEAD
# Client Close Ticket — osTicket Plugin

Adds a **"Close My Ticket"** button to the client-side ticket view.  
Clients can self-close their own tickets (when the ticket is in an allowed status)  
after confirming a popup dialog.

---

## Features

- Close button appears only when the ticket belongs to the logged-in client
- Button is hidden if the ticket is not in an allowed status (default: Open, Answered)
- Confirmation modal prevents accidental closes
- CSRF-protected AJAX request — no full page reload needed
- Adds an audit-trail note to the ticket thread on close
- Configurable button label, dialog message, success message, and allowed statuses via Admin Panel

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
=======
# Client Close Ticket Button - osTicket



## Getting started

To make it easy for you to get started with GitLab, here's a list of recommended next steps.

Already a pro? Just edit this README.md and make it your own. Want to make it easy? [Use the template at the bottom](#editing-this-readme)!

## Add your files

* [Create](https://docs.gitlab.com/user/project/repository/web_editor/#create-a-file) or [upload](https://docs.gitlab.com/user/project/repository/web_editor/#upload-a-file) files
* [Add files using the command line](https://docs.gitlab.com/topics/git/add_files/#add-files-to-a-git-repository) or push an existing Git repository with the following command:

```
cd existing_repo
git remote add origin https://gitlab.com/ost-plugin/client-close-ticket-button-osticket.git
git branch -M main
git push -uf origin main
```

## Integrate with your tools

* [Set up project integrations](https://gitlab.com/ost-plugin/client-close-ticket-button-osticket/-/settings/integrations)

## Collaborate with your team

* [Invite team members and collaborators](https://docs.gitlab.com/user/project/members/)
* [Create a new merge request](https://docs.gitlab.com/user/project/merge_requests/creating_merge_requests/)
* [Automatically close issues from merge requests](https://docs.gitlab.com/user/project/issues/managing_issues/#closing-issues-automatically)
* [Enable merge request approvals](https://docs.gitlab.com/user/project/merge_requests/approvals/)
* [Set auto-merge](https://docs.gitlab.com/user/project/merge_requests/auto_merge/)

## Test and Deploy

Use the built-in continuous integration in GitLab.

* [Get started with GitLab CI/CD](https://docs.gitlab.com/ci/quick_start/)
* [Analyze your code for known vulnerabilities with Static Application Security Testing (SAST)](https://docs.gitlab.com/user/application_security/sast/)
* [Deploy to Kubernetes, Amazon EC2, or Amazon ECS using Auto Deploy](https://docs.gitlab.com/topics/autodevops/requirements/)
* [Use pull-based deployments for improved Kubernetes management](https://docs.gitlab.com/user/clusters/agent/)
* [Set up protected environments](https://docs.gitlab.com/ci/environments/protected_environments/)

***

# Editing this README

When you're ready to make this README your own, just edit this file and use the handy template below (or feel free to structure it however you want - this is just a starting point!). Thanks to [makeareadme.com](https://www.makeareadme.com/) for this template.

## Suggestions for a good README

Every project is different, so consider which of these sections apply to yours. The sections used in the template are suggestions for most open source projects. Also keep in mind that while a README can be too long and detailed, too long is better than too short. If you think your README is too long, consider utilizing another form of documentation rather than cutting out information.

## Name
Choose a self-explaining name for your project.

## Description
Let people know what your project can do specifically. Provide context and add a link to any reference visitors might be unfamiliar with. A list of Features or a Background subsection can also be added here. If there are alternatives to your project, this is a good place to list differentiating factors.

## Badges
On some READMEs, you may see small images that convey metadata, such as whether or not all the tests are passing for the project. You can use Shields to add some to your README. Many services also have instructions for adding a badge.

## Visuals
Depending on what you are making, it can be a good idea to include screenshots or even a video (you'll frequently see GIFs rather than actual videos). Tools like ttygif can help, but check out Asciinema for a more sophisticated method.

## Installation
Within a particular ecosystem, there may be a common way of installing things, such as using Yarn, NuGet, or Homebrew. However, consider the possibility that whoever is reading your README is a novice and would like more guidance. Listing specific steps helps remove ambiguity and gets people to using your project as quickly as possible. If it only runs in a specific context like a particular programming language version or operating system or has dependencies that have to be installed manually, also add a Requirements subsection.

## Usage
Use examples liberally, and show the expected output if you can. It's helpful to have inline the smallest example of usage that you can demonstrate, while providing links to more sophisticated examples if they are too long to reasonably include in the README.

## Support
Tell people where they can go to for help. It can be any combination of an issue tracker, a chat room, an email address, etc.

## Roadmap
If you have ideas for releases in the future, it is a good idea to list them in the README.

## Contributing
State if you are open to contributions and what your requirements are for accepting them.

For people who want to make changes to your project, it's helpful to have some documentation on how to get started. Perhaps there is a script that they should run or some environment variables that they need to set. Make these steps explicit. These instructions could also be useful to your future self.

You can also document commands to lint the code or run tests. These steps help to ensure high code quality and reduce the likelihood that the changes inadvertently break something. Having instructions for running tests is especially helpful if it requires external setup, such as starting a Selenium server for testing in a browser.

## Authors and acknowledgment
Show your appreciation to those who have contributed to the project.

## License
For open source projects, say how it is licensed.

## Project status
If you have run out of energy or time for your project, put a note at the top of the README saying that development has slowed down or stopped completely. Someone may choose to fork your project or volunteer to step in as a maintainer or owner, allowing your project to keep going. You can also make an explicit request for maintainers.
>>>>>>> 8b40bb42eae7549d1ee31edc185de22e535a0124
