# Project 3 — Vulnerable Web App: Attack & Defend

A deliberately-vulnerable PHP login application, built to demonstrate 5 common web
vulnerabilities (OWASP-relevant) and the fix for each. Built and tested locally on Kali Linux.

> All work performed on `localhost` (`127.0.0.1`) in an isolated lab. Sensitive values
> (credentials, secrets) are redacted as `***` in this document.

## Stack
- **OS:** Kali Linux
- **Web:** PHP built-in dev server (`php -S 127.0.0.1:8000`)
- **DB:** MariaDB 11.8.6
- **Tools:** curl, Hydra

## Vulnerabilities covered
| # | Attack | Fix |
|---|--------|-----|
| 1 | SQL Injection (login bypass) | Prepared statements |
| 2 | Stored XSS | Output encoding (`htmlspecialchars`) |
| 3 | Reflected XSS | Output encoding |
| 4 | Brute Force | Rate limiting + lockout |
| 5 | Directory Traversal | Path validation (`realpath`) + Apache `-Indexes` |

---

# Stage 0 — Database Setup  ✅ DONE

Started MariaDB and created an isolated database, a dedicated low-priv user, and a
`users` table seeded with test accounts.

```sql
CREATE DATABASE vulnapp;
CREATE USER 'vulnuser'@'localhost' IDENTIFIED BY '***';
GRANT ALL PRIVILEGES ON vulnapp.* TO 'vulnuser'@'localhost';
FLUSH PRIVILEGES;

USE vulnapp;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  bio TEXT
);
INSERT INTO users (username, password, bio) VALUES
  ('admin', '***', 'Site administrator'),
  ('rick',  '***', 'I am pickle Rick'),
  ('morty', '***', 'aw geez');
```

**Result — table seeded successfully:**
```
+----+----------+----------+
| id | username | password |
+----+----------+----------+
|  1 | admin    | ***      |
|  2 | rick     | ***      |
|  3 | morty    | ***      |
+----+----------+----------+
3 rows in set
```

> Note: passwords stored in **plaintext** intentionally at this stage — this is itself a
> vulnerability, fixed later with `password_hash()`/`password_verify()`.

---

# Stage 1 — Build the Vulnerable App  ✅ DONE

Created 7 PHP files plus demo content, served via `php -S 127.0.0.1:8000`.

| File | Purpose | Deliberate flaw |
|------|---------|-----------------|
| `config.php` | DB constants | — |
| `db.php` | mysqli connection | — |
| `login.php` | login form | SQL via string concatenation → **SQLi** |
| `register.php` | sign-up + bio | concatenation + stores bio raw → **SQLi + stored XSS** |
| `dashboard.php` | post-login page | echoes bio and `?msg=` unencoded → **stored + reflected XSS** |
| `download.php` | file fetcher | joins `?file=` to path, no validation → **path traversal** |
| `logout.php` | session destroy | — |

Demo targets: `files/welcome.txt` (public), `private/secret.txt` (contains a flag + creds).

**Verification:**
- `php -l` on all files → no syntax errors
- DB connectivity test → `users in DB: 3`
- `GET /login.php` → HTTP 200 (login page renders in browser)

---

# Stage 2 — Exploit (5 attacks)  ✅ DONE

## Attack 1 — SQL Injection (login bypass)
**Payload (username field):** `admin'-- -`  (password = anything)
The query becomes `... WHERE username = 'admin'-- -' AND password = '...'` — the `--` comments
out the password check, so it logs in as admin with no valid password.
```
POST /login.php  ->  HTTP 302  Location: dashboard.php
Dashboard: "Welcome, admin!"
```
✅ Authenticated as admin without credentials.

**Proof:**
![SQL Injection — payload + admin dashboard](screenshots/01-sqli.png)

## Attack 2 — Stored XSS
**Payload (bio field at registration):** `<script>alert(document.cookie)</script>`
Stored in the DB and rendered **unencoded** on every dashboard view → JS executes for any
visitor of that profile (popup fires in browser).
```
GET /dashboard.php  ->  <div class="box"><script>alert(document.cookie)</script></div>
```
✅ Persistent JavaScript injection. (Note: payloads containing `'` break the INSERT because of
the SQLi flaw — a quote-free payload was used.)

**Proof:**
![Stored XSS — alert popup on login as evil](screenshots/02-stored-xss.png)

## Attack 3 — Reflected XSS
**Payload (URL):** `/dashboard.php?msg=<script>alert('REFLECTED-XSS')</script>`
The `msg` parameter is echoed straight into the page → executes when a victim opens the link.
```
<div class="box" ...><script>alert('REFLECTED-XSS')</script></div>
```
✅ Non-persistent injection via crafted URL (classic phishing/cookie-theft vector).

