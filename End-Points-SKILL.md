---
name: laravel-endpoint-auditor
description: >
  A structured skill for auditing, testing, verifying, fixing, and certifying
  Laravel REST API endpoints for a Flutter mobile app (Customer + Vendor marketplace).
  Use this skill whenever an agent is asked to: check if endpoints exist, test if
  endpoints are working, verify API responses, fix broken routes or controllers,
  audit a Laravel backend, generate a Postman collection, or prepare API endpoints
  for frontend handover. Triggers on phrases like "check endpoints", "test API",
  "verify backend", "fix endpoints", "are the routes working", or "prepare for
  frontend developer".
---

# Laravel Endpoint Auditor Skill

## Purpose
Guide an agent through a **complete, non-destructive audit loop** of a Laravel backend
hosted on Railway with AWS S3 for file storage. The agent must:
- Preserve ALL working endpoints exactly as-is
- Fix or recreate ONLY broken or missing endpoints
- Loop diagnosis → fix → retest until everything passes
- Output a verified Postman Collection for frontend handover

---

## Context: This Project

- **Backend:** Laravel + Sanctum (Railway hosted)
- **Database:** MySQL (Railway)
- **File Storage:** AWS S3
- **Frontend:** Two Flutter apps — Customer App + Vendor App
- **Auth:** Laravel Sanctum (Bearer token)
- **Payment:** Paystack
- **Notifications:** Firebase FCM

**Golden Rule:** If an endpoint exists and passes all tests → LEAVE IT ALONE.

---

## PHASE 0 — Setup & Orientation

Before touching anything, agent must gather context.

### Step 0.1 — Read the Project
```bash
# Understand the project structure
cat routes/api.php
ls app/Http/Controllers/
ls app/Models/
cat .env | grep -E "APP_URL|DB_|AWS_|SANCTUM"
```

### Step 0.2 — Capture the Base URL
```bash
# Get the live Railway URL from .env
grep APP_URL .env
```
Save this as `BASE_URL`. All tests will use this.

### Step 0.3 — Build the Master Endpoint Checklist
Agent must map every endpoint from `api.php` into this tracking format:

```
[ ] METHOD  /api/endpoint/path  →  Status: UNCHECKED
```

If an endpoint from the required list (see PHASE 1) is missing from `api.php`, mark it:
```
[MISSING] POST  /api/vendor/products  →  Status: NOT BUILT
```

---

## PHASE 1 — Required Endpoint Master List

These are ALL endpoints that must exist and pass testing.

### AUTH (Both Apps)
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
POST   /api/auth/forgot-password
POST   /api/auth/reset-password
PUT    /api/auth/update-profile
POST   /api/auth/upload-avatar
```

### CUSTOMER APP
```
GET    /api/vendors
GET    /api/vendors/{id}
GET    /api/categories
GET    /api/vendors?category={id}
GET    /api/vendors?search={keyword}
GET    /api/products
GET    /api/products/{id}
GET    /api/products?vendor_id={id}
POST   /api/orders
GET    /api/orders
GET    /api/orders/{id}
PUT    /api/orders/{id}/cancel
POST   /api/payments/initiate
POST   /api/payments/verify
GET    /api/payments/history
POST   /api/vendors/{id}/reviews
GET    /api/vendors/{id}/reviews
POST   /api/notifications/token
GET    /api/notifications
PUT    /api/notifications/{id}/read
```

### VENDOR APP
```
GET    /api/vendor/profile
PUT    /api/vendor/profile
POST   /api/vendor/upload-logo
POST   /api/vendor/upload-banner
GET    /api/vendor/products
POST   /api/vendor/products
GET    /api/vendor/products/{id}
PUT    /api/vendor/products/{id}
DELETE /api/vendor/products/{id}
POST   /api/vendor/products/{id}/images
GET    /api/vendor/orders
GET    /api/vendor/orders/{id}
PUT    /api/vendor/orders/{id}/status
GET    /api/vendor/earnings
GET    /api/vendor/payouts
POST   /api/vendor/payouts/request
```

### ADMIN
```
GET    /api/admin/users
PUT    /api/admin/users/{id}/suspend
GET    /api/admin/vendors
PUT    /api/admin/vendors/{id}/approve
PUT    /api/admin/vendors/{id}/reject
GET    /api/admin/orders
GET    /api/admin/payments
GET    /api/admin/categories
POST   /api/admin/categories
PUT    /api/admin/categories/{id}
DELETE /api/admin/categories/{id}
POST   /api/admin/notifications/send
GET    /api/admin/dashboard/stats
```

---

## PHASE 2 — Authentication (Get a Test Token)

Every protected endpoint needs a valid Bearer token. Do this first.

### Step 2.1 — Register a test user (if needed)
```bash
curl -s -X POST {BASE_URL}/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@bizztechhub.com","password":"Password123!","password_confirmation":"Password123!","role":"customer"}'
```

### Step 2.2 — Login and capture token
```bash
curl -s -X POST {BASE_URL}/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@bizztechhub.com","password":"Password123!"}'
```
Save the returned token as `CUSTOMER_TOKEN`.

Repeat with `"role":"vendor"` → save as `VENDOR_TOKEN`.

### Step 2.3 — Verify token works
```bash
curl -s -X GET {BASE_URL}/api/auth/me \
  -H "Authorization: Bearer {CUSTOMER_TOKEN}"
