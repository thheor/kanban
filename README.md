# Kanban

Kanban board web application to improve Agile development team.

Native PHP 8 + MariaDB + Bootstrap 5, no framework, no build step.

## Requirements

- PHP 8.1+ with `pdo_mysql`, `fileinfo`, `mbstring`
- MariaDB 10.4+ (ships with XAMPP) or Docker

Check the driver before anything else: `php -m | grep pdo_mysql`. Arch Linux ships the
extension commented out in `php.ini`; enable it there or run PHP with
`-d extension=pdo_mysql`.

## Setup — Linux (Docker)

```bash
docker compose up -d                                  # MariaDB on 127.0.0.1:3307
cp config.example.php app/config.php                  # Docker defaults already match
php -S 127.0.0.1:8000 -t public                       # http://127.0.0.1:8000
```

`sql/schema.sql` is imported automatically the first time the volume is created. To
re-import by hand:

```bash
mysql -h127.0.0.1 -P3307 -ukanban -pkanban < sql/schema.sql
```

If the Docker daemon needs `sudo` on your machine and you keep podman around instead, the
same image works:

```bash
podman run -d --name kanban-db -e MARIADB_DATABASE=kanban -e MARIADB_USER=kanban \
  -e MARIADB_PASSWORD=kanban -e MARIADB_ROOT_PASSWORD=root -p 3307:3306 \
  -v "$PWD/sql/schema.sql:/docker-entrypoint-initdb.d/01-schema.sql:ro,Z" \
  docker.io/library/mariadb:10.4
```

If edits stop showing up while `php -S` is running, CLI opcache is holding files for up to
180s — restart the server or add `-d opcache.enable_cli=0`.

## Setup — Windows (XAMPP)

1. Clone into `C:\xampp\htdocs\kanban`.
2. XAMPP Control Panel → start **Apache** and **MySQL**.
3. `http://localhost/phpmyadmin` → Import → choose `sql/schema.sql` (creates database `kanban`).
4. Copy `config.example.php` to `app/config.php`, set `port` to `3306`, `user` to `root`,
   `pass` to `''`.
5. Open `http://localhost/kanban`.

No Docker on Windows. `BASE_PATH` is detected from the request, so links work under
`http://localhost/kanban/` and `http://127.0.0.1:8000/` without edits.

## Layout

```
public/            web root — the only directory the browser may reach
  index.php        router: dispatch, auth guard, CSRF, render
  assets/app.js    theme toggle, later drag & drop
app/
  config.php       gitignored, per-machine credentials
  db.php           PDO
  auth.php         session guard
  helpers.php      e(), url(), redirect(), flash, CSRF
  pages/           one file per route
  views/layout.php shared shell
sql/schema.sql     single source of truth for the database
storage/avatars/   uploads, streamed through PHP (never served directly)
tasks/             plan.md (decisions, acceptance criteria) + todo.md
tests/smoke.php    assert-based checks: php tests/smoke.php
```

## Docs

- Plan, acceptance criteria, risks: [`tasks/plan.md`](tasks/plan.md)
- Task checklist: [`tasks/todo.md`](tasks/todo.md)

## License

MIT — see [LICENSE](LICENSE).
