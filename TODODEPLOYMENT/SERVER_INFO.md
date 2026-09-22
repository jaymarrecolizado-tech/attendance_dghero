# Server info — digitalhero

| Field | Value |
|-------|--------|
| Domain | digitalhero.dictr2.cloud |
| Site owner (files) | digitalhero |
| SSH login | `dghero111` (not `digitalhero`) |
| IP address | 187.77.150.203 |
| App path | `/home/digitalhero/htdocs/digitalhero.dictr2.cloud` |
| Database name | See local `.env.vps` (`DB_NAME`) — currently `dghero` |
| Database username | See `.env.vps` (`DB_USER`) |
| Database password | See `.env.vps` (`DB_PASS`) — never commit |
| Suggested SSH | `ssh dghero111@187.77.150.203` |
| Suggested URL | https://digitalhero.dictr2.cloud |

## Database connection (app)

Use [`.env.vps`](../.env.vps) as the source of truth. Do not use the old panel names `dbdigitalhero` / `dbudigitalhero`.

Core attendance tables are **MyISAM**. `event_assignments` was created without InnoDB FKs so MariaDB would not fail with `1824 Failed to open the referenced table 'admins'`.

## First admin (create on server)

```bash
php scripts/seed_admin.php allfather 'YourStrongPasswordHere'
```

Do not reuse the DB password or the SSH password as the admin login password.