```
Expected: `200 OK` with user object. If this fails, stop and fix auth before proceeding.

---

## PHASE 3 — The Diagnosis Loop

Run this loop for **every single endpoint** in the master list.

### For each endpoint:

**Step A — Check if route exists**
```bash
php artisan route:list --path=api | grep "endpoint-path"
```
- EXISTS → go to Step B
- MISSING → go to PHASE 4 (Fix Protocol)

**Step B — Check if controller method exists**
```bash
grep -r "methodName" app/Http/Controllers/
```
- EXISTS → go to Step C
- MISSING → go to PHASE 4 (Fix Protocol)

**Step C — Hit the endpoint live**
```bash
curl -s -o /tmp/response.json -w "%{http_code}" \
  -X {METHOD} {BASE_URL}/{endpoint} \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{test_payload}'
```

**Step D — Evaluate the response**

| HTTP Code | Meaning | Action |
|-----------|---------|--------|
| `200` or `201` | ✅ PASS | Mark VERIFIED. Move to next. |
| `401` | Auth rejected | Check middleware, token |
| `404` | Route not found | Fix route or add missing route |
| `422` | Validation error | Check request payload / validation rules |
| `500` | Server error | Check Laravel logs immediately |
| `000` / timeout | App down | Check Railway deployment status |

**Step E — Log the result**
```
[PASS]   GET  /api/vendors           → 200 OK
[FAIL]   POST /api/orders            → 500 Server Error → QUEUED FOR FIX
[MISSING] PUT /api/vendor/profile    → Route not in api.php → QUEUED FOR FIX
```

---

## PHASE 4 — Fix Protocol

**Only triggered when an endpoint FAILS or is MISSING.**

### Rule: Never break what works.
Before editing any file, agent must confirm the file is not shared with a passing endpoint.

### Fix Decision Tree

```
Is the route missing from api.php?
  YES → Add the route only. Do not touch other routes.
  NO  → Is the Controller missing or method missing?
          YES → Create Controller or add method only.
          NO  → Is it a database/migration issue?
                  YES → Create and run the migration.
                  NO  → Check .env config (S3, Sanctum, DB).
```

### Step 4.1 — Add a missing route
```php
// In routes/api.php — add inside appropriate middleware group
Route::put('/vendor/profile', [VendorController::class, 'updateProfile']);
```

### Step 4.2 — Create a missing controller method
```php
// Standard response format — ALWAYS use this
public function methodName(Request $request)
{
    // logic here
    return response()->json([
        'status'  => true,
        'message' => 'Success',
        'data'    => $result
    ], 200);
}
```

### Step 4.3 — After every fix, immediately retest
Go back to PHASE 3, Step C for that endpoint.
Do NOT move on until it returns `200` or `201`.

### Step 4.4 — Check Laravel logs if 500 errors persist
```bash
tail -n 50 storage/logs/laravel.log
```

---

## PHASE 5 — Special Endpoint Instructions

### Paystack Payment Flow
```
POST /api/payments/initiate
  → Receives: amount, email, order_id
  → Calls Paystack API to create a transaction
  → Returns: { authorization_url, reference }
  → Flutter opens authorization_url in WebView

POST /api/payments/verify
  → Receives: reference
  → Calls Paystack verify endpoint
  → On success: updates order status to 'paid'
  → Returns: { status: true, message: "Payment verified" }
```
Test initiate with: `{"amount":1000,"email":"test@bizztechhub.com","order_id":1}`

### AWS S3 File Uploads
All upload endpoints must:
1. Accept `multipart/form-data`
2. Validate file type (jpeg, png, jpg, max 2MB)
3. Store to S3 using `Storage::disk('s3')->put()`
4. Return the full S3 URL in response

Test with:
```bash
curl -s -X POST {BASE_URL}/api/auth/upload-avatar \
  -H "Authorization: Bearer {TOKEN}" \
  -F "avatar=@/path/to/test-image.jpg"
