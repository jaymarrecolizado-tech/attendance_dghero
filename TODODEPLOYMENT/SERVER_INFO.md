# Server info — digitalhero

| Field | Value |
|-------|--------|
| Domain | digitalhero.dictr2.cloud |
| Site user | digitalhero |
| IP address | 187.77.150.203 |
| Database name | dbdigitalhero |
| Database username | dbudigitalhero |
| Database password | See `.env.production` (`DB_PASS`) |
| Suggested SSH | `ssh digitalhero@187.77.150.203` |
| Suggested URL | https://digitalhero.dictr2.cloud |

## Database connection (app)

```
Host: localhost   # change if panel shows another MySQL host
Name: dbdigitalhero
User: dbudigitalhero
```

## First admin (create on server)

```bash
php scripts/seed_admin.php admin 'YourStrongPasswordHere'
```

Do not reuse the DB password as the admin login password.
