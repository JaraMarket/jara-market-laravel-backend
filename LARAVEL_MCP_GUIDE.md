# Complete Guide: Connecting Gemini CLI to Laravel MCP Servers

This guide details how to integrate your Gemini CLI with two different types of Laravel MCP integrations: the **Laravel MCP Package** (a framework to build servers) and **Laravel Docs MCP** (a pre-built documentation server).

## 1. Understanding the Two "Laravel MCP" Projects

1. **[laravel/mcp](https://github.com/laravel/mcp):** This is a **PHP package** that allows you to *build your own* MCP servers using the Laravel framework.
2. **[brianirish/laravel-docs-mcp](https://github.com/brianirish/laravel-docs-mcp):** This is an **actual, pre-built MCP server** (Laravel MCP Companion) that provides curated documentation for the Laravel ecosystem.

---

## 2. Connecting to Laravel-Docs-MCP (Companion)

### Prerequisites
- Docker must be installed and running.

### Connection Command
```bash
gemini mcp add laravel-docs-mcp docker run --rm -i ghcr.io/brianirish/laravel-mcp-companion:latest
```

---

## 3. Connecting to a Custom Server Built with `laravel/mcp`

### Step A: Set up your Laravel Application
1. Install the package: `composer require laravel/mcp`
2. Publish routes: `php artisan vendor:publish --tag=ai-routes`
3. Create a Server: `php artisan make:mcp-server MyServer`

### Step B: Register as a Local Server (STDIO) in `routes/ai.php`
```php
use App\Mcp\Servers\MyServer;
use Laravel\Mcp\Facades\Mcp;
Mcp::local('myserver', MyServer::class);
```

### Step C: Connect Gemini CLI
```bash
gemini mcp add my-custom-laravel-server php artisan mcp:start myserver
```
