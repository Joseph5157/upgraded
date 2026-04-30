# Recent Changes & Known Edge Cases - PlagExpert (Portal)

This document summarizes recent updates to PlagExpert (Portal), a plagiarism checking and document processing service, along with known edge cases and considerations for ongoing development and operations. It serves as a changelog for significant features and improvements, ensuring stakeholders are aware of the system's evolution.

## Today's Executive Summary

- Added queued delivery for vendor order-completed Telegram notifications so uploads return faster.
- Added live polling for vendor, logged-in client, and guest-link dashboards so workspace and status sections refresh without manual reload.
- Added token-scoped guest-link polling for upload and track pages so public link clients only see their own order/report updates.
- Kept guest-link access fail-closed for revoked and expired tokens, and rebound guest upload controls after fragment replacement.

## Recent Changes

The following updates reflect the current project state, newest first.

### Guest-Link Live Updates

- Guest-link upload and track pages now poll token-scoped pulse endpoints instead of relying on manual refresh.
- The upload page swaps a server-rendered fragment in place and rebinds upload/drop listeners after replacement.
- The track page also swaps a live fragment in place, so report availability and download state update without a full reload.
- Revoked or expired guest links fail closed and do not reveal another link's state.

### Dashboard Polling

- Vendor and logged-in client dashboards now use signature-based polling and server-rendered fragments for live updates.
- Available orders, workspace sections, and client order status panels update across sessions without a full page refresh.
- Fragment replacement is followed by listener rebinds so existing AJAX actions keep working.

### Queue and Notifications

- Vendor order-completed Telegram notifications now run through the queue instead of blocking the upload request.
- The request path keeps file storage, transaction, and report persistence synchronous, but notification delivery is asynchronous.
- Queue dispatch is covered by upload tests so the behavior stays correct when Telegram fails later.

## Older Changes

These are still valid historical notes from earlier work, but they are no longer the most recent project changes.

### AI Report Skip Feature

- **Description**: Clients or vendors can opt to skip AI report generation for an order by providing a reason.
- **Implementation**: The report upload flow supports `ai_skip_reason`, and AI report storage can be omitted when skipped.
- **Operational Impact**: Orders marked as `delivered` do not require an AI report if the skip path is used.

### Enhanced Validation in Report Upload Flow

- **Description**: Report upload validation was tightened to prevent incomplete or incorrect submissions.
- **Implementation**: `UploadVendorReportService` checks file types, sizes, and required fields more strictly.
- **User Impact**: Vendors get clearer feedback when uploads fail.

### Nullable AI Report Path

- **Description**: The report schema supports missing AI output when the skip path is used.
- **Operational Impact**: Existing AI-backed orders remain unaffected, and skipped orders do not fail validation.

## Known Edge Cases

Below are identified edge cases and areas of potential concern that may require attention during development, testing, or operations. These are not necessarily bugs but scenarios where the system behavior might be unexpected or require special handling.

### Order Deletion with Credit Restoration

- **Scenario**: When a client deletes an unclaimed `pending` order, credits are restored based on `files_count`. If an admin deletes a client account with active orders, credits are restored for all cancelled orders.
- **Edge Case**: If database transactions fail midway (e.g., due to a deadlock), there's a risk of credits being restored without order cancellation or vice versa.
- **Mitigation**: The `DeleteClientOrderService` uses database transactions to ensure atomicity. However, monitor `audit_logs` for `credits.restored` events without corresponding `order.cancelled` or `order.deleted` events.
- **Testing**: Stress-test deletion under high load to confirm transaction reliability.
- **Operational Action**: If inconsistency is detected, manually adjust credits via admin dashboard and log the correction.

### Public Upload Link Throttling

