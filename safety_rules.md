# JaraMarket Safety Rules for Agents

This document outlines the mandatory safety protocols that any AI Agent must follow when working within the JaraMarket workspace. These rules are designed to protect the integrity of the MySQL database, the security of environment secrets, and the stability of the production deployment on Railway.

## 1. MySQL Database Safety
*   **No Destructive Commands:** Commands that delete tables or truncate data (e.g., `migrate:fresh`, `db:wipe`) are strictly prohibited in the production environment.
*   **Mandatory Backups:** Before performing any schema changes (`php artisan migrate`), the Agent must verify that a recent database snapshot exists on Railway.
*   **Dry-Run Principle:** Always use `php artisan migrate --pretend` to preview SQL changes before execution.
*   **Data Masking:** When displaying database records in the chat, the Agent must mask Personally Identifiable Information (PII) including:
    *   Full email addresses (e.g., `u***@example.com`)
    *   Phone numbers
    *   Hashed passwords or tokens
*   **Query Limits:** Avoid running `SELECT *` on large tables. Always use `LIMIT` and specify columns to conserve resources and prevent accidental data exposure.

## 2. Environment & Secret Management
*   **.env Protection:** The `.env` file must never be committed to Git. The Agent must verify `.gitignore` contains `.env` before every push.
*   **Secret Injection:** Secrets should only be managed via the Railway Dashboard or the local `.env` file. Never hardcode API keys, passwords, or S3 credentials into the codebase.
*   **Permission Required:** The Agent must request explicit permission before reading or modifying the `.env` file.

## 3. Git & Railway Deployment Workflow
*   **Sync Before Push:** Always run `git pull origin master` (or the active branch) before pushing to ensure the local state is synchronized and to avoid merge conflicts.
*   **Build Verification:** After pushing to Railway, the Agent must monitor the build logs to ensure the deployment was successful.
*   **Health Check:** Immediately after a successful build, the Agent must check the application logs for `500` errors or database connection failures.
*   **Atomic Commits:** Each push should be small, focused, and accompanied by a clear, descriptive commit message.

## 4. Application Stability
*   **Maintenance Mode:** For high-risk updates, the Agent should suggest using `php artisan down` to prevent user interaction during the transition.
*   **Error Monitoring:** Regularly check `storage/logs/laravel.log` and Railway runtime logs for recurring exceptions.
*   **Optimization:** When adding new features, prioritize Eloquent performance (avoiding N+1 queries) to maintain database health.

---
*Failure to follow these rules may result in data loss or service interruption. Safety is the priority.*
