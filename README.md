# Task Management System

## Objective

To build a platform that helps individuals and teams plan, assign, and track tasks efficiently, enhancing productivity and accountability.

---

## Folder Structure

```
actions/      — Server-side PHP endpoints (CRUD, auth, file uploads)
view/         — Frontend PHP/HTML pages (forms, dashboard, task views)
config/       — Shared configuration and database connection (config.php, db.php)
uploads/      — Directory for uploaded files (.gitkeep included)
sql/          — Database schema and example scripts (schema.sql)
index.php     — Root entry point that routes users to login or dashboard
```

---

## Main Features

* **User Management:** Secure registration, login, and role-based access control.
* **Task Operations:** Create, edit, delete, assign, and track tasks with priorities and deadlines.
* **Dashboard & Reports:** Visual performance summaries and productivity reports.
* **Notifications & Reminders:** Alerts for assignments and due dates.
* **Collaboration Tools:** Comments, file attachments, and activity logs.
* **Search & Filter:** Quickly find tasks by keyword, status, or user.
* **Admin Controls:** Manage users and oversee team operations.

---

## How to Run Locally

1. Clone the repository:

```bash
git clone <repository-url>
```

2. Navigate to the project root:

```bash
cd task-management-system
```

3. Start PHP's built-in server:

```bash
php -S localhost:8000
```

4. Open the dashboard or login page in your browser:

```
http://localhost:8000/index.php
```

---

## Notes

* `actions/` contains all backend logic for tasks, users, comments, file uploads, and notifications.
* `uploads/` stores attachments. Make sure to validate file types and size in production.
* `config/` contains database connection (`db.php`) and other project-wide settings (`config.php`).
* `sql/` contains schema and sample data to quickly set up the database.
* Use Bootstrap CDN for styling; no local CSS/JS is required.

---
