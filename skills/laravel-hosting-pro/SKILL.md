---
name: laravel-hosting-pro
description: Expert deployment and hosting management for Laravel applications on Railway and AWS. Use this skill to prepare deployment scripts, configure multi-service cloud architectures (App, Worker, Cron), manage production-ready environments, configure AWS S3 storage, Firebase credentials, and third-party service keys.
---

# Laravel Hosting Pro Skill 🚀

This skill provides a standardized, high-quality workflow for hosting JaraMarket (and other Laravel projects) on modern cloud platforms, specifically targeting **Railway** and **AWS Elastic Beanstalk**.

> **IMPORTANT — JaraMarket uses MySQL exclusively.** Do NOT configure PostgreSQL (`pgsql`) for this project. Always use `DB_CONNECTION=mysql`.

---

## 🚂 Railway Hosting (Majestic Monolith)

Railway is the preferred platform for rapid deployment. We use the "Majestic Monolith" approach, where a single repository powers multiple services.

### 1. Project Preparation
Run the following steps to prepare the repository for Railway:
1.  **Create Scripts Folder:** `mkdir railway`
2.  **Create Init Script (`railway/init-app.sh`):**
    ```bash
    #!/bin/bash
    set -e
    php artisan migrate --force
    php artisan storage:link --force
    php artisan optimize:clear
    php artisan config:cache
    php artisan event:cache
    php artisan route:cache
    php artisan view:cache
    ```
    > ⚠️ `storage:link` MUST be included. Without it, all uploaded images/files will return 404 errors on first deployment.
3.  **Create Worker Script (`railway/run-worker.sh`):**
    ```bash
    #!/bin/bash
    php artisan queue:work --tries=3 --timeout=90
    ```
4.  **Create Cron Script (`railway/run-cron.sh`):**
    ```bash
    #!/bin/bash
    while [ true ]; do
      php artisan schedule:run --no-interaction
      sleep 60
    done
    ```
5.  **Make Scripts Executable:** `chmod +x railway/*.sh`

### 2. Service Configuration
*   **App Service:**
    *   **Build Command:** `npm install && npm run build`
    *   **Pre-deploy Command:** `chmod +x ./railway/init-app.sh && sh ./railway/init-app.sh`
*   **Worker Service:**
    *   **Start Command:** `chmod +x ./railway/run-worker.sh && sh ./railway/run-worker.sh`
*   **Cron Service:**
    *   **Start Command:** `chmod +x ./railway/run-cron.sh && sh ./railway/run-cron.sh`

### 3. Essential Environment Variables
| Variable | Value |
| :--- | :--- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `LOG_CHANNEL` | `stderr` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | *(Railway MySQL host)* |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | `jaramarket` |
| `DB_USERNAME` | *(Railway MySQL username)* |
| `DB_PASSWORD` | *(Railway MySQL password — must be strong)* |
| `SANCTUM_STATEFUL_DOMAINS` | `yourdomain.com` *(update to live domain)* |

---

## ☁️ AWS S3 Storage Configuration

JaraMarket uses AWS S3 for all media/image storage. This MUST be configured before deployment or image uploads will fail silently.

### Required Environment Variables
Set all of these in your hosting platform's environment variable panel — never hardcode them in files.

| Variable | Description |
| :--- | :--- |
| `FILESYSTEM_DISK` | Set to `s3` for production (local for dev) |
| `AWS_ACCESS_KEY_ID` | From AWS IAM → Your user → Security Credentials |
| `AWS_SECRET_ACCESS_KEY` | From AWS IAM (only shown once at creation) |
| `AWS_DEFAULT_REGION` | Must match the region your S3 bucket was created in (e.g. `us-east-1`) |
| `AWS_BUCKET` | The exact name of your S3 bucket |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `false` (unless using MinIO or a local S3-compatible store) |

