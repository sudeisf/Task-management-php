# Task Management — Simple Plain PHP Structure
# Task Management — Simple Plain PHP Structure

This repository uses a simplified layout for a school project:

- `actions/` — server-side action endpoints (e.g. `upload.php`).
- `view/` — plain PHP/HTML pages and forms (e.g. `upload_form.php`).
- `sql/` — SQL schema and example scripts (`schema.sql`).
- `uploads/` — directory for uploaded files (contains `.gitkeep`).

How to run locally

1. From the project root run:

```bash
php -S localhost:8000
```

2. Open the upload form at:

```
http://localhost:8000/view/upload_form.php
```

Notes
- `actions/upload.php` receives a POST `file` field and saves it to `uploads/` and returns JSON.
- Validate and secure uploads before production (MIME checks, extension whitelist, auth, virus scanning).

If you'd like, I can also:
- Add a root `index.php` linking to the upload form.
- Add simple session-based auth around uploads.
- Store uploaded file metadata in a database.

Tell me which of those you'd like next and I'll implement it.

## Features to implement (based on project objective)

This section breaks the Main Features into concrete implementation tasks with suggested files/folders and notes for a plain-PHP school project.

- **User Management — Secure registration, login, role-based access**
	- Purpose: let individuals sign up, sign in, and give admins/managers role-based privileges.
	- Suggested files: `actions/register.php`, `actions/login.php`, `actions/logout.php`, `view/auth/register.php`, `view/auth/login.php`.
	- Data: `sql/schema.sql` (add `users` table), store hashed passwords (`password_hash`) and role column.
	- Notes: implement session-based auth, CSRF tokens for forms, and server-side validation.

- **Task Operations — Create, edit, delete, assign, priorities, deadlines**
	- Purpose: core task CRUD and assignment workflow.
	- Suggested files: `view/tasks/list.php`, `view/tasks/create.php`, `view/tasks/edit.php`, `actions/tasks_save.php`, `actions/tasks_delete.php`.
	- Data: `sql/schema.sql` (add `tasks` table, `assigned_to`, `priority`, `status`, `due_date`).

- **Dashboard & Reports — Visual summaries and productivity reports**
	- Purpose: quick performance overview for users and managers.
	- Suggested files: `view/dashboard.php`, `actions/reports_export.php`.
	- Notes: implement simple charts (Chart.js via CDN) and CSV export for reports.

- **Notifications & Reminders — Alerts for assignments and due dates**
	- Purpose: notify users about new assignments and upcoming/overdue tasks.
	- Suggested files: `actions/notify.php`, `scripts/cron_reminders.php`, `view/notifications.php`.
	- Notes: implement email sending with `mail()` or a library, plus an in-app `notifications` table for history.

- **Collaboration Tools — Comments, file attachments, activity logs**
	- Purpose: let users comment on tasks, attach files, and view activity history.
	- Suggested files: `view/tasks/comments.php`, `actions/comment_add.php`, attachments handled by `actions/upload.php` (already present), `sql` additions for `comments` and `activity_logs`.

- **Search & Filter — Find tasks by keyword, status, user**
	- Purpose: quick lookup and filtering of large task lists.
	- Suggested files: add query parameters to `view/tasks/list.php` and implement `actions/search.php` or server-side filtering.

- **Admin Controls — Manage users and oversee team operations**
	- Purpose: user management UI for admins: list users, change roles, deactivate accounts.
	- Suggested files: `view/admin/users.php`, `actions/admin_user_update.php`.

- **Tests, Security & Documentation**
	- Purpose: validate core workflows and secure the app (input validation, file upload restrictions, session handling).
	- Suggested files: `tests/` folder for simple PHP test scripts, documentation in `docs/implementation-notes.md`.

If you want, I can start implementing any one of these features now — tell me which to begin with and I'll update the todo progress and create the required files.
