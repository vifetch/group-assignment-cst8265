# YAPC Secured Application

This is the secured app for the CST8265 group final project. It implements the mitigations described in my complete proposal.

## Run

```bash
php setup.php
php -S localhost:8002 -t public
```

## Security controls

### SQL injection
All database operations involving user input use prepared statements. The public paste list is read through the `public_pastes` view.

### XSS
User-controlled text is encoded with `htmlspecialchars()` before being placed into HTML. The response also sends a restrictive Content Security Policy through the HTML meta policy. Unlike the vulnerable version, the CSS is loaded from style.css as inline CSS is forbidden due to the CSP.

### Authentication
Passwords are stored with Bcrypt using PHP's password hashing API.

### Session management
The application:
- regenerates the session ID after successful authentication;
- uses HttpOnly cookies;
- uses SameSite=Strict;
- uses a one-hour inactivity timeout;
- uses Secure when HTTPS is active (or when `YAPC_FORCE_SECURE_COOKIE=1` is set);
- destroys the session during logout.

### CSRF
State-changing POST requests require a per-session CSRF token.

### Database security
The application uses:
- foreign keys;
- a public-only database view;
- an audit table;
- INSERT/UPDATE/DELETE triggers for paste changes;
- parameterized queries;
- no direct HTML rendering from database content.

Since I'm using sqlite, there is no true full DBMS role isolation.

The encryption key at `storage/encryption.key` must also be preserved securely. The key is deliberately not stored in the database.

### Cryptography
Private paste contents are encrypted at rest using AES-256-GCM with a random IV and authentication tag. The key is a separate 32-byte file in `storage/`.