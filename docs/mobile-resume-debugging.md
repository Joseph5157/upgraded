# Mobile Resume Debugging

This guide documents how to reproduce and inspect failures that happen after a phone is locked, the browser is backgrounded, or the app is resumed after idle time.

## How To Reproduce

1. Open a client-facing page such as the dashboard, public upload page, or tracking page.
2. Perform an action once so the session and CSRF state are active.
3. Background the browser or lock the device for 2 to 10 minutes.
4. Resume the page and retry the action immediately.
5. Record the exact time, route, user role, and whether the page fully reloaded before the failure.

## What To Capture

- `X-Request-Id` response header from the failed request if present
- Request method and path
- Route name if known
- HTTP status code
- Whether the page resumed from background or was fully reloaded
- User role and account status
- Browser and device details

## What Logs To Inspect

- Request start and finish log lines for the matching request ID
- Session timeout or account-status middleware behavior
- Authentication redirects around the same timestamp
- Any exception log lines that share the same request ID

## How To Interpret Common Statuses

### 403

- Usually indicates authorization denial or invalid user/session state
- Check route, role, account status, and policy outcome
- Correlate the denied action with the matching request ID

### 419

- Usually indicates expired CSRF token or expired session
- Check whether the page was left idle long enough for session expiry
- Confirm whether a token refresh or full reload happened before retry

### 302 / 303

- Usually indicates redirect to login, verification, or another guarded route
- Check final destination and whether the redirect is role-correct
- Confirm whether the original request came from a stale client page

### 500

- Indicates an unhandled server-side error
- Capture request ID, timestamp, route, and user role first
- Inspect exception logs and any related database or storage failures

## Scope Limits For Diagnostics

- Do not capture raw file contents
- Do not capture passwords, tokens, or full session payloads
- Do not log full request bodies for uploads
