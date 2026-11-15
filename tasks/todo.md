# Kanban — Task List

Legend: `[ ]` todo · `[x]` done. One task = one branch = one commit set:
`<type>(t<N>): <what>`. Acceptance criteria, invariants and risks live in
[`plan.md`](./plan.md).

## Done

- [x] **T1** Repo + scaffold + schema + Docker/XAMPP setup · M
- [x] **T2** Router + layout + base path (`/kanban`) · S
- [x] **T3** Light/dark theme, cookie only · S
- [x] **T4** Schema v2: lists, labels, members, attachments, archive · M
- [x] **T5** mysqli helpers replacing PDO · M
- [x] **T6** PHPDoc + `strict_types` pass · S
- [x] **Checkpoint 1** boots on both base paths, schema imports, theme persists

## Phase 1 — Auth, permissions, profile

- [ ] **T7** Signup + seed board with To do / Doing / Done · M · deps: T6
- [ ] **T8** Login/logout + throttle (`failed_logins`/`locked_until`) · M · deps: T7
- [ ] **T9** Remember-me token cookie · S · deps: T8
- [ ] **T10** `require_board()` permission chokepoint · M · deps: T8
- [ ] **T11** Profile: avatar + pronouns/job title/department/about · M · deps: T9
- [ ] **Checkpoint** two accounts isolated, uploads safe

## Phase 2 — Boards, lists, cards

- [ ] **T12** Boards CRUD + archive + assignee + due date · M · deps: T10
- [ ] **T13** Board members by email *(only if multi-assignee stays)* · M · deps: T10, T12
- [ ] **T14** Lists CRUD + reorder + `is_done` · M · deps: T12
- [ ] **T15** Cards CRUD + archive + completion · M · deps: T13, T14
- [ ] **T16** Labels (board set, many per card) · S · deps: T15
- [ ] **T17** Board view (DnD) + list view + filters · M · deps: T15, T16
- [ ] **T18** Checklists + `2/3` badge · M · deps: T15
- [ ] **T19** Attachments (upload/download/delete) · M · deps: T15
- [ ] **T20** Dashboard counts + 2 charts · M · deps: T19
- [ ] **Checkpoint** isolation holds, drag persists, dashboard reconciles

## Phase 3 — Remaining routes

- [ ] **T21** Calendar month grid · M · deps: T15
- [ ] **T22** Settings: password, delete account · S · deps: T9, T12
- [ ] **T23** Polish, `seed.sql`, README, smoke green · S · deps: all

## Per-commit checklist

- [ ] `php -l` clean on changed files
- [ ] `php tests/smoke.php` passes
- [ ] Manual check for this task passed
- [ ] No secrets — `app/config.php` and `opencode.json` never staged
- [ ] Message: `<type>(t<N>): <what>` + body explaining why
- [ ] One logical change — no formatting mixed in
- [ ] `git pull --rebase` done before push