### S3 Bucket Policy (Minimum Required)
Ensure your S3 bucket allows public read access for image URLs to work:
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::YOUR_BUCKET_NAME/*"
    }
  ]
}
```

### How Dynamic S3 Works in JaraMarket
The `AppServiceProvider::boot()` method calls `SettingsController::reconfigureS3()` at startup. This allows S3 credentials to be updated via the admin panel without redeployment. It is wrapped in a `try-catch` so it will not crash if the `settings` table doesn't exist yet during fresh migrations.

---

## 🔥 Firebase Push Notifications Setup

JaraMarket uses Firebase Cloud Messaging (FCM) for push notifications to the Flutter mobile apps.

### Steps
1.  Go to **Firebase Console** → Your Project → **Project Settings** → **Service Accounts**
2.  Click **"Generate new private key"** → Download the JSON file
3.  **On Railway/AWS:** Upload this JSON file to `storage/app/firebase/service-account.json` on the server. Use the platform's file/volume feature or an environment variable injection method.
4.  Set these environment variables:

| Variable | Value |
| :--- | :--- |
| `FIREBASE_PROJECT_ID` | Your Firebase project ID (found in project settings) |
| `FIREBASE_CREDENTIALS` | `storage/app/firebase/service-account.json` |

> ⚠️ The Firebase JSON file is a secret. Never commit it to Git. Add `storage/app/firebase/` to `.gitignore`.

---

## 💳 Paystack Payment Configuration

JaraMarket uses Paystack as the primary payment gateway.

### Keys to Switch for Production
| Variable | Dev Value | Production Value |
| :--- | :--- | :--- |
| `PAYSTACK_SECRET_KEY` | `sk_test_...` | `sk_live_...` |
| `PAYSTACK_PUBLIC_KEY` | `pk_test_...` | `pk_live_...` |
| `PAYSTACK_WEBHOOK_SECRET` | *(blank)* | Set from Paystack Dashboard → Webhooks |

### Webhook Setup
1.  Go to **Paystack Dashboard** → **Settings** → **API Keys & Webhooks**
2.  Set webhook URL to: `https://yourdomain.com/jaram/webhook/paystack`
3.  Copy the webhook secret and set it as `PAYSTACK_WEBHOOK_SECRET`

---

## 📧 Mail Configuration (Production)

Replace the Mailtrap (dev-only) config with a real mail provider for production.

**Recommended: Mailgun**
```
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.yourdomain.com
MAILGUN_SECRET=your-mailgun-api-key
MAIL_FROM_ADDRESS=no-reply@jaramarket.com
MAIL_FROM_NAME="JaraMarket"
```

**Alternative: AWS SES**
```
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
```

---

## 📱 SMS / OTP (Termii)

JaraMarket uses Termii for OTP delivery during registration and login.

| Variable | Value |
| :--- | :--- |
| `TERMII_API_KEY` | Get from Termii Dashboard |
| `TERMII_SENDER_ID` | `JaraMarket` (pre-approved sender name) |
| `TERMII_BASE_URL` | `https://api.ng.termii.com` |

> Without this, all OTP-based registration and phone verification will fail.

---

## ☁️ AWS Elastic Beanstalk Hosting

AWS is suitable for enterprise-grade deployments requiring more granular control.

### 1. Document Root Configuration
Create `.ebextensions/laravel.config`:
```yaml
option_settings:
  aws:elasticbeanstalk:container:php:phpini:
    document_root: /public
    memory_limit: 512M
  aws:elasticbeanstalk:application:environment:
    APP_ENV: production
    APP_DEBUG: false
    LOG_CHANNEL: errorlog
```

### 2. Deployment Workflow
1.  **Generate Source Bundle:**
    `git archive -o deployment.zip HEAD` (Excludes vendor/ and untracked files).
2.  **Database Connection (RDS):**
    Configure `config/database.php` to use RDS environment variables:
    ```php
    'mysql' => [
        'host' => env('RDS_HOSTNAME', '127.0.0.1'),
        'port' => env('RDS_PORT', '3306'),
        'database' => env('RDS_DB_NAME'),
        'username' => env('RDS_USERNAME'),
        'password' => env('RDS_PASSWORD'),
    ],
    ```

---

## 🔒 Security Best Practices
*   **APP_KEY:** Never hardcode the `APP_KEY`. Generate it once (`php artisan key:generate`) and store it in the platform's secret manager.
*   **SSL:** Always enable HTTPS (Railway handles this automatically; AWS requires an ACM certificate on the ALB).
*   **Secrets:** Request third-party API keys (Paystack, Termii, Firebase, AWS) from the user and enter them directly into the hosting platform's environment variable panel.
*   **`.env` file:** Never commit `.env` to Git. Confirm it is in `.gitignore` before every deploy.
*   **Firebase JSON:** Never commit the Firebase service account JSON to Git.

---
*Updated by Antigravity - 2026-05-01*
