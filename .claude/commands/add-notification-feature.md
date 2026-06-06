---
name: add-notification-feature
description: Workflow command scaffold for add-notification-feature in ci-penjualan-api.
allowed_tools: ["Bash", "Read", "Write", "Grep", "Glob"]
---

# /add-notification-feature

Use this workflow when working on **add-notification-feature** in `ci-penjualan-api`.

## Goal

Adds or enhances notification delivery (in-app, email, or WebSocket) for user-triggered events.

## Common Files

- `app/Models/Notification.php`
- `app/Services/NotificationService.php`
- `app/Controllers/Api/NotificationController.php`
- `app/Database/Migrations/*CreateNotificationsTable.php`
- `app/Config/Routes.php`
- `app/Jobs/*.php`

## Suggested Sequence

1. Understand the current state and failure mode before editing.
2. Make the smallest coherent change that satisfies the workflow goal.
3. Run the most relevant verification for touched files.
4. Summarize what changed and what still needs review.

## Typical Commit Signals

- Create or update Notification model, service, and controller (app/Models/Notification.php, app/Services/NotificationService.php, app/Controllers/Api/NotificationController.php)
- Add or update database migration for notifications (app/Database/Migrations/*CreateNotificationsTable.php)
- Update routes in app/Config/Routes.php
- Update job or service to emit notification (e.g., ExportJob.php, NotificationService.php)
- Implement secure file serving and ownership checks if needed

## Notes

- Treat this as a scaffold, not a hard-coded script.
- Update the command if the workflow evolves materially.