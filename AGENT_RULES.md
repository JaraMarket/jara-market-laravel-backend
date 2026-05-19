# 📜 JaraMarket: Agent Rules & Persistence Guidelines
**IMPORTANT:** All agents working in this workspace must follow these rules to prevent data loss and ensure system stability.

---

## 🔑 1. Production API Key
The following key has been designated for **Live/Production** use. Do not generate new keys for the frontend developer unless explicitly asked by the user.

*   **Variable:** `PROD_FRONTEND_API_KEY` (Stored securely in `.env`)
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

## 🚀 5. Deployment & Monitoring
*   **MANDATORY:**Agents must seek permission before atempting to make a push to git, Whenever an agent makes a push to Git, they **MUST** immediately commence monitoring of Railway logs using the Railway CLI (`railway logs`).
*   **CONFIRMATION:** Monitoring must continue until the build and deployment are confirmed successful.
*   **RECTIFICATION:** If any issues are identified in the logs, the agent must immediately attempt to rectify them.


---

## 🔐 6. Secret Key Handling Protocol (MANDATORY)
*   **Strict `.gitignore` Validation**: Before any Git operation (add, commit, push), agents must verify that `.env`, `.pem`, and other sensitive files are explicitly listed in the `.gitignore`.
*   **No Hardcoding**: Agents are strictly forbidden from hardcoding secrets directly into source code. All secrets must be accessed via environment variables.
*   **Leak Detection & Immediate Halt**: If an agent detects a potential secret key hardcoded in any file tracked by Git, it must immediately halt all operations and notify the user to rotate the key.
*   **Explicit Consent for `.env` Edits**: Agents must provide a line-by-line explanation of any proposed change to the `.env` file before requesting permission to execute.
Agents must carry a thorough check through all files,codes e.t.c ensuring there is no risk of exposing secrets before pushing to github.

## 📐 7. SOLID Design Principles (MANDATORY)
*   **SOLID Compliance:** Whenever you write code, it must follow software engineering SOLID design principles.
*   **No Violations:** Never write code that violates these principles. If you do, you will be asked to refactor it immediately.

---
*Updated by Antigravity - 2026-05-19*

