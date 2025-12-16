# Task Management System

A comprehensive PHP-based Task Management System designed to help teams facilitate project tracking, task assignment, and collaboration. Built with a custom MVC architecture, it supports role-based access control (Admin, Manager, Member), real-time status updates, and automated workflows.

## Features

*   **Role-Based Access Control (RBAC)**:
    *   **Admin**: Full system access, user management, and global oversight.
    *   **Manager**: Create and manage projects, assign tasks, and track team progress.
    *   **Member**: View assigned tasks, update status, and collaborate.
*   **Project Management**:
    *   Create and manage projects.
    *   **Automated Status**: Projects automatically update to "In Progress" or "Completed" based on task progress.
    *   **Team Sync**: Assigning a user to a task automatically adds them to the project team.
*   **Task Management**:
    *   Create, edit, delete, and assign tasks.
    *   Set priorities, deadlines, and descriptions.
    *   **Search & Filter**: Powerful search capabilities for tasks and projects.
    *   **Bulk Actions**: Update or delete multiple tasks at once.
*   **User Management**:
    *   Secure registration and login.
    *   Admin panel to manage user roles and status (Active/Inactive).
    *   **Security**: Inactive users are automatically blocked from logging in.
*   **Dashboard**:
    *   Visual overview of system statistics.
    *   Recent activity logs and notifications.
    *   "My Tasks" quick view for members.

## Tech Stack

*   **Backend**: Native PHP 8.x (MVC Pattern)
*   **Database**: MySQL
*   **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
*   **Server**: Built-in PHP server or Apache/Nginx

## Folder Structure

```
config/         — Database connection and global constants
controller/     — Business logic and request handling (Admin, Task, Project, etc.)
models/         — Database interactions and data logic (User, Task, Project, etc.)
views/          — Frontend templates (layouts, forms, lists)
core/           — Framework core (Database, Session, Auth, Router)
helpers/        — Utility functions
sql/            — Database schema scripts
public/         — Public assets (CSS, JS, images)
uploads/        — User uploaded attachments
```

## Installation & Setup

1.  **Clone the Repository**
    ```bash
    git clone <repository-url>
    cd task-management-php
    ```

2.  **Database Setup**
    *   Create a MySQL database (e.g., `task_manager`).
    *   Import the schema files from the `sql/` directory in order.
    *   Update database credentials in `config/db.php`.

3.  **Run the Application**
    Start the built-in PHP development server:
    ```bash
    php -S localhost:8000
    ```

4.  **Access the App**
    Open your browser and navigate to:
    ```
    http://localhost:8000
    ```

## Development Notes

*   **Clean Code**: The project follows strict MVC principles for maintainability.
*   **Security**: Inputs are sanitized, and SQL injections are prevented using prepared statements. Passwords are hashed using `password_hash()`.
*   **Automation**: Several background logic hooks (like project status updates) are implemented in the keys Controller methods (e.g., `TaskController`).
