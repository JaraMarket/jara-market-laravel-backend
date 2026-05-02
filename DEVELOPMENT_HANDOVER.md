# 🚀 JaraMarket: Frontend Integration Guide (Flutter)

Hey,

I've successfully stabilized the backend and performed a surgical cleanup of the production database. Everything is now live on Railway and ready for you to begin the Flutter integration. 

Below are the core details, endpoints, and authentication flows you'll need to get started.

---

## 🔗 Environment & Documentation

| Variable | Production Value |
| :--- | :--- |
| **Base API URL** | `https://jara-market-laravel-backend-production.up.railway.app/api/jaram` |
| **Swagger UI** | `https://jara-market-laravel-backend-production.up.railway.app/api/documentation` |
| **Auth Strategy** | `Bearer Token (Laravel Sanctum)` |

> [!TIP]
> Use the **Swagger UI** to test the endpoints interactively. Once you log in, copy the token and use the "Authorize" button (top right) to unlock protected routes.

---

## 🔑 Authentication Flow

All protected routes require a Bearer token in the header:
`Authorization: Bearer 2|qZrjSJFP0GKNXHjL0nfuxG03NZfzCRYPuYry3nTo098cb68e`
`Accept: application/json`

### 1. Login
*   **Endpoint:** `POST /login`
*   **Payload:** `{ "email": "...", "password": "..." }`
*   **Test Credentials:**
    *   **Email:** `admin@jaramarket.com`
    *   **Password:** `Admin@123`
    *   *(Alternative: `admin@gmail.com` / `admin1234`)*

### 2. Registration
*   **Endpoint:** `POST /register`
*   **Fields:** `firstname`, `lastname`, `email`, `phone_number`, `password`, `password_confirmation`.

### 3. User Profile
*   **Endpoint:** `GET /fetch-user` (Requires Auth)

---

## 🥗 Core Data Endpoints

The database has been cleaned of all legacy test data. We currently have 4 core "Gold Master" products for your initial UI testing.

### 1. Products
*   **Endpoint:** `GET /fetch/product`
*   **Returns:** A list of meals (Rice, White Soup, Egusi, Afang).
*   **Note:** These currently have `image_url: null`. I'll be syncing the physical assets shortly.

### 2. Ingredients
*   **Endpoint:** `GET /fetch/ingredients`
*   **Returns:** A list of raw materials/ingredients for the meals.

### 3. Categories
*   **Endpoint:** `GET /vendors/categories`

---

## 📸 Media Handling

Images are served via the public storage link. Once assets are uploaded, the full URL will follow this pattern:
`https://jara-market-laravel-backend-production.up.railway.app/storage/<image_path>`

---

## 🛠️ Next Steps

The backend is now in a "stable" state. You can reliably point the Flutter app to the Railway production URL. If you encounter any `404` or `401` errors, double-check the `api/jaram` prefix in your base URL.

Let me know if you need any specific endpoint adjustments as you build out the screens!

Best,
Inimfon
