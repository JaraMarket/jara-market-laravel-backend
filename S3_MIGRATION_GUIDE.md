# 🚀 JaraMarket: AWS S3 & Firebase Migration Guide

This document is for the next agent to complete the transition from local storage to persistent cloud storage.

## 📋 Current Status
- **Host:** Railway
- **Database:** MySQL (Internal Railway Service)
- **File Storage:** Currently set to `public` (Local ephemeral storage)
- **GitHub Sync:** Enabled. Pushing to `main` branch triggers auto-deploy.

---

## 🛠️ Task 1: Migrate to AWS S3
Railway's filesystem is ephemeral. All images uploaded will be lost on the next redeploy unless S3 is configured.

### **Steps:**
1. **Get Credentials:** The user will provide:
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`
   - `AWS_DEFAULT_REGION`
   - `AWS_BUCKET`
   - `AWS_URL`

2. **Update Railway Variables:**
   Run the following or use the Railway Dashboard:
   ```bash
   railway variables set FILESYSTEM_DISK=s3 AWS_ACCESS_KEY_ID=xxx AWS_SECRET_ACCESS_KEY=xxx AWS_DEFAULT_REGION=xxx AWS_BUCKET=xxx AWS_URL=xxx
   ```

3. **Verify Product Images:**
   Ensure that the `Product` model and `ProductController` are using the `s3` disk for uploads.

---

## 🛠️ Task 2: Configure Firebase
Firebase is required for push notifications and other services.

### **Steps:**
1. **Locate Credentials:** The JSON file is located at:
   `storage/app/firebase/jaramarket-30f6a-firebase-adminsdk-fbsvc-6daa0bdd1d.json`
2. **Update Variable:**
   Ensure `FIREBASE_PROJECT_ID=jaramarket-30f6a` is set in Railway.

---

## 🛠️ Task 3: Final Production Cleanup
Once testing is complete:
1. Set `APP_DEBUG=false` in Railway.
2. Set `APP_ENV=production`.
3. Update `APP_URL` to the final custom domain.

---
**Note:** The backend is currently running with `APP_DEBUG=true` for easier troubleshooting during the initial launch.
