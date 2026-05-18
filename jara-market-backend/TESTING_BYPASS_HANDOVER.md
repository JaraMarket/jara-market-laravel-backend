# 🛠️ Test-User Auto-Recycle Bypass Handover & Rollback Guide

This document contains full details of the automated test-user recycling system implemented for OTP testing, and step-by-step instructions to **undo/rollback** the changes when testing is complete.

---

## 📋 Context & Rationale

During production OTP testing, registering multiple times with the same email address throws a strict MySQL `1062 Duplicate entry` exception on the `users_email_unique` index. Because the `User` model uses Laravel's `SoftDeletes`, soft-deleted test users remain in the physical table database and cause unique validation errors, preventing clean, repetitive testing.

To enable **infinite, conflict-free sign-up and OTP tests**, we implemented an automatic database recycling hook for specific test emails.

---

## 🔧 What Was Implemented

We added a `prepareForValidation()` hook inside the Laravel registration request validator:
* **File Path**: [RegisterRequest.php](file:///c:/Users/user/.gemini/jara-market/jara-market-backend/jaraman/app/Http/Requests/RegisterRequest.php)
* **Target Emails**: `iudofa0@gmail.com` and `stenographersservices0@gmail.com`

### **Behavior**
Whenever a sign-up request is initiated with either of the target emails, the hook intercepts the request **before validation runs**, queries the database (including soft-deleted records via `withTrashed()`), detaches any associated permissions/pivot tables, deletes their active wallets and OTP records, and permanently `forceDelete()`s the old user record. 

This ensures that each new sign-up behaves exactly like a brand-new Customer registration (generating a fresh wallet, fresh OTP database entry, and triggering a new Gmail OTP email).

---

## 💻 The Code Added

The following block was inserted at the very top of the `RegisterRequest` class in `app/Http/Requests/RegisterRequest.php` (directly below the class definition line):

```php
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $email = $this->input('email');
        $testEmails = ['iudofa0@gmail.com', 'stenographersservices0@gmail.com'];
        
        if (in_array($email, $testEmails)) {
            $user = \App\Models\User::withTrashed()->where('email', $email)->first();
            if ($user) {
                if ($user->wallet) {
                    $user->wallet->delete();
                }
                $user->userPermissions()->detach();
                \App\Models\User_otp::where('email', $email)->delete();
                $user->forceDelete();
            }
        }
    }
```

---

## 🔄 How to Undo / Rollback These Changes

To restore standard production behavior where no user accounts are ever auto-deleted, follow these simple steps:

### **Step 1: Delete the Hook Method**
Open [RegisterRequest.php](file:///c:/Users/user/.gemini/jara-market/jara-market-backend/jaraman/app/Http/Requests/RegisterRequest.php) and **delete** the entire `prepareForValidation()` method shown above.

### **Step 2: Commit and Push**
Run the following git commands in the terminal to deploy the rollback to production:

```bash
# Navigate to backend git root
cd c:\Users\user\.gemini\jara-market\jara-market-backend

# Stage the file
git add jaraman/app/Http/Requests/RegisterRequest.php

# Commit changes
git commit -m "chore: remove test-user auto-recycle bypass hook"

# Push to Railway production remote
git push target main
```

Once pushed, Railway will automatically rebuild and standard production constraints will be active again!
