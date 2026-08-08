# Attack Demonstration Matrix

| Test | yapc-vulnerable | yapc-secured |
|---|---|---|
| SQLi login: `admin' --` | Authentication can be bypassed | Authentication fails |
| Stored XSS: `<script>alert('XSS')</script>` | Script executes when rendered | Text is encoded and CSP blocks scripts |
| Plaintext password inspection | Password is directly readable in DB | Bcrypt hash is stored |
| Session fixation test | Session ID is not regenerated on login | Session ID regenerates after login |
| Private paste inspection | Content is plaintext | Content is encrypted at rest |
| Paste audit | No audit trail | Triggered audit records exist |
| Backup | Manual SQLite copy | Backup and documented recovery workflow |
