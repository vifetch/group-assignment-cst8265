# STRIDE Threat Model — YAPC Authentication

The assignment requires STRIDE analysis for the authentication roadmap.

| STRIDE category | Example YAPC threat | Secured response |
|---|---|---|
| Spoofing | Attacker logs in as another user using SQL injection or stolen credentials | Prepared statements, Bcrypt password verification, session regeneration |
| Tampering | Attacker changes authentication/session data | Server-side sessions, CSRF tokens, authorization checks |
| Repudiation | User denies changing a paste | Audit log records paste INSERT/UPDATE/DELETE with user ID and timestamp |
| Information Disclosure | Passwords or private paste contents exposed | Password hashing, encrypted private pastes, encoded output |
| Denial of Service | Excessive authentication requests | Rate limiting was not implemented in either version |
| Elevation of Privilege | Normal user accesses another user's private paste | Ownership checks and parameterized queries; public data is exposed through a restricted view |