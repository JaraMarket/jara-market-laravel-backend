# JaraMarket Database Schema Documentation

## Overview
This document provides context for AI agents (like Gemini CLI) regarding the `equivuxb_jaramarket.sql` database dump. This SQL file is the structural blueprint and initial data seed for the **JaraMarket** backend infrastructure. 

It is designed to run on a **MariaDB/MySQL** relational database engine and appears to be heavily integrated with a modern PHP framework (highly likely **Laravel**, evidenced by typical timestamp columns like `created_at` and `updated_at`, pivot table naming conventions, and morph types like `App\Models\User`).

## Database Engine Details
- **Target Engine:** MySQL / MariaDB (Exported from 11.4.10-MariaDB)
- **Collation:** `utf8mb4_unicode_ci` (Full Unicode support, suitable for emojis and international text)
- **Scalability Note:** The primary keys (`id`) across the major tables utilize `bigint(20) UNSIGNED`. This infrastructure is easily capable of handling extremely large volumes of data perfectly out of the box (e.g., smoothly scaling past 50,000 users and 2,000 products without structural modification).

## Core Table Structures & Context

### 1. User & Access Management
*   **`admins` & `admin_permissions`**: Stores dashboard administrators and their respective access rights.
*   *(Note: The main `users` table is implied to exist further down in the SQL file, as it is referenced as a foreign key in tables like `addresses` and `bank_accounts`)*.

### 2. E-Commerce & Marketplace Core
*   **`carts` & `cart_items`**: Manages active user shopping sessions and the specific products added to them.
*   **`categories`, `category_types`, `category_product`, `category_user`**: A robust categorization system. Note the use of pivot tables (`category_product` and `category_user`), which indicates a many-to-many relationship (e.g., a product can belong to multiple categories, a user/vendor can serve multiple categories). Categories include "African Recipes", "Stews & Sauces", "Keto Meal", etc.
*   **`advertisements`**: Manages promotional blocks, discounts, and visual banners.
*   **`commissions`**: Defines business logic for platform cuts based on varying order amounts.

### 3. Localization & Financials
*   **`addresses` & `countries`**: Handles user shipping/billing information mapped to geographical regions.
*   **`banks` & `bank_accounts`**: Stores a comprehensive list of Nigerian financial institutions and associates specific user withdrawal accounts using polymorphic relations (`owner_type` = `App\Models\User`).

### 4. System Performance
*   **`cache` & `cache_locks`**: Used by the backend framework to store temporary data and prevent race conditions, drastically improving response times for high-traffic environments.

## Instructions for AI Agents Working with this File
1.  **Framework Context:** When interacting with code connected to this database, default to Laravel Eloquent ORM conventions unless instructed otherwise.
2.  **Data Types:** Respect the `UNSIGNED BIGINT` constraints when generating migrations or adding new related tables.
3.  **Relationships:** Pay close attention to pivot table naming (e.g., `category_product`) which signifies a `belongsToMany` relationship.
4.  **Security:** Always use parameterized queries or the framework's ORM to interact with this data to prevent SQL injection.

---
*Created for Gemini CLI to maintain contextual awareness of the JaraMarket database architecture.*
