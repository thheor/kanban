# Implementation Plan: Kanban (native PHP + MariaDB + Bootstrap 5)

- **Repository:** https://github.com/thheor/kanban — public, MIT licensed
- **Design reference:** [Simple Kanban Board (Figma)](https://www.figma.com/design/sD71f85oie1rawt46fWYdI/Simple-Kanban-Board--Community-?node-id=13-1406)

## Overview

A multi-board kanban app on plain PHP. One front controller enforces session auth, CSRF, and
layout for every route. Data is `users → boards → tasks → task_items`. Full CRUD on boards,
tasks and checklist items. Light/dark theme via Bootstrap 5.3 `data-bs-theme`. The dashboard
carries the numbers (total / completed / incomplete / overdue) and two charts.

Deployed two ways from the same code, with no conditional logic: Docker on Linux, XAMPP on
Windows. Both developers commit to the same repository.

## Decisions

- **Routing**: single front controller `public/index.php`, dispatch table, one auth guard.
  (Not a graded assignment, so no "one file per page" constraint.)
- **Base path**: derived at runtime from `dirname($_SERVER['SCRIPT_NAME'])`. XAMPP yields
  `/kanban`, `php -S` yields `''`. No per-OS config.
- **Date format**: `dd/mm/yyyy` on screen, `YYYY-MM-DD` in the DB, timezone `Asia/Jakarta`.
- **Boards**: multiple per user, full CRUD, navbar switcher, cascade delete.
- **Checklists**: in MVP (`task_items`) with a "2/3" progress badge on the card, as in the
  Figma reference.
- **Charts**: Chart.js 4 via CDN (no build step), dark-mode aware. Two charts: status
  doughnut and a 7-day completed-tasks bar. Decided over hand-rolled CSS bars because two
  charts are more than "a few lines" to hand-roll correctly.
- **Dark mode**: `data-bs-theme` rendered server-side from cookie → user pref → `system`.
  No theme flash, no custom color CSS.
- **Uploads**: avatar only, stored in `storage/avatars/` (outside the web root) and streamed
  by `/avatar/{id}`. `php -S` and XAMPP both serve uploads as executable if left in the web
  root, so they never live there.
- **Drag & drop**: HTML5 DnD → `fetch` POST with CSRF → optimistic UI, revert on error. A
  `<select>` fallback ships in the same task for keyboard and no-JS users.
- **Git**: trunk-based `main`, short-lived branches, conventional commits, one commit per
  task, `.gitattributes` pins LF so Windows and Linux never fight over line endings.

## Design reference (Figma)

The source of the visual language. Read it, don't copy its numbers verbatim: lanes are fixed
308px in the file, ours are a responsive Bootstrap grid.

- Node to design against: `13:1406` in file `sD71f85oie1rawt46fWYdI`
- Component inventory: `Card` (avatar stack / content / task list / labels, each toggleable),
  `Label/{Grey,Lavender,Rose,Green}` lane pills, `Tag/{Pink,Grey,Green}` chips,
  `Checkbox/{Unchecked,Checked}` with struck-through text, `Avatar`/`Vic` 18px with an SVG mask.
- Tokens: Black `#212121`, Grey `#4D4D4D`, Dark Grey `#5A5A65`, Light Grey `#E1E4E8`,
  Lavender `#F0E7F6`, Rose `#FFDCE0`, Green `#CBDFD8`. Type: board title SF Compact Display
  Bold 32, card title SF Pro Text Semibold 16, label 14/500, body 14/400.
- Mapping into this stack: lane pill → `status`, `Tag/*` → priority / due badge, card task
  list → `task_items`. Fonts stay at Bootstrap/system defaults; the file's SF fonts aren't
  worth licensing.

### Figma MCP

`opencode.json` is gitignored because it holds machine-local tooling, so recreate it at the
repo root to let an agent read the design directly:

```json
{
  "mcp": {
    "servers": {
      "figma": { "type": "remote", "url": "https://mcp.figma.com/mcp" }
    }
  }
}
```

Authenticate once in OpenCode, then ask for the file by URL. Useful calls: `get_metadata`
(layer tree), `get_screenshot` (the visual target), `get_variable_defs` (the token list
above), `get_design_context` (per-node code — pass a child node id; the top-level canvas can
be rejected when nothing is selected in the Figma desktop app).

Rule: MCP output is a reference. It emits React + Tailwind, ours is PHP + Bootstrap, so
translate geometry into Bootstrap classes and never paste the generated markup.

## Environments

### Linux (Docker)

```bash
docker compose up -d                 # mariadb:10.4 on 127.0.0.1:3307, db kanban, kanban/kanban
mysql -h127.0.0.1 -P3307 -ukanban -pkanban kanban < sql/schema.sql
php -S 127.0.0.1:8000 -t public      # http://127.0.0.1:8000
```

Port 3307 so a future local MySQL install cannot clash.

### Windows (XAMPP — no Docker)

1. Clone the repo into `C:\xampp\htdocs\kanban`.
2. XAMPP Control Panel → start **Apache** and **MySQL**.
3. `http://localhost/phpmyadmin` → Import → `sql/schema.sql` (creates database `kanban`).
4. `cp config.example.php config.php`, then set `DB_PORT` `3306`, `DB_USER` `root`,
   `DB_PASS` `''` (XAMPP defaults).
5. Open `http://localhost/kanban`.

`docker-compose.yml` is never used on Windows. `sql/schema.sql` is the only schema source;
nobody hand-edits tables in phpMyAdmin.

## Data model

```sql
users(id, name, email UNIQUE, password_hash, avatar_path NULL,
      theme ENUM('light','dark','system') DEFAULT 'system',
      failed_logins TINYINT DEFAULT 0, locked_until DATETIME NULL,
      created_at, updated_at)

remember_tokens(id, user_id FK, selector CHAR(24) UNIQUE, validator_hash CHAR(64),
                expires_at, created_at)

boards(id, user_id FK, name, description NULL, position INT, created_at, updated_at)

tasks(id, board_id FK, title, description NULL,
      status ENUM('not_started','in_progress','blocked','done') DEFAULT 'not_started',
      priority ENUM('low','normal','high') DEFAULT 'normal',
      due_date DATE NULL, position INT, completed_at DATETIME NULL,
      created_at, updated_at)

task_items(id, task_id FK, title, is_done TINYINT(1) DEFAULT 0, position INT)
```

InnoDB, `utf8mb4_unicode_ci`, `ON DELETE CASCADE`, portable SQL only (the Windows XAMPP runs
MariaDB 10.4 — no MySQL-8-only syntax). Indexes: `(user_id)`,
`(board_id, status, position)`, `(due_date)`, `(task_id, position)`.

## Routes

| Method | Path | Auth | Purpose |
|---|---|---|---|
| GET/POST | `/login` | – | sign in |
| GET/POST | `/signup` | – | register + seed default board |
| POST | `/logout` | ✔ | destroy session + remember cookie |
| GET | `/dashboard` | ✔ | summary, charts, due today / overdue |
| GET | `/board?id=` | ✔ | kanban, 4 lanes |
| POST | `/tasks/status` | ✔ | DnD status/position update (fetch) |
| GET | `/tasks` | ✔ | filterable table (board, status, search, overdue) |
| POST | `/tasks/save` | ✔ | create + update |
| POST | `/tasks/delete` | ✔ | delete |
| POST | `/tasks/toggle` | ✔ | complete / uncomplete |
| POST | `/items/save` · `/items/toggle` · `/items/delete` | ✔ | checklist CRUD |
| GET | `/calendar?board=&month=YYYY-MM` | ✔ | month grid |
| POST | `/boards/save` · `/boards/delete` | ✔ | board CRUD |
| GET/POST | `/profile` | ✔ | name/email, password, avatar |
| GET/POST | `/settings` | ✔ | theme pref, change password, delete account |
| GET | `/avatar/{id}` | – | streams `storage/avatars/*` |

## File layout

```
docker-compose.yml          mariadb:10.4 + volume (Linux only)
.gitignore  .gitattributes  config.example.php  README.md
sql/schema.sql              single source of truth, phpMyAdmin-importable
sql/seed.sql                demo data (T16)
public/index.php            router + auth guard + CSRF + dispatch
public/assets/app.css       only what Bootstrap cannot do (~80 lines)
public/assets/app.js        theme toggle, DnD, chart bootstrapping
app/config.php              (gitignored)
app/db.php                  PDO, ERRMODE_EXCEPTION, prepared statements only
app/auth.php                current_user, require_login, login/logout, remember-me
app/helpers.php             e(), url(), redirect(), flash(), csrf_*, date helpers
app/pages/{login,signup,dashboard,board,tasks,calendar,profile,settings}.php
app/views/layout.php        nav, flash, theme attribute, Bootstrap + Chart.js CDN
storage/avatars/            (gitignored, keeps .gitkeep)
tests/smoke.php             assertions: e(), url() base path, upload validation, date format
```

## Dashboard

Counts from one conditional-aggregation query: total, completed, incomplete, overdue
(`due_date < CURDATE() AND status <> 'done'`). Lists: due today and overdue (top 5 each).
Charts: doughnut of tasks by status, bar of tasks completed per day over the last 7 days.
Board filter dropdown, defaulting to all boards.

## Git workflow

Two committers (Linux + Windows) on one repository, so the rules are slightly stricter than
solo work.

- `git init` in T1; `.gitignore` and `.gitattributes` land in the very first commit.
- **Trunk-based.** `main` is always runnable. If both of you are working the same day, each
  takes a separate task and a separate short-lived branch:
  `feature/t<N>-<slug>` → merge back within 1–3 days → delete the branch.
- **One task = one branch = one commit set.** Never two tasks in one commit, never a
  formatting-only change mixed with behavior.
- **Before every push:** `git pull --rebase origin main`. Never force-push `main`.
- Message format `<type>(t<N>): <what>`, blank line, then a body explaining **why**.
  Types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`.
- Commit only when `php -l` is clean, `php tests/smoke.php` passes, and the task's manual
  check passed.
- No secrets: `app/config.php` and `opencode.json` are gitignored from day one. Windows needs
  its own `config.php` with the XAMPP credentials; neither file is ever committed.
- `.gitignore`: `app/config.php`, `opencode.json`, `storage/avatars/*`,
  `!storage/avatars/.gitkeep`, `vendor/`, `*.log`, `.idea/`, `.vscode/`, `Thumbs.db`,
  `desktop.ini`, `.env`.
- `.gitattributes`: `* text=auto eol=lf`, plus `*.png binary`, `*.jpg binary`. On Windows also
  run `git config --global core.autocrlf false` so the attribute file is the only authority.
- No tags or `CHANGELOG.md` while this is a private, undeployed project. Once it is deployed,
  tag `v1.0.0` and keep a changelog grouped by Added / Changed / Fixed.
- **Conflict habit:** `sql/schema.sql` and `app/pages/*.php` are the likely collision points.
  Assign pages so no two people edit the same file in the same week; if a conflict hits
  `schema.sql`, the resolution is a re-import on both machines, never a hand merge.

## Tasks

### Phase 0 — Foundation

- [x] **T1 Repo, scaffold, schema, both environments** — `git init`, `.gitignore`,
  `.gitattributes`, `config.example.php`, `db.php`, `helpers.php`, `sql/schema.sql`,
  `docker-compose.yml`, README with both setup paths.
  *AC:* schema imports in Docker **and** phpMyAdmin; PDO connects on both; DB errors log and
  never print; first commit holds scaffolding only.
  *Files:* ~10 new. *Scope:* M. *Deps:* none.
- [x] **T2 Router, layout, base path** — dispatch, 404 page, nav, flash, CDN tags, `url()`
  honoring `/kanban`.
  *AC:* `/login` and `/signup` render inside the layout; unknown path → 404; every generated
  link works under `http://127.0.0.1:8000/` and `http://localhost/kanban/`.
  *Scope:* S. *Deps:* T1.
- [x] **T3 Theme** — `data-bs-theme` from cookie → user pref → system; toggle writes the
  cookie and, when logged in, the user pref.
  *AC:* persists across reload, no theme flash, works logged out.
  *Scope:* S. *Deps:* T2.

> **Checkpoint 1** — app boots in both environments, theme persists, links correct under `/kanban`.

### Phase 1 — Auth & session

- [ ] **T4 Signup** — validation, `password_hash`, unique email, PRG, auto-login, seed board.
  *AC:* duplicate email → field error; no plain-text password stored; new user lands on
  `/dashboard` with one board. *Scope:* M. *Deps:* T2, T3.
- [ ] **T5 Login/logout + throttle** — `password_verify`, `session_regenerate_id(true)`,
  generic errors, 5 failures → 15-minute lock, full session destroy on logout.
  *AC:* the error never reveals which field is wrong; the 6th attempt is locked; logged-out
  `/board` redirects to `/login`. *Scope:* M. *Deps:* T4.
- [ ] **T6 Remember me** — selector/validator cookie, 30 days, rotated on use, revoked on
  logout and on password change.
  *AC:* a stolen DB row alone cannot forge the cookie; an expired token falls back to login.
  *Scope:* S. *Deps:* T5.
- [ ] **T7 Guard, ownership, profile, avatar** — `require_login`, `own_board/own_task/own_item`,
  profile updates, avatar upload (`finfo` jpg/png/webp, ≤2 MB, random name, old file unlinked).
  *AC:* a `.php` renamed to `.jpg` is rejected; avatar served only through `/avatar/{id}`;
  other users' boards/tasks/items return 404, never data. *Scope:* M. *Deps:* T5.

> **Checkpoint 2** — signup → logout → remember-me → profile → password change; two accounts
> are fully isolated.

### Phase 2 — Boards, tasks, checklists

- [ ] **T8 Boards CRUD** — dashboard grid with counts, create/rename/delete, navbar switcher.
  *AC:* deleting a board cascades its tasks and items; every query is scoped by `user_id`;
  the switcher keeps you on the current page. *Scope:* M. *Deps:* T7.
- [ ] **T9 Tasks CRUD** — Bootstrap modal create/edit, delete confirm, toggle done (title,
  description, board, status, priority, due_date).
  *AC:* the server re-validates every field; PRG prevents duplicate submit; `completed_at`
  follows status. *Scope:* M. *Deps:* T8.
- [ ] **T10 Task list** — table with filters (board, status, overdue, search) and sort,
  `dd/mm` dates, overdue badge.
  *AC:* filters compose in the query string and survive reload; empty state renders.
  *Scope:* S. *Deps:* T9.
- [ ] **T11 Kanban board + DnD** — 4 lanes, cards with title/description/priority badge/due
  tag/checklist progress, lane counts, DnD via fetch, `<select>` fallback.
  *AC:* a drag persists after reload; a failed request reverts the card; `position` reindexes
  so no lane ever holds duplicate positions. *Scope:* M. *Deps:* T9.
- [ ] **T12 Checklists** — `task_items` CRUD inside the task modal, inline toggle, "2/3" badge
  on the card.
  *AC:* toggling updates progress without a page reload; deleting a task deletes its items; a
  task with no items hides the badge. *Scope:* M. *Deps:* T9, T11.
- [ ] **T13 Dashboard summary + charts** — 4 counts, due-today and overdue lists, status
  doughnut, 7-day completions bar.
  *AC:* counts match the task list for the same filter; one query per widget (no N+1); charts
  re-render correctly in dark mode; a new user sees zeros, not errors.
  *Scope:* M. *Deps:* T9, T12.

> **Checkpoint 3** — full CRUD on boards/tasks/items, drag persists, both accounts isolated,
> dashboards differ per user.

### Phase 3 — Remaining routes & polish

- [ ] **T14 Calendar** — month grid, prev/next, tasks placed on `due_date`, click opens the
  task modal.
  *AC:* December → January and leap-year math correct; no off-by-one day under `Asia/Jakarta`.
  *Scope:* M. *Deps:* T9.
- [ ] **T15 Settings** — theme preference, change password (revokes remember tokens), delete
  account (typed confirmation, cascade, session killed).
  *AC:* the DB theme wins over the cookie after login; a deleted account leaves no rows
  anywhere. *Scope:* S. *Deps:* T6, T8.
- [ ] **T16 Polish, seed, handoff** — 403/404, empty states, focus rings, dark-mode contrast
  pass, `sql/seed.sql` demo data, README final, `tests/smoke.php` green, `.gitignore` audit.
  *AC:* a fresh clone runs from the README alone on both OSes; `git status` clean; smoke tests
  pass. *Scope:* S. *Deps:* all.

## Risks

| Risk | Impact | Mitigation |
|---|---|---|
| Windows/Linux CRLF churn | Med | `.gitattributes` `eol=lf` in the first commit; `core.autocrlf false` on Windows |
| MariaDB version drift (XAMPP vs `mariadb:10.4`) | Med | Portable SQL only; `schema.sql` is the sole source |
| Uploads executable under XAMPP | High | Stored outside the web root, streamed via PHP, `finfo` + whitelist |
| CSRF missed on fetch endpoints | High | Token in `<meta>`; the router enforces `csrf_check()` on every POST |
| DnD unusable without JS | Low | `<select>` fallback ships with T11 |
| Two committers, one schema | Med | Nobody hand-edits phpMyAdmin; schema changes are commits; conflicts resolved by re-import |
| Same-file collisions between the two of you | Med | One owner per file per week; branches merge within 1–3 days |