**Proof:**
![Reflected XSS — alert popup from crafted URL](screenshots/03-reflected-xss.png)

## Attack 4 — Brute Force (Hydra)
No rate limiting → unlimited password guesses.
```
hydra -l rick -P pwlist.txt 127.0.0.1 -s 8000 \
  http-post-form "/login.php:username=^USER^&password=^PASS^:Invalid credentials" -f
[8000][http-post-form] host: 127.0.0.1   login: rick   password: ***
```
✅ Password recovered (`rick`'s password = `***`).

**Proof:**
![Brute Force — Hydra recovering the password](screenshots/04-bruteforce.png)

## Attack 5 — Directory Traversal
**Payload (URL):** `/download.php?file=../../../../../../etc/passwd`
`?file=` is concatenated to the base path with no validation, so `../` escapes the folder.
```
?file=welcome.txt              -> "Public download. Nothing secret here."
?file=../private/secret.txt    -> FLAG{***} + DB creds (***)
?file=../../../../etc/passwd   -> root:x:0:0:root:/root:/usr/bin/zsh ...
```
✅ Arbitrary file read, including `/etc/passwd` and the app's own "private" secret.

**Proof:**
![Directory Traversal — /etc/passwd leaked in browser](screenshots/05-traversal.png)

---

# Stage 3 — Remediate + Re-test  ✅ DONE

Hardened files: `login_safe.php`, `register_safe.php`, `dashboard_safe.php`,
`download_safe.php`, plus `.htaccess` (Apache). Each attack was re-run against the safe
version to prove it is blocked.

## Fix 1 — SQL Injection → Prepared statements
```php
$stmt = $conn->prepare('SELECT password FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username);
```
**Re-test:** `admin'-- -` → `HTTP 200` + "Invalid credentials" (no login). ✅ Blocked.

## Fix 2 — Stored XSS → Output encoding
```php
echo htmlspecialchars($bio, ENT_QUOTES, 'UTF-8');
```
**Re-test:** bio renders as `&lt;script&gt;alert(document.cookie)&lt;/script&gt;` (inert text). ✅ Blocked.

**Proof (optional):**
![Fix — XSS rendered as inert text on dashboard_safe.php](screenshots/07-fix-xss.png)

## Fix 3 — Reflected XSS → Output encoding
Same `htmlspecialchars()` applied to `?msg=`.
**Re-test:** payload renders as `&lt;script&gt;alert(1)&lt;/script&gt;`. ✅ Blocked.

## Fix 4 — Brute Force → Rate limiting + lockout
Track failed attempts per session; lock for 300s after 5 failures.
**Re-test:** attempts 1–5 = "Invalid credentials", attempt 6+ = "Locked for 300s" —
and the **correct** password is also refused while locked. ✅ Blocked.

> Caveat (honest, important for portfolio): session-based lockout is bypassable because an
> attacker can discard cookies. Production should track failures **server-side by IP +
> username** (or use CAPTCHA / exponential backoff). This demo shows the mechanism.

## Fix 5 — Directory Traversal → Path validation + Apache hardening
```php
$file = basename($_GET['file']);            // strip ../ parts
$path = realpath($base . '/' . $file);      // resolve real path
// reject if $path escapes $base
```
Plus `.htaccess`: `Options -Indexes`, deny `config.php`/`db.php`, block `private/`.
**Re-test:** `welcome.txt` works; `../private/secret.txt` and `../../../etc/passwd` → "Access denied". ✅ Blocked.

**Proof (optional):**
![Fix — traversal attempt blocked with Access denied](screenshots/06-fix-traversal.png)

---

# Summary

| # | Attack | Before | After (fixed) | Technique |
|---|--------|--------|---------------|-----------|
| 1 | SQL Injection | Logged in as admin, no password | "Invalid credentials" | Prepared statements |
| 2 | Stored XSS | `<script>` executes | Rendered inert | `htmlspecialchars()` |
| 3 | Reflected XSS | `<script>` executes | Rendered inert | `htmlspecialchars()` |
| 4 | Brute Force | Password cracked by Hydra | Locked after 5 tries | Rate limit + lockout |
| 5 | Directory Traversal | Read `/etc/passwd` | "Access denied" | `realpath()` + `-Indexes` |

**Key takeaways**
- Two root causes covered most of the impact: **trusting user input** (1,2,3,5) and **no
  abuse controls** (4).
- Defenses live at the right layer: parameterize at the **query**, encode at **output**,
  validate paths at the **filesystem boundary**, throttle at the **auth** layer.
- Plaintext password storage was itself a flaw; production fix = `password_hash()` /
  `password_verify()`.

_All testing performed locally on `127.0.0.1`; credentials/secrets redacted as `***`._

# Stage 3 — Remediate + Re-test  ⬜ TODO
