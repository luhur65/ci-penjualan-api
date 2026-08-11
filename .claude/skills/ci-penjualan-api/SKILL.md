```markdown
# ci-penjualan-api Development Patterns

> Auto-generated skill from repository analysis

## Overview

This skill provides guidance for contributing to the `ci-penjualan-api` TypeScript codebase. It covers coding conventions, commit standards, and key workflows such as adding background jobs, implementing notifications, and refactoring to use services. The repository is structured for maintainability and follows clear, conventional commit and code organization patterns.

## Coding Conventions

- **Language:** TypeScript
- **Framework:** None detected (custom structure)
- **File Naming:** PascalCase (e.g., `ExportJob.ts`, `NotificationService.ts`)
- **Import Style:** Relative imports  
  _Example:_
  ```typescript
  import { ExportJob } from '../Jobs/ExportJob';
  ```
- **Export Style:** Named exports  
  _Example:_
  ```typescript
  export class ExportJob { ... }
  ```
- **Commit Messages:** Conventional Commits  
  - Prefixes: `feat`, `refactor`
  - Example:  
    ```
    feat: add export job for background processing
    refactor: move notification logic to service class
    ```
- **Folder Structure:**  
  - `app/Jobs/` — Background jobs
  - `app/Services/` — Service classes
  - `app/Controllers/Api/` — API controllers
  - `app/Models/` — Data models
  - `app/Config/` — Configuration files
  - `app/Database/Migrations/` — Database migrations

## Workflows

### Add Background Job with Queue
**Trigger:** When a time-consuming process (like export or email) should run asynchronously in the background.  
**Command:** `/new-background-job`

1. **Create a Job class** in `app/Jobs/` (e.g., `ExportJob.ts`):
    ```typescript
    export class ExportJob {
      async handle(data: ExportData) {
        // export logic here
      }
    }
    ```
2. **Register the job** in `app/Config/Queue.ts`:
    ```typescript
    import { ExportJob } from '../Jobs/ExportJob';
    export const queueJobs = [ExportJob];
    ```
3. **Update the relevant Controller** to dispatch the job instead of running logic synchronously:
    ```typescript
    import { ExportJob } from '../../Jobs/ExportJob';
    // ...
    queue.dispatch(new ExportJob(request.body));
    ```
4. **Update or create related Service or Library classes** if needed.
5. **Update dependencies** if new packages are required.

### Add Notification Feature
**Trigger:** When users need to be notified about an event (e.g., export completion, password reset) via in-app notification, email, or real-time WebSocket.  
**Command:** `/new-notification`

1. **Create or update Notification model, service, and controller:**
    - `app/Models/Notification.ts`
    - `app/Services/NotificationService.ts`
    - `app/Controllers/Api/NotificationController.ts`
    ```typescript
    export class NotificationService {
      send(userId: string, message: string) {
        // notification logic
      }
    }
    ```
2. **Add or update database migration** for notifications in `app/Database/Migrations/*CreateNotificationsTable.ts`.
3. **Update routes** in `app/Config/Routes.ts` to expose notification endpoints.
4. **Update job or service** to emit notification (e.g., in `ExportJob.ts`):
    ```typescript
    notificationService.send(user.id, 'Export completed!');
    ```
5. **Implement secure file serving and ownership checks** if needed.

### Refactor Feature to Use Service
**Trigger:** When logic is duplicated across jobs/controllers and should be centralized for reuse and maintainability.  
**Command:** `/refactor-to-service`

1. **Move logic** from job/controller to a new or existing service in `app/Services/`:
    ```typescript
    // app/Services/EmailService.ts
    export class EmailService {
      sendEmail(to: string, subject: string, body: string) { ... }
    }
    ```
2. **Update job/controller** to use the service method:
    ```typescript
    import { EmailService } from '../Services/EmailService';
    emailService.sendEmail(user.email, 'Subject', 'Body');
    ```
3. **Test** to ensure behavior remains unchanged.

## Testing Patterns

- **Test File Pattern:** `*.test.*` (e.g., `ExportJob.test.ts`)
- **Testing Framework:** Unknown (no framework detected)
- **Typical Structure:**  
  - Place test files alongside or near the code under test.
  - Use descriptive test names and group related tests.

  _Example:_
  ```typescript
  // ExportJob.test.ts
  import { ExportJob } from './ExportJob';

  describe('ExportJob', () => {
    it('should process export data', async () => {
      // test logic
    });
  });
  ```

## Commands

| Command               | Purpose                                                        |
|-----------------------|----------------------------------------------------------------|
| /new-background-job   | Scaffold and register a new background job with the queue      |
| /new-notification     | Add or enhance notification delivery for user-triggered events |
| /refactor-to-service  | Refactor logic into a reusable service class                  |
```
