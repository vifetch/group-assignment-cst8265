# YAPC Vulnerable Application

This application is the intentionally vulnerable app I made for the CST8265 group assignment.

## Run

```bash
php setup.php # first time db initialization
php -S localhost:8001 -t public
```

## Demonstrations

### SQL injection Demo
```text
Username: admin' --
Password: anything
```
### Stored XSS Demo

```html
<script>alert('YAPC XSS demo')</script>
```

### Password/session management
Passwords are intentionally stored in plaintext. Session IDs are not regenerated after login and the cookie is not hardened.

### Database security
The application connects directly to the SQLite database and has no audit trail or least-privilege separation.