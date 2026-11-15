# Kanban — Task List

Legend: `[ ]` todo · `[x]` done. One task = one branch = one commit set:
`<type>(t<N>): <what>`. Details, acceptance criteria and risks live in
[`plan.md`](./plan.md).

## Phase 0 — Foundation

- [ ] **T1** Repo + scaffold + schema + Docker/XAMPP setup · M · deps: none
- [ ] **T2** Router + layout + base path (`/kanban`) · S · deps: T1
- [ ] **T3** Light/dark theme (cookie + user pref) · S · deps: T2
- [ ] **Checkpoint 1** boots on both OSes, theme persists, links right

## Phase 1 — Auth & session

- [ ] **T4** Signup + seed default board · M · deps: T2, T3
- [ ] **T5** Login/logout + throttle + session hardening · M · deps: T4
- [ ] **T6** Remember-me token cookie · S · deps: T5
- [ ] **T7** Guard + ownership + profile + avatar upload · M · deps: T5
- [ ] **Checkpoint 2** full auth flow, two accounts isolated

## Phase 2 — Boards, tasks, checklists

- [ ] **T8** Boards CRUD + navbar switcher · M · deps: T7
- [ ] **T9** Tasks CRUD (modal create/edit/delete/toggle) · M · deps: T8
- [ ] **T10** Task list + filters + `dd/mm` dates · S · deps: T9
- [ ] **T11** Kanban board + drag & drop + `<select>` fallback · M · deps: T9
- [ ] **T12** Checklists (`task_items`) + progress badge · M · deps: T9, T11
- [ ] **T13** Dashboard counts + status doughnut + 7-day bar · M · deps: T9, T12
- [ ] **Checkpoint 3** CRUD works, drag persists, dashboard per user

## Phase 3 — Routes & polish

- [ ] **T14** Calendar month grid · M · deps: T9
- [ ] **T15** Settings (theme, password, delete account) · S · deps: T6, T8
- [ ] **T16** Polish, `seed.sql`, README, smoke tests · S · deps: all
- [ ] **Checkpoint 4** fresh clone runs from the README on both OSes

## Per-commit checklist

- [ ] `php -l` clean on changed files
- [ ] `php tests/smoke.php` passes
- [ ] Manual check for this task passed
- [ ] No secrets — `app/config.php` never staged
- [ ] Message: `<type>(t<N>): <what>` + body explaining why
- [ ] One logical change — no formatting mixed in
- [ ] `git pull --rebase` done before push
