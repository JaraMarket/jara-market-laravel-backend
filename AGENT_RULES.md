# 📜 JaraMarket: Agent Rules & Persistence Guidelines
**IMPORTANT:** All agents working in this workspace must follow these rules to prevent data loss and ensure system stability.

---

## 🔑 1. Production API Key
The following key has been designated for **Live/Production** use. Do not generate new keys for the frontend developer unless explicitly asked by the user.

*   **Key:** `2|qZrjSJFP0GKNXHjL0nfuxG03NZfzCRYPuYry3nTo098cb68e` (Verified: 2026-05-02)
*   **User:** `admin@jaramarket.com`
*   **Purpose:** Primary connection for Customer and Vendor apps.

---

## 🚫 2. Data Integrity (STRICT RULE)
The user is populating the backend with over **2,000 automated entries** via MCP.

*   **NEVER** run `php artisan migrate:fresh` on a database containing populated data.
*   **NEVER** run `db:seed` if it contains logic that truncates (clears) existing tables.
*   **MANDATORY:** Before running any structural migrations, an agent **MUST** perform a backup using `mysqldump` to preserve the 2,000 entries.

---

## 📦 3. Data Persistence Strategy
To ensure the 2,000 entries survive hosting and migration:

1.  **Backup first:** Use `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe` to create a `.sql` snapshot.
2.  **Seeder Creation:** Once population is complete, agents should convert the database state into a `ProductionDataSeeder.php` to make the data portable.
3.  **Deployment:** When moving to hosting, use **Import** instead of **Rebuild** to maintain data integrity.

---

## 🛠️ 4. Local Environment
*   **Laragon Path:** `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin` is on the System PATH.
*   **Backend URL:** `http://127.0.0.1:8000`
*   **Frontend URL:** `http://localhost:5173`

---
*Created by Antigravity - 2026-04-30*
