# Recent Changes & Known Edge Cases - PlagExpert (Portal)

This document summarizes recent updates to PlagExpert (Portal), a plagiarism checking and document processing service, along with known edge cases and considerations for ongoing development and operations. It serves as a changelog for significant features and improvements, ensuring stakeholders are aware of the system's evolution.

## Recent Changes

The following updates have been implemented to enhance functionality, improve user experience, and address operational needs. These changes reflect the latest improvements to the codebase as of the documentation date.

### AI Report Skip Feature

- **Description**: Clients or vendors can now opt to skip the AI report generation for an order, providing a reason for skipping.
- **Implementation**: A checkbox and `ai_skip_reason` field have been added to the order report upload form. This allows flexibility in cases where AI analysis is not required or relevant.
- **Database Impact**: The `order_reports.ai_report_path` field is now nullable, accommodating orders without an AI report.
- **User Impact**: Vendors see a simplified workflow when AI reports are not needed, and clients receive faster delivery in such cases.
- **Audit Logging**: Skipping an AI report is logged with `event_type = order.report_ai_skipped` in `audit_logs`, including the provided reason for traceability.
- **Deployment Note**: Ensure existing database migrations reflect the nullable `ai_report_path` column; if not, apply the relevant migration.

### Enhanced Validation in Report Upload Flow

- **Description**: Improved validation and error handling during the vendor report upload process to prevent incomplete or incorrect submissions.
- **Implementation**: The `UploadVendorReportService` now includes stricter checks for file types, sizes, and required fields. Error messages are more descriptive, guiding vendors to correct issues.
- **User Impact**: Vendors receive clearer feedback if uploads fail (e.g., missing plagiarism report when required), reducing support tickets.
- **Logging**: Failed uploads log detailed errors to application logs (not `audit_logs`) under `report.upload_failed` for debugging without exposing sensitive file contents.
- **Testing**: Feature tests have been updated to cover edge cases like invalid file formats or missing required reports.

### Nullable AI Report Path

- **Description**: As part of the AI skip feature, the database schema now explicitly allows `order_reports.ai_report_path` to be null.
- **Implementation**: Migration updated to set `ai_report_path` as nullable, with application logic adjusted to handle cases where no AI report is uploaded.
- **Operational Impact**: Orders marked as `delivered` no longer require an AI report if skipped, preventing false incompleteness flags.
- **Backward Compatibility**: Existing orders with AI reports remain unaffected; new orders can skip without database errors.
- **Maintenance Note**: The `orders:repair-missing-reports` command now respects the skip flag when checking for missing reports.

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

- **AI Report Skip Feature**: Added checkbox and `ai_skip_reason` field to allow skipping AI reports, with `order_reports.ai_report_path` made nullable.
- **Enhanced Report Upload Validation**: Improved error handling in `UploadVendorReportService` for clearer feedback on upload failures.
- **Nullable AI Report Path**: Schema update to support orders without AI reports, ensuring `delivered` status isn't blocked by missing AI files.

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
