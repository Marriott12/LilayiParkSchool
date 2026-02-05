Purpose
-------
This file helps AI coding agents become productive quickly in the LilayiParkSchool PHP codebase. Focus on discoverable facts only: structure, important files, workflows, conventions, and concrete examples to change common features.

**Big Picture**
- Web app: procedural PHP pages served from repository root (WAMP on Windows). Entry point is [index.php](index.php). UI assets under [assets/](assets/).
- Configuration: application-level settings in [config/config.php](config/config.php) and DB connection in [config/database.php](config/database.php).
- Authentication/authorization: centralized in [includes/Auth.php](includes/Auth.php) — include it at top of pages to require login.
- API surface: lightweight PHP endpoints under [api/](api/) and [api/mobile/](api/mobile/) for mobile/exports (examples: [api/export_attendance.php](api/export_attendance.php)).
- Persistence: SQL files in [database/](database/) and ad-hoc migrations in [migrations/](migrations/). Use these to inspect schema changes.

**Key Files and Directories (quick links)**
- **Entry:** [index.php](index.php)
- **Config:** [config/config.php](config/config.php), [config/database.php](config/database.php)
- **Auth helper:** [includes/Auth.php](includes/Auth.php)
- **Feature pages:** root-level pattern `*_form.php`, `*_list.php`, `*_view.php`, `*_delete.php` (e.g., [pupils_form.php](pupils_form.php), [pupils_list.php](pupils_list.php))
- **APIs:** [api/](api/) and subfolder [api/mobile/](api/mobile/)
- **Database assets:** [database/](database/) (deployment scripts)
- **Utilities:** [clear_cache.php](clear_cache.php), [logs/](logs/)
- **Composer:** [composer.json](composer.json) and `vendor/` — run `composer install` if dependencies are missing.

**Common Conventions & Patterns**
- File naming: UI interactions follow `*_form.php` (create/edit), `*_list.php` (index), `*_view.php` (single record). To change a CRUD flow, update both the `*_form.php` and the related `*_list.php`/`*_view.php`.
- Inline PHP pages: Many pages include HTML + logic in the same file. Look for `require`/`include` at top for shared helpers and the DB connection.
- DB access: check [config/database.php](config/database.php) for connection details; raw SQL is used in many places rather than an ORM.
- Auth checks: Pages call the `Auth` helper early — modify access rules there for global changes.

**Developer Workflows (discoverable steps)**
- Local run: place repository in WAMP `www` directory (e.g., `C:\wamp64\www\LilayiParkSchool`) and start Apache + MySQL from WAMP UI.
- PHP dependencies: run `composer install` in the repo root if `vendor/` is absent.
- Database setup: import SQL from [database/](database/) - use `full_schema_deployment.sql` then `seed_data_deployment.sql` via phpMyAdmin or MySQL CLI:  
```
mysql -u <user> -p <dbname> < database/full_schema_deployment.sql
mysql -u <user> -p <dbname> < database/seed_data_deployment.sql
```
- Verify setup: run `database/verify_deployment.sql` to check database structure
- Debugging: check `logs/` for runtime errors; enable PHP error display in php.ini for local debugging.
- Clear caches: run or open [clear_cache.php](clear_cache.php) to reset application caches.

**Integration & External Dependencies**
- Composer packages located under `vendor/` (PHPMailer present). See [composer.json](composer.json) for full list.
- Mobile clients expect endpoints under [api/mobile/](api/mobile/). Export and search endpoints live in [api/](api/) (e.g., `export_*`, `search_*`).

**Concrete Examples**
- To add a new field to pupil records: 1) update form UI in [pupils_form.php](pupils_form.php), 2) adapt persistence SQL in the appropriate DB file under [database/](database/) or add a migration under [migrations/](migrations/), 3) expose the field in [pupils_view.php](pupils_view.php) and [pupils_list.php](pupils_list.php).
- To change auth for all pages: edit [includes/Auth.php](includes/Auth.php) or search for `require 'includes/Auth.php'` at top of pages.

**What I could not find (ask maintainers)**
- No automated tests or test runner discovered — confirm preferred testing strategy.
- No documented deployment scripts; check `DEPLOYMENT_CHECKLIST.md` and ask for CI/CD expectations.

Notes for AI agents
- Prefer small, focused edits: modify the smallest set of files that implement feature/bugfix, following existing naming patterns.
- When adding DB columns, provide an SQL migration under [migrations/] and keep a matching update to forms/views.
- Don't assume an MVC framework — changes are often localized to procedural pages.

Feedback
- If anything important is missing (deployment steps, CI, coding standards), tell me which area to expand and I will iterate.
