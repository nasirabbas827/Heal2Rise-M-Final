# Heal2Rise

Heal2Rise-M-Final is a support platform for case management, counselling, and NGO coordination.

![PHP](https://img.shields.io/badge/PHP-2563EB?style=flat-square) ![MySQL](https://img.shields.io/badge/MySQL-0F172A?style=flat-square)

## Overview

The data model covers `admin`, `cases`, `case_progress`, `counseling_sessions`, `counselors`, `ngos`, `skill_development`, `team_members`, `users`.

## Application areas

| Area | Pages in the repository |
| --- | --- |
| Admin | [`admin/admin_case_details.php`](admin/admin_case_details.php), [`admin/admin_dashboard.php`](admin/admin_dashboard.php), [`admin/manage_cases.php`](admin/manage_cases.php), [`admin/manage_counselors.php`](admin/manage_counselors.php), [`admin/manage_members.php`](admin/manage_members.php) |
| Root Pages | [`adminlogin.php`](adminlogin.php), [`dashboard.php`](dashboard.php), [`login.php`](login.php), [`ngo_register.php`](ngo_register.php), [`register.php`](register.php) |
| Counselor | [`counselor/counselor_dashboard.php`](counselor/counselor_dashboard.php), [`counselor/log_session.php`](counselor/log_session.php), [`counselor/manage_skills.php`](counselor/manage_skills.php), [`counselor/view_case_details.php`](counselor/view_case_details.php) |
| Ngo | [`ngo/ngo_case_details.php`](ngo/ngo_case_details.php), [`ngo/ngo_cases.php`](ngo/ngo_cases.php), [`ngo/ngo_dashboard.php`](ngo/ngo_dashboard.php), [`ngo/update_profile.php`](ngo/update_profile.php) |
| Team | [`team/team_case_details.php`](team/team_case_details.php), [`team/team_dashboard.php`](team/team_dashboard.php) |

## Getting started

### 1. Get the project

```bash
git clone https://github.com/nasirabbas827/Heal2Rise-M-Final.git
cd Heal2Rise-M-Final
```

### 2. Configure PHP and the database

1. Use a local PHP server with MySQL or MariaDB, such as XAMPP.
2. Import [`Database/heal2risem_db.sql`](Database/heal2risem_db.sql) into your local database server.
3. Set your local database connection details in [`admin/config.php`](admin/config.php), [`config.php`](config.php), [`counselor/config.php`](counselor/config.php), [`ngo/config.php`](ngo/config.php), [`team/config.php`](team/config.php).
4. Keep the existing asset and upload directory paths intact.

### 3. Start the application

With the database running, start a local development server from the repository root:

```bash
php -S 127.0.0.1:8000
```

Open http://127.0.0.1:8000/index.php. If using Apache/XAMPP instead, place the project in its document root and open the matching localhost folder.

## Repository guide

| Path | Purpose |
| --- | --- |
| [`CSS`](CSS) | Stylesheets |
| [`Database`](Database) | Database scripts |
| [`config.php`](config.php) | Application/database configuration |
| [`images`](images) | Image assets |
| [`index.php`](index.php) | Web entry page |

## Development notes

These setup instructions are based on the repository source. The application has not been executed as part of this documentation update; verify dependencies and configuration in your local environment.
