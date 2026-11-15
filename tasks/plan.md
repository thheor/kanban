# Implementation Plan: Kanban (Trello-style, native PHP + MariaDB + Bootstrap 5)

- **Repository:** https://github.com/thheor/kanban — public, MIT licensed
- **Design reference:** [Simple Kanban Board (Figma)](https://www.figma.com/design/sD71f85oie1rawt46fWYdI/Simple-Kanban-Board--Community-?node-id=13-1406)

## Overview

A private, single-account Trello: **boards → user-defined lists → cards**, with several
members per card, board-scoped labels, checklists, due dates, attachments, archive instead of
delete, and light/dark mode. Server-rendered PHP with `fetch` only for drag & drop and
inline checklists. No framework, no build step.

## Decisions

- **Routing:** one front controller (`public/index.php`) with a route table, so session
  setup, CSRF and the layout are wired once instead of per page.
- **Base path:** derived from `dirname($_SERVER['SCRIPT_NAME'])`. XAMPP yields `/kanban`,
  `php -S` yields `''`, no per-OS config.
- **Lists, not statuses:** columns are rows in `lists` (name + position), created, renamed,
  reordered and archived per board. The status enum is gone.
- **Completion:** each list can be flagged `is_done`. A card entering such a list gets
  `completed_at`; leaving clears it. That is what "completed" counts mean on the dashboard.
- **Members:** `board_members` (the owner adds existing accounts by email) plus
  `task_members` for several assignees per card. See the open question below.
- **Labels:** board-scoped, Trello palette, optional name, many per card through
  `task_labels`. This replaces my earlier invented `priority` enum.
- **Archive:** `archived_at` on boards, lists and cards. Nothing user-facing hard-deletes;
  permanent delete lives in the archive view behind a confirmation.
- **Attachments:** 5 MB, mime-whitelisted, stored in `storage/attachments/` and streamed by
  `/attachment/{id}` after an ownership check. Never served from the web root.
- **Positions:** integer `position` reindexed inside a transaction on every move
  (Trello uses fractional floats; unnecessary at this scale).
- **Charts:** Chart.js 4 via CDN, dark-mode aware. Status doughnut + 7-day completions bar.
- **Theme:** cookie only (`kanban_theme`), no database column. Bootstrap 5.3
  `data-bs-theme` is rendered server-side, `system` defers to `prefers-color-scheme`.
- **Driver:** prepared **mysqli** through `db_all`/`db_one`/`db_insert`/`db_exec`. Values are
  never interpolated into SQL.
- **Comments convention:** `declare(strict_types=1)` and a file docblock in every PHP file;
  every function documents `@param`/`@return`/`@throws`; inline comments explain only *why*.
- **Git:** trunk-based `main`, short-lived branches, conventional commits, one commit per
  task, LF pinned by `.gitattributes` for the Windows collaborator.

## Design reference (Figma)

Reference only — translate geometry into Bootstrap, never paste MCP output.

- Node: `13:1406` in file `sD71f85oie1rawt46fWYdI`
- Inventory: `Card` (avatar stack / description / checklist / labels), `Label/*` lane pills,
  `Tag/*` chips, `Checkbox/*` with struck-through text, 18px avatars with an SVG mask.
- Tokens: Black `#212121`, Grey `#4D4D4D`, Dark Grey `#5A5A65`, Light Grey `#E1E4E8`,
  Lavender `#F0E7F6`, Rose `#FFDCE0`, Green `#CBDFD8`; board title SF Compact Display 32,
  card title SF Pro Text Semibold 16, label 14/500, body 14/400.
- Mapping: lane pill → `lists` row, `Tag/*` → `labels`, card checklist → `task_items`,
  avatar stack → `task_members`. Fonts stay at Bootstrap/system defaults.

### Figma MCP

`opencode.json` is gitignored (machine-local tooling), so recreate it at the repo root:

```json
{
  "mcp": {
    "servers": {
      "figma": { "type": "remote", "url": "https://mcp.figma.com/mcp" }
    }
  }
}
```

Authenticate once in OpenCode, then pass the file URL. Useful calls: `get_metadata` (layer
tree), `get_screenshot` (visual target), `get_variable_defs` (tokens), `get_design_context`
(per-node code — pass a child id; the canvas can be rejected when nothing is selected in
the desktop app).

## Environments

### Linux (Docker)

```bash
docker compose up -d                 # mariadb:10.4 on 127.0.0.1:3307
cp config.example.php app/config.php
php -S 127.0.0.1:8000 -t public
```

`sql/schema.sql` is imported automatically when the volume is first created; re-import by
hand with `mysql -h127.0.0.1 -P3307 -ukanban -pkanban < sql/schema.sql`. If the Docker
daemon needs `sudo`, the same image runs under podman (command in README).

### Windows (XAMPP)

Clone into `C:\xampp\htdocs\kanban`, start Apache + MySQL, import `sql/schema.sql` in
phpMyAdmin, set `app/config.php` to port `3306`, user `root`, empty password, then open
`http://localhost/kanban`. No Docker.

## Data model

```
users           id, name, email UQ, password_hash, avatar_path, pronouns, job_title,
                department, about, role ENUM('owner','member'), failed_logins, locked_until
remember_tokens id, user_id, selector UQ, validator_hash, expires_at
boards          id, user_id (owner), name, description, assignee_id, due_date, position, archived_at
board_members   board_id, user_id, role ENUM('admin','member')          PK(board_id,user_id)
lists           id, board_id, name, position, is_done, archived_at
tasks           id, board_id, list_id, title, description, due_date, position,
                completed_at, archived_at
task_members    task_id, user_id                                          PK(task_id,user_id)
labels          id, board_id, name, color ENUM(10 Trello colours), position
task_labels     task_id, label_id                                         PK(task_id,label_id)
task_items      id, task_id, title, is_done, position
attachments     id, task_id, user_id, original_name, stored_name, mime, size_bytes
```

InnoDB, `utf8mb4_unicode_ci`, portable SQL only (XAMPP is MariaDB 10.4).

**Invariants the schema cannot enforce, so the app validates on save:**
- `tasks.board_id` must equal `lists.board_id` for the card's list.
- Every `task_labels.label_id` and `task_members.user_id` must belong to the card's board.
- `tasks.list_id` is `ON DELETE RESTRICT`: a list holding cards cannot be dropped. Archive
  it, or move its cards first.

## Routes

| Method | Path | Auth | Purpose |
|---|---|---|---|
| GET/POST | `/login` | – | sign in |
| GET/POST | `/signup` | – | register, seeds one board + three lists |
| POST | `/logout` | ✔ | destroy session + remember cookie |
| GET | `/` `/dashboard` | ✔ | counts, due today, overdue, charts |
| GET | `/board?id=` | ✔ | kanban view of the board's cards |
| GET | `/tasks?board=&list=&label=&member=&q=&due=` | ✔ | same cards as a table |
| POST | `/tasks/save` | ✔ | create + update a card |
| POST | `/tasks/move` | ✔ | DnD: list + position |
| POST | `/tasks/archive` · `/tasks/restore` · `/tasks/delete` | ✔ | archive lifecycle |
| POST | `/lists/save` · `/lists/move` · `/lists/archive` | ✔ | list CRUD |
| POST | `/boards/save` · `/boards/archive` | ✔ | board CRUD |
| POST | `/boards/members` | ✔ | add/remove members by email |
| POST | `/labels/save` · `/labels/delete` | ✔ | board label set |
| POST | `/items/save` · `/items/toggle` · `/items/delete` | ✔ | checklist |
| POST | `/attachments/upload` · `/attachments/delete` | ✔ | files |
| GET | `/attachment/{id}` | ✔ | streams one file after an ownership check |
| GET | `/calendar?board=&month=YYYY-MM` | ✔ | month grid on `due_date` |
| GET/POST | `/profile` | ✔ | name, pronouns, job title, department, about, avatar |
| GET/POST | `/settings` | ✔ | password change, delete account |
| GET | `/avatar/{id}` | – | streams `storage/avatars/*` |

## File layout

```
public/index.php          router: dispatch, CSRF, login guard, render
public/assets/app.js      theme toggle, drag & drop, inline checkbox/checklist
public/assets/app.css     only what Bootstrap cannot express (lanes, card strips)
app/config.php            gitignored, per-machine credentials
app/db.php                mysqli connection + query helpers
app/auth.php              current_user, require_login, permissions
app/helpers.php           e(), url(), redirect(), flash, CSRF, theme
app/pages/                one file per route (login, signup, dashboard, board, tasks, ...)
app/views/layout.php      shell; views/tasks-board.php + views/tasks-list.php for the two views
sql/schema.sql            single source of truth
storage/avatars/          uploads, streamed through PHP
storage/attachments/      uploads, streamed through PHP
tests/smoke.php           assert-based checks
tasks/                    this plan + todo.md
```

## Dashboard

Counts: total cards, completed (`completed_at IS NOT NULL`), open, overdue
(`due_date < CURDATE() AND completed_at IS NULL`). Lists: due today, overdue (top 5).
Charts: cards by list (doughnut), cards completed per day over 7 days (bar). Board filter,
default all boards.

## Git workflow

Two committers, one repository.

- Trunk-based: `main` always runnable. Parallel work takes `feature/t<N>-<slug>` and merges
  back within 1–3 days.
- One task = one commit set. Format `<type>(t<N>): <what>`, body explains **why**.
  Types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`.
- Commit only when `php -l` is clean, `php tests/smoke.php` passes, and the task's manual
  check passed. `git pull --rebase` before pushing; never force-push `main`.
- `app/config.php` and `opencode.json` are gitignored; neither ever reaches the remote.
- `.gitattributes` pins `eol=lf`; on Windows also set `core.autocrlf false`.
- Schema changes are commits to `sql/schema.sql`. Nobody hand-edits phpMyAdmin.
- No tags or `CHANGELOG.md` until the app is deployed; then tag `v1.0.0`.

## Tasks

### Done

- [x] **T1** Repo, scaffold, schema, Docker/XAMPP setup
- [x] **T2** Router, layout, base path
- [x] **T3** Light/dark theme (cookie)
- [x] **T4** Schema v2: lists, labels, members, attachments, archive
- [x] **T5** mysqli helpers replacing PDO
- [x] **T6** PHPDoc + `strict_types` convention pass

> **Checkpoint 1** — boots on both base paths, schema imports, theme persists. Verified.

### Phase 1 — Auth, permissions, profile

- [ ] **T7 Signup** — validate name/email/password, `password_hash`, PRG, auto-login, seed one
  board with three lists (To do / Doing / Done, the last flagged `is_done`).
  *AC:* duplicate email → field error; no plain-text password; new user lands on a usable
  board. *Deps:* T6. *M*
- [ ] **T8 Login + throttle** — `password_verify`, `session_regenerate_id(true)`, generic
  errors, 5 failures → 15-minute `locked_until`, full teardown on logout.
  *AC:* error never names the wrong field; 6th attempt locked; guest on a protected route
  redirects to `/login`. *Deps:* T7. *M*
- [ ] **T9 Remember me** — selector/validator cookie, 30 days, rotate on use, revoke on
  logout and password change. *AC:* DB leak alone cannot forge the cookie; expired token
  falls back to login. *Deps:* T8. *S*
- [ ] **T10 Permissions + ownership** — `require_board($id)` style guards: owner or
  `board_members` row, and every card/list/label/attachment resolved through its board.
  *AC:* a second account gets 404 on another user's board, card, attachment and avatar id;
  one helper, used by every route. *Deps:* T8. *M*
- [ ] **T11 Profile** — avatar upload (`finfo`, jpg/png/webp, ≤2 MB, random name, old file
  unlinked) plus name, pronouns, job title, department, about.
  *AC:* `.php` renamed `.jpg` rejected; avatar served only through `/avatar/{id}`, never
  directly; empty optional fields stay null, not `''`. *Deps:* T9. *M*

### Phase 2 — Boards, lists, cards

- [ ] **T12 Boards CRUD + archive** — dashboard grid, create/rename/delete, switcher,
  `assignee_id`, board `due_date`.
  *AC:* archive hides the board without losing cards; counts scoped to visible boards.
  *Deps:* T10. *M*
- [ ] **T13 Board members** *(only if the multi-assignee model stays)* — add an existing
  account by email, remove, prompt before removing someone who holds cards.
  *AC:* only the owner or a board admin can change members; a non-member cannot be assigned.
  *Deps:* T10, T12. *M*
- [ ] **T14 Lists CRUD** — create, rename, reorder, archive, toggle `is_done`.
  *AC:* reorder rewrites positions in one transaction; archiving keeps cards; a non-empty
  list cannot be hard-deleted (RESTRICT backs this up). *Deps:* T12. *M*
- [ ] **T15 Cards CRUD + archive** — title, description, list, due date, members, labels.
  *AC:* server re-validates every field and the list→board invariant; entering a done list
  sets `completed_at`; archive and restore both keep the card's checklist and files.
  *Deps:* T14, T13. *M*
- [ ] **T16 Labels** — board label set (name optional, colour, position), assign many per
  card. *AC:* a label from another board is rejected server-side; deleting a label removes
  its card links. *Deps:* T15. *S*
- [ ] **T17 Board view + list view** — one page, two templates: kanban with drag & drop
  (`/tasks/move`), table with filters (list, label, member, `q`, overdue).
  *AC:* a drag survives reload and reverts on a failed request; both views respect the same
  filters; positions never collide inside a list. *Deps:* T15, T16. *M*
- [ ] **T18 Checklists** — `task_items` CRUD, inline toggle, `2/3` badge on the card.
  *AC:* toggling updates the badge without a reload; deleting a card deletes its items.
  *Deps:* T15. *M*
- [ ] **T19 Attachments** — upload (5 MB, mime whitelist), download, delete.
  *AC:* files land outside the web root; `/attachment/{id}` checks board membership before
  streaming; a wrong mime is rejected even with a forged extension. *Deps:* T15. *M*
- [ ] **T20 Dashboard** — 4 counts, due today, overdue, two charts, board filter.
  *AC:* numbers match the filtered table view; one query per widget; charts re-render in
  dark mode; empty state for a new account. *Deps:* T19. *M*

> **Checkpoint 2** — two accounts are fully isolated; every card operation survives a
> reload; dashboard numbers reconcile with the table.

### Phase 3 — Remaining routes

- [ ] **T21 Calendar** — month grid on `due_date`, prev/next, click opens the card panel.
  *AC:* year boundary and leap day correct; no off-by-one under `Asia/Jakarta`.
  *Deps:* T15. *M*
- [ ] **T22 Settings** — change password (revokes remember tokens), delete account
  (typed confirmation, cascade, session killed). *AC:* no orphan rows after deletion; the
  theme cookie survives the logout. *Deps:* T9, T12. *S*
- [ ] **T23 Polish, seed, handoff** — 403/404, empty states, focus rings, dark-mode contrast,
  `sql/seed.sql`, README final, smoke green. *AC:* a fresh clone runs from the README on
  both OSes; `git status` clean. *Deps:* all. *S*

## Risks

| Risk | Impact | Mitigation |
|---|---|---|
| Permission bug exposes another account's board | **High** | Single `require_board()` chokepoint (T10) before any board UI; every card query joins through its board |
| Attachments served or executed from the web root | **High** | `storage/`, PHP streaming route, mime whitelist, `finfo` |
| CSRF missed on the `fetch` endpoints | High | Token in `<meta>`, router enforces it on every POST |
| Windows/Linux CRLF churn | Med | `.gitattributes` `eol=lf`, `core.autocrlf false` |
| MariaDB drift (XAMPP vs image) | Med | Portable SQL, `schema.sql` as the only source |
| Multi-assignee with one account per board | Med | `board_members` exists in schema; T13 decides whether it becomes real (see open question) |

## Open question

**Board sharing.** `board_members` and `task_members` are in the schema, but nothing writes
to them yet. Multi-assignee and `users.role` only mean something if other accounts can join a
board (option A: add members by email). Otherwise the honest fallback is a single
`tasks.assignee_id` (option C) and the member tables get dropped. Decide before T13.
