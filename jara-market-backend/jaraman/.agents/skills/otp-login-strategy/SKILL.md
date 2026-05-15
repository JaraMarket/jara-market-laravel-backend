---
name: otp-login-strategy
description: Use this skill to implement secure and reliable email OTP login flows for users and vendors. It covers OTP generation, storage, notification dispatching, and verification logic using Laravel 11 standards and Resend.
---

# OTP Login Strategy Skill

This skill provides the architectural pattern for implementing One-Time Password (OTP) authentication in the JaraMarket ecosystem.

## 🏗️ Architecture

### 1. Database Schema

Add the following columns to the target model (User or Vendor):
- `otp_code`: 6-digit numeric string.
- `otp_expires_at`: Datetime.
- `otp_verified_at`: Datetime (optional, for tracking).

### 2. OTP Generation

Use a secure random number generator:
```php
$otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
```

### 3. Notification (Via Resend)

Create a queued notification:
```php
php artisan make:notification SendOTPNotification --queued
```
In the `toMail` method, use the `Resend` mailer and a clean markdown template.

### 4. Verification Logic

- Check if the code matches.
- Check if `now() < otp_expires_at`.
- Use a single-use policy: nullify the `otp_code` immediately after successful verification to prevent reuse.

## 🚀 Best Practices

- **Rate Limiting:** Always throttle OTP request endpoints to prevent brute-force attacks and email spam.
- **Queueing:** Always queue the email dispatch using `ShouldQueue` to keep the API response snappy.
- **Expiration:** Set a short expiration window (e.g., 5-10 minutes).
- **Sanctum Integration:** Return a Sanctum token immediately upon successful OTP verification to log the user in.

---
*Created for Inimfon - 2026-05-15*