- **Scenario**: Public upload links (`/u/{token}`) are rate-limited to 30 requests per minute to prevent abuse.
- **Edge Case**: Legitimate high-volume clients using a single link may hit the throttle limit during peak usage, resulting in 429 (Too Many Requests) errors.
- **Mitigation**: Clients are advised to use dashboard uploads for bulk operations, or admins can issue multiple links to distribute load.
- **User Impact**: Affected clients see a temporary block with a retry-after header; no data loss occurs.
- **Future Improvement**: Consider configurable per-link throttle limits or CAPTCHA challenges for high-frequency users.

### Session Expiry on Mobile Resume

- **Scenario**: Mobile users resuming the app after backgrounding for extended periods (beyond `SESSION_TIMEOUT_MINUTES`) may encounter session expiry.
- **Edge Case**: If the browser or app doesn't handle the 302 redirect to login gracefully, users might see a CSRF error loop or a blank page instead of a clean login prompt.
- **Mitigation**: The system ensures a clean redirect to `/login` with a flash message. Mobile UI tests (see `docs/smoke-test-checklist.md`, Section D) verify this behavior.
- **User Impact**: Minor inconvenience; users must re-authenticate but no data is lost.
- **Monitoring**: Check logs for repeated 419 (CSRF token mismatch) errors with the same `X-Request-Id` to identify problematic clients.

### Audit Log Growth

- **Scenario**: The `audit_logs` table grows rapidly in high-traffic environments due to logging of all significant actions (order claims, deletions, account changes).
- **Edge Case**: Without archiving, database performance may degrade over time for queries on `audit_logs` or backups may become slower.
- **Mitigation**: No immediate action needed for small-to-medium deployments. For large-scale systems, plan to archive old logs (e.g., older than 6 months) to a separate table or external storage.
- **Operational Action**: Monitor table size (`SELECT COUNT(*) FROM audit_logs;`) and query performance monthly. Do not truncate without archiving.
- **Future Improvement**: Implement automated log archiving via a new Artisan command.

### AI Report Skip Reason Logging

- **Scenario**: When vendors skip AI reports, the `ai_skip_reason` is logged in `audit_logs.meta`.
- **Edge Case**: If vendors input sensitive or inappropriate content in the reason field, it could be visible in logs, posing a privacy or compliance risk.
- **Mitigation**: Current logs are sanitized to prevent file content leakage, but `ai_skip_reason` is stored as-is. Admin review of logs should be restricted to trusted personnel.
- **User Impact**: No direct impact unless logs are exposed.
- **Future Improvement**: Add input validation or sanitization for `ai_skip_reason` to prevent misuse, or mask sensitive parts in logs.

## Changelog Summary

For a quick reference, below is a summarized timeline of notable changes:

- **Guest-Link Live Updates**: Added token-scoped polling for guest upload and track pages with fragment replacement and listener rebinds.
- **Dashboard Polling**: Added live polling for vendor and logged-in client dashboards with server-rendered fragments.
- **Queue and Notifications**: Moved vendor completion Telegram notifications off the request path into a queue job.
- **AI Report Skip Feature**: Historical behavior retained for skipped AI uploads and nullable report paths.

## Best Practices for Handling Edge Cases

- **Proactive Monitoring**: Use `audit_logs` and application logs to detect anomalies early (e.g., credit inconsistencies, throttle hits).
- **User Communication**: Inform clients about throttle limits on public links and provide alternatives (dashboard uploads, multiple links).
- **Testing Coverage**: Expand test suites to cover edge cases like transaction failures during deletions or session expiry on mobile devices.
- **Documentation**: Keep this document updated with new edge cases or changes as they are discovered or implemented.
- **Feedback Loop**: Encourage users to report unexpected behavior with `X-Request-Id` headers for precise log correlation.

## Support for Recent Changes

If issues arise from recent updates (e.g., AI report skipping not reflecting correctly in order status), contact the development team with:
- Specific order IDs or user IDs affected.
- Relevant `X-Request-Id` headers from error responses.
- Logs from `storage/logs/` or database entries from `audit_logs` if accessible.

This document ensures transparency on the system's current state, helping developers, operators, and stakeholders understand recent enhancements and areas requiring attention.
