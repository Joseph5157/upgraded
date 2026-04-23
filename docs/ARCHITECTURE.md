# Architecture Overview - PlagExpert (Portal)

This document provides a detailed overview of the technical architecture and structure of PlagExpert (Portal), a plagiarism checking and document processing service built on Laravel 12.

## Tech Stack

- **Backend Framework**: Laravel 12
- **Programming Language**: PHP 8.2
- **Database**: MySQL with database-driven sessions
- **Storage**: Cloudflare R2 (default filesystem for file uploads and reports)
- **Frontend**: Laravel Blade templating, Tailwind CSS for styling, Alpine.js for interactivity, bundled with Vite
- **Key Dependencies**:
  - `laravel/framework`: ^12.0
  - `league/flysystem-aws-s3-v3`: For Cloudflare R2 integration
  - Frontend tools: `@tailwindcss/forms`, `axios`, `alpinejs`

## System Architecture

PlagExpert follows a typical Laravel MVC architecture enhanced with a service layer for business logic, comprehensive policies for authorization, and enums for state management. The system is designed for scalability and security, leveraging Cloudflare services and Telegram integrations.

### High-Level Flow

1. **Client Interaction**: Clients upload PDF/DOC files via a dashboard or public links, consuming slot-based credits.
2. **Vendor Processing**: Vendors claim orders, process documents, and upload AI and plagiarism reports.
3. **Admin Oversight**: Admins manage users, pricing, billing, and system health through a dedicated dashboard.
4. **External Integrations**:
   - **Cloudflare R2**: Stores uploaded files and reports.
   - **Cloudflare Turnstile**: Provides bot protection for public endpoints.
   - **Telegram**: Facilitates OTP login and notifications.

### Key Components

- **Controllers**: Handle HTTP requests and user input (e.g., `DashboardController`, `ClientDashboardController`, `AdminDashboardController`).
- **Models**: Represent data entities with Eloquent ORM (e.g., `Order`, `Client`, `User`, `OrderReport`).
- **Services**: Encapsulate business logic (e.g., `CreateClientOrderService`, `OrderWorkflowService`, `UploadVendorReportService`).
- **Policies**: Define authorization rules (e.g., `OrderPolicy`, `UserPolicy`).
- **Enums**: Manage predefined states (e.g., `OrderStatus` with states like `pending`, `claimed`, `processing`, `delivered`).
- **Artisan Commands**: Handle maintenance and operational tasks (e.g., `HealthCheckCommand`, `AutoReleaseOrdersCommand`).
- **Middleware**: Enforce security and session management (e.g., custom session timeout, account status checks).

## Folder Structure

The project adheres to Laravel's conventional directory structure with additional organization for clarity and maintainability:

- **`app/Console/Commands/`**: Custom Artisan commands for system maintenance.
  - Examples: `HealthCheckCommand`, `AutoReleaseOrdersCommand`, `CloseDayCommand`
- **`app/Enums/`**: Enumerations for state management.
  - Key file: `OrderStatus.php` (defines order lifecycle states)
- **`app/Http/Controllers/`**: Request handling logic segmented by user role.
  - Examples: `DashboardController` (Vendor), `ClientDashboardController`, `AdminDashboardController`
- **`app/Models/`**: Eloquent models representing database entities.
  - Core models: `Order`, `Client`, `User`, `OrderReport`, `AuditLog`
- **`app/Policies/`**: Authorization logic for various entities.
  - Examples: `OrderPolicy`, `UserPolicy`, `ClientPolicy`
- **`app/Services/`**: Business logic abstracted from controllers.
  - Key services: `CreateClientOrderService`, `OrderWorkflowService`, `UploadVendorReportService`, `AuditLogger`
- **`config/`**: Configuration files for Laravel components and integrations.
- **`database/`**: Migrations, factories, and seeders for database setup.
- **`public/`**: Web server document root, entry point for assets.
- **`resources/`**: Blade views and frontend assets (CSS, JS).
- **`routes/`**: Route definitions for web and API endpoints.
  - Key files: `web.php` (main routes), `auth.php` (authentication routes)
