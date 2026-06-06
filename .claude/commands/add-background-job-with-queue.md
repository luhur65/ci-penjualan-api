---
name: add-background-job-with-queue
description: Workflow command scaffold for add-background-job-with-queue in ci-penjualan-api.
allowed_tools: ["Bash", "Read", "Write", "Grep", "Glob"]
---

# /add-background-job-with-queue

Use this workflow when working on **add-background-job-with-queue** in `ci-penjualan-api`.

## Goal

Implements a new background job using the queue system to handle asynchronous tasks such as exports or email sending.

## Common Files

- `app/Jobs/*.php`
- `app/Config/Queue.php`
- `app/Controllers/Api/*.php`
- `app/Services/*.php`
- `composer.json`
- `composer.lock`

## Suggested Sequence

1. Understand the current state and failure mode before editing.
2. Make the smallest coherent change that satisfies the workflow goal.
3. Run the most relevant verification for touched files.
4. Summarize what changed and what still needs review.

## Typical Commit Signals

- Create a new Job class in app/Jobs (e.g., ExportJob.php, SendEmailJob.php)
- Register the job in app/Config/Queue.php
- Update relevant Controller to dispatch the job instead of running logic synchronously
- Update or create related Service or Library classes if needed
- Update composer.json/lock if new packages are required

## Notes

- Treat this as a scaffold, not a hard-coded script.
- Update the command if the workflow evolves materially.