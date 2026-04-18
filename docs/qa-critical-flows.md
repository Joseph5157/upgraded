# QA Critical Flows

Use this checklist for manual stabilization QA. Capture the request ID from the response header or logs whenever a case fails.

## Client Login

1. Log in as a client with a valid active account.
2. Confirm redirect lands on `client.dashboard`.
3. Confirm no verification prompt is shown during normal client use.
4. Confirm logout returns to the login page.

## Vendor/Admin Verification

1. Log in as a verified vendor and confirm access to `dashboard`.
2. Log in as a verified admin and confirm access to `admin.dashboard`.
3. Log in as an unverified vendor or admin and confirm verified-only routes are blocked.
4. Confirm any auth failure can be tied to a request ID.

## Client Upload

1. Upload one file from the client dashboard.
2. Upload multiple files from the client dashboard.
3. Upload multiple files from the public client link.
4. Confirm limits, failures, and success responses are traceable with a request ID.

## Claim / Unclaim / Start Processing

1. Claim a pending order as a vendor.
2. Confirm the order becomes reserved/claimed, not processing.
3. Start processing from the claimed state.
4. Unclaim only from the claimed state.
5. Confirm invalid transitions are blocked.

## Client Delete Restrictions

1. Delete an unclaimed pending order as the owning client.
2. Attempt to delete a claimed order and confirm it is denied.
3. Attempt to delete a processing order and confirm it is denied.
4. Confirm denied actions are reproducible and correlate to a request ID.

## Credit Accounting

1. Create a multi-file order and note the consumed slots.
2. Delete an eligible pending order and confirm consumed slots are restored by file count.
3. Delete a client account with unfinished multi-file orders and confirm slot restoration matches `SUM(files_count)`.
4. Confirm subscription screens reflect `slots_consumed`, not order count.

## Admin Freeze / Delete

1. Freeze a vendor account and confirm session invalidation behavior is safe.
2. Freeze a client account and confirm client status reflects suspension.
3. Delete a client account with unfinished orders and verify credit restoration.
4. Delete a vendor account with claimed or processing orders and verify order release behavior.

## Mobile Standby / Resume

1. Open a client-facing page on mobile or a mobile emulator.
2. Background the browser or lock the device for several minutes.
3. Resume and repeat the last action.
4. If the result is 403, 419, 302/303 loop, or 500, capture the request ID, route, timestamp, and user role.