- **`tests/`**: Unit and feature tests for quality assurance.

## Key Models & Relationships

Below are the primary models and their relationships, crucial for understanding the data model:

- **`User`**:
  - Represents all system users (admins, vendors, clients).
  - Relationships:
    - `client()`: Belongs to a `Client` (if role is client).
    - `orders()`: Has many `Order` (as vendor or creator).
  - Key methods: `isFrozen()`, `isActive()`, `isSuperAdmin()`, role-based permission checks.
- **`Client`**:
  - Represents client accounts with slot-based credits.
  - Relationships:
    - `user()`: Belongs to a `User`.
    - `orders()`: Has many `Order`.
    - `links()`: Has many `ClientLink`.
    - `topupRequests()`: Has many `TopupRequest`.
  - Key attribute: `total_slots` (computed).
- **`Order`**:
  - Core entity representing a document processing request.
  - Relationships:
    - `client()`: Belongs to a `Client`.
    - `vendor()`: Belongs to a `User` (as claimed_by).
    - `files()`: Has many `OrderFile`.
    - `report()`: Has one `OrderReport`.
    - `link()`: Belongs to a `ClientLink` (for public uploads).
  - Key attribute: `computed_status` (derived from state).
- **`OrderFile`**:
  - Represents individual uploaded files within an order.
  - Relationship: `order()`: Belongs to an `Order`.
- **`OrderReport`**:
  - Stores AI and plagiarism reports for an order.
  - Relationship: `order()`: Belongs to an `Order`.
  - Key method: `isComplete()` (checks if reports are uploaded).
- **`AuditLog`**:
  - Records system events for traceability.
  - Relationship: `user()`: Belongs to a `User`.
- **`ClientLink`**:
  - Represents public upload links for clients.
  - Relationships:
    - `client()`: Belongs to a `Client`.
    - `orders()`: Has many `Order`.
- **`TopupRequest`** & **`RefundRequest`**:
  - Manage client credit adjustments.
  - Relationships: Belong to `Client` and/or `Order`.

## Important Services & Commands

### Services

Services encapsulate complex business logic, ensuring controllers remain thin and focused on HTTP handling:

- **`AuditLogger`**: Logs system events with request correlation for traceability.
- **`CreateClientOrderService`**: Handles order creation with file uploads and credit deduction.
- **`DeleteClientOrderService`**: Manages order deletion with credit restoration.
- **`OrderWorkflowService`**: Manages order state transitions (claim, unclaim, process, deliver).
- **`UploadVendorReportService`**: Handles report uploads with validation and storage.
- **`NotificationService`**: Sends notifications for order status changes.
- **`PortalTelegramAlertService`**: Sends Telegram alerts for key events.
- **`TurnstileService`**: Validates Cloudflare Turnstile tokens for bot protection.

### Artisan Commands

Custom commands for maintenance and operational tasks:

- **`HealthCheckCommand`**: Verifies system health (database, storage).
- **`AutoReleaseOrdersCommand`**: Releases stalled orders back to pending status.
- **`CloseDayCommand`**: Closes daily ledgers for billing.
- **`CleanupLinkOrdersCommand`**: Cleans up orders from public links.
- **`DeleteOrdersCommand`**: Deletes orders with proper credit handling.
- **`PurgeOrderFilesCommand`**: Removes old order files from storage.
- **`RepairMissingReportsCommand`**: Fixes orders with missing report files.
- **`TestR2Connection`**: Tests Cloudflare R2 storage connectivity.
- **`PromoteSuperAdmin`**: Elevates a user to super admin status.

## Best Practices Implemented

- **Service Layer**: Business logic is abstracted into services, promoting reusability and testability.
- **Comprehensive Policies**: Fine-grained access control ensures users can only perform authorized actions.
- **Audit Logging**: Critical actions are logged with request IDs for end-to-end traceability.
- **Enum Usage**: Order states are managed via enums, preventing invalid states.
- **Database Sessions**: Ensures session persistence and scalability.
- **Security Middleware**: Custom middleware for session timeouts and account status checks enhances security.
