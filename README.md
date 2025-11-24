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