```

### FCM Push Notifications
```
POST /api/notifications/token
  → Receives: { device_token: "FCM_TOKEN_STRING", platform: "android|ios" }
  → Saves to device_tokens table linked to user
  → Returns: { status: true, message: "Token saved" }
```

---

## PHASE 6 — Generate Postman Collection

Run after ALL endpoints are verified.

### Step 6.1 — Generate the collection file

Create `BizzTechHub_API.postman_collection.json` using this structure:

```json
{
  "info": {
    "name": "BizzTechHub Marketplace API",
    "description": "Complete verified API for Customer + Vendor Flutter Apps",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "variable": [
    { "key": "BASE_URL", "value": "{RAILWAY_URL}" },
    { "key": "CUSTOMER_TOKEN", "value": "" },
    { "key": "VENDOR_TOKEN", "value": "" }
  ],
  "item": [
    // One folder per section: Auth, Customer, Vendor, Admin
    // One request per endpoint
    // All protected routes use: Bearer {{CUSTOMER_TOKEN}} or {{VENDOR_TOKEN}}
  ]
}
```

### Step 6.2 — Every request entry must include:
- Method + full URL using `{{BASE_URL}}`
- Headers: `Content-Type: application/json` + `Authorization: Bearer {{TOKEN}}`
- A sample request body (where applicable)
- A sample expected response as a comment/description

### Step 6.3 — Add Postman Test Scripts
For every request, add this auto-test script in Postman:
```javascript
pm.test("Status is 200 or 201", function () {
    pm.expect(pm.response.code).to.be.oneOf([200, 201]);
});
pm.test("Response has status:true", function () {
    const json = pm.response.json();
    pm.expect(json.status).to.eql(true);
});
```

---

## PHASE 7 — Final Certification Checklist

Agent must confirm ALL of the following before declaring done:

```
AUTHENTICATION
[ ] POST /api/auth/register         → 201
[ ] POST /api/auth/login            → 200 + token returned
[ ] POST /api/auth/logout           → 200
[ ] GET  /api/auth/me               → 200 + user object
[ ] POST /api/auth/forgot-password  → 200
[ ] POST /api/auth/reset-password   → 200
[ ] PUT  /api/auth/update-profile   → 200
[ ] POST /api/auth/upload-avatar    → 200 + S3 URL

CUSTOMER APP (20 endpoints)
[ ] All 20 endpoints return 200/201

VENDOR APP (16 endpoints)
[ ] All 16 endpoints return 200/201

ADMIN (13 endpoints)
[ ] All 13 endpoints return 200/201

SPECIAL
[ ] Paystack initiate returns authorization_url
[ ] Paystack verify updates order to 'paid'
[ ] S3 uploads return a valid URL
[ ] FCM token saves successfully

OUTPUT
[ ] Postman Collection generated → BizzTechHub_API.postman_collection.json
[ ] All Postman test scripts pass
[ ] Zero endpoints with FAIL or MISSING status
```

Only when ALL boxes are checked → handover to Flutter developer is approved.

---

## PHASE 8 — Handover Package

Deliver these two files to the frontend developer:

1. **`Jaramarket_API.postman_collection.json`** — Import into Postman to test live
2. **`ENDPOINT_AUDIT_REPORT.md`** — Summary of what was found, fixed, and verified

### ENDPOINT_AUDIT_REPORT.md template:
```markdown
# Jaramerket API Audit Report
Date: {DATE}
Base URL: {RAILWAY_URL}
Laravel Version: {VERSION}

## Summary
- Total endpoints audited: XX
- Already working (untouched): XX
- Fixed: XX
- Newly created: XX
- Final status: ALL PASSING ✅

## Fixed Endpoints
| Endpoint | Issue Found | Fix Applied |
|----------|------------|-------------|
| ...      | ...        | ...         |

## Notes for Flutter Developer
- Use Bearer token from /api/auth/login in all protected requests
- Paystack: open authorization_url in WebView, then call /api/payments/verify
- File uploads: use multipart/form-data
- All responses follow: { status, message, data }
```

---

## Agent Behaviour Rules

1. **Always check before creating** — never duplicate an existing working endpoint
2. **One fix at a time** — fix, retest, confirm, then move to the next
3. **Never edit `.env`** — read it only
4. **Log everything** — maintain a running pass/fail log throughout
5. **If unsure about a fix** — flag it with `[NEEDS HUMAN REVIEW]` and move on
6. **Respect existing naming conventions** — match whatever naming the original developer used